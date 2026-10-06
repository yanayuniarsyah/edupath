// =====================================================
// EduPath Frontend — src/api.js
// Fetch-based HTTP Client untuk komunikasi dengan Backend PHP
// =====================================================

// Baca URL API dari environment variable (dikonfigurasi di .env atau .env.production)
// Set VITE_API_URL di file .env Anda. Lihat .env.example untuk petunjuk.
const BASE_URL = import.meta.env.VITE_API_URL;

if (!BASE_URL) {
  console.error(
    '[EduPath] VITE_API_URL tidak dikonfigurasi!\n' +
    'Salin .env.example ke .env dan isi VITE_API_URL.'
  );
}

// =====================================================
// IN-MEMORY CACHE LAYER
// Mencegah request berulang untuk data yang jarang berubah.
// Cache dibuang saat user logout / page refresh (in-memory, tidak persistent).
// =====================================================

const _cache = new Map(); // key → { data, expiresAt }
const _inflight = new Map(); // key → Promise (request deduplication)

/**
 * TTL (time-to-live) dalam milidetik per endpoint.
 * Sesuaikan jika ada endpoint yang frekuensi update-nya berbeda.
 */
const CACHE_TTL = {
  // Data statis / jarang berubah → cache lebih lama
  '/plans.php':                       5 * 60 * 1000,  // 5 menit
  '/materials.php?action=list':       3 * 60 * 1000,  // 3 menit
  '/spp.php?action=data':             5 * 60 * 1000,  // 5 menit
  // Data profil user → cache pendek (30 detik) agar perubahan cepat terasa
  '/auth.php?action=me':              30 * 1000,       // 30 detik
};

function getCacheTtl(endpoint) {
  for (const [pattern, ttl] of Object.entries(CACHE_TTL)) {
    if (endpoint.includes(pattern)) return ttl;
  }
  return 0; // default: tidak di-cache
}

function cacheGet(key) {
  const entry = _cache.get(key);
  if (!entry) return null;
  if (Date.now() > entry.expiresAt) {
    _cache.delete(key);
    return null;
  }
  return entry.data;
}

function cacheSet(key, data, ttlMs) {
  if (ttlMs <= 0) return;
  _cache.set(key, { data, expiresAt: Date.now() + ttlMs });
}

/** Hapus semua cache (dipanggil saat logout) */
export function clearApiCache() {
  _cache.clear();
  _inflight.clear();
}

/** Hapus cache untuk endpoint tertentu (dipanggil setelah mutasi data) */
export function invalidateCache(endpointPattern) {
  for (const key of _cache.keys()) {
    if (key.includes(endpointPattern)) _cache.delete(key);
  }
}

// =====================================================
// TOKEN MANAGEMENT
// Auth Strategy: HttpOnly cookie (primary) + Bearer fallback
//
// - Access token: disimpan sebagai HttpOnly cookie oleh backend
//   Browser mengirimkan cookie ini secara otomatis pada setiap request
//   JavaScript TIDAK bisa membaca cookie ini (XSS protection)
//
// - CSRF token: disimpan di sessionStorage (bukan localStorage)
//   Dikirim sebagai X-CSRF-Token header pada setiap mutating request
//
// - Bearer token: disimpan di sessionStorage sebagai fallback
//   untuk backward compatibility dengan Android app / external API client
//   Akan dihapus setelah semua client menggunakan cookie-based auth
// =====================================================

const TOKEN_KEY        = 'ep_session_token';     // sessionStorage (bukan localStorage)
const CSRF_KEY         = 'ep_csrf';              // sessionStorage
const ADMIN_TOKEN_KEY  = 'ep_admin_token';       // sessionStorage (admin)
const ADMIN_CSRF_KEY   = 'ep_admin_csrf';        // sessionStorage (admin)

/**
 * Simpan token auth ke sessionStorage (bukan localStorage)
 * HttpOnly cookie di-set oleh backend secara otomatis
 */
export function saveAuthTokens(token, csrfToken, refreshToken) {
  if (token)        sessionStorage.setItem(TOKEN_KEY, token);
  if (csrfToken)    sessionStorage.setItem(CSRF_KEY, csrfToken);
  if (refreshToken) sessionStorage.setItem('ep_refresh', refreshToken);
}

export function clearAuthTokens() {
  [TOKEN_KEY, CSRF_KEY, 'ep_refresh'].forEach(k => sessionStorage.removeItem(k));
  // CATATAN: HttpOnly cookie dibersihkan oleh backend saat logout
}

export function getAuthToken()   { return sessionStorage.getItem(TOKEN_KEY); }
export function getCsrfToken()   { return sessionStorage.getItem(CSRF_KEY); }

export function saveAdminTokens(token, csrfToken) {
  if (token)     sessionStorage.setItem(ADMIN_TOKEN_KEY, token);
  if (csrfToken) sessionStorage.setItem(ADMIN_CSRF_KEY, csrfToken);
}

export function clearAdminTokens() {
  [ADMIN_TOKEN_KEY, ADMIN_CSRF_KEY].forEach(k => sessionStorage.removeItem(k));
}

// Legacy read (backward compat — untuk kode yang belum diupdate)
export function legacyGetToken() {
  return sessionStorage.getItem(TOKEN_KEY) || localStorage.getItem('auth_token') || null;
}

/**
 * Wrapper untuk native fetch dengan penambahan:
 * - Authorization Bearer header (fallback untuk mobile/API)
 * - X-CSRF-Token header (untuk cookie-based auth)
 * - credentials: 'include' (kirim HttpOnly cookie)
 * - In-memory cache untuk GET requests
 * - Request deduplication (mencegah double-fire untuk request yg sama)
 * - Auto-retry untuk transient network errors
 *
 * @param {string} endpoint - Path API
 * @param {object} options  - Opsi fetch
 * @returns {Promise<any>}
 */
function getCookie(name) {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : null;
}

export async function apiFetch(endpoint, options = {}) {
  const isAdminRoute = endpoint.includes('admin.php') || endpoint.includes('admin_affiliate.php');
  const method = (options.method || 'GET').toUpperCase();

  const token = isAdminRoute
    ? (sessionStorage.getItem(ADMIN_TOKEN_KEY) || sessionStorage.getItem('admin_token') || legacyGetToken())
    : legacyGetToken();

  const csrf = (isAdminRoute ? sessionStorage.getItem(ADMIN_CSRF_KEY) : getCsrfToken())
    || sessionStorage.getItem(ADMIN_CSRF_KEY)
    || getCsrfToken()
    || getCookie('ep_csrf_token');

  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  // Bearer token — backend menggunakan ini sebagai fallback jika cookie tidak ada
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // CSRF token — wajib untuk semua mutating requests (POST/PUT/DELETE)
  if (csrf && method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
    headers['X-CSRF-Token'] = csrf;
  }

  const fullKey = `${method}:${BASE_URL}${endpoint}`;

  // ── CACHE CHECK (hanya GET, bukan admin route) ──────────────────────────
  if (method === 'GET' && !isAdminRoute) {
    const cached = cacheGet(fullKey);
    if (cached !== null) return cached;

    // Request deduplication: jika request yang sama sudah in-flight, tunggu hasilnya
    if (_inflight.has(fullKey)) {
      return _inflight.get(fullKey);
    }
  }

  // ── FETCH WITH RETRY ────────────────────────────────────────────────────
  const maxRetries = options.retries ?? (method === 'GET' ? 2 : 0); // GET: 2x retry, POST/PUT/DELETE: 0

  const doFetch = async (attempt = 0) => {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), options.timeoutMs || 30000);
    let response;
    try {
      response = await fetch(`${BASE_URL}${endpoint}`, {
        ...options,
        method,
        headers,
        credentials: 'include',
        signal: options.signal || controller.signal,
      });
    } catch (error) {
      if (error.name === 'AbortError') throw new Error('Permintaan ke server habis waktu. Silakan coba lagi.');
      // Retry untuk network error (bukan AbortError)
      if (attempt < maxRetries) {
        const delay = Math.pow(2, attempt) * 300; // 300ms, 600ms
        await new Promise(r => setTimeout(r, delay));
        return doFetch(attempt + 1);
      }
      throw new Error('Tidak dapat terhubung ke server.');
    } finally {
      clearTimeout(timeout);
    }

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : await response.text();

    if (!response.ok) {
      // Retry pada 503 (server overload) untuk GET requests
      if (response.status === 503 && attempt < maxRetries && method === 'GET') {
        const delay = Math.pow(2, attempt) * 500;
        await new Promise(r => setTimeout(r, delay));
        return doFetch(attempt + 1);
      }
      throw new Error(data.error || data.message || 'Terjadi kesalahan pada server');
    }

    return data;
  };

  // ── INFLIGHT TRACKING + CACHE WRITE ────────────────────────────────────
  if (method === 'GET' && !isAdminRoute) {
    const promise = doFetch().then(data => {
      const ttl = getCacheTtl(endpoint);
      cacheSet(fullKey, data, ttl);
      _inflight.delete(fullKey);
      return data;
    }).catch(err => {
      _inflight.delete(fullKey);
      throw err;
    });

    _inflight.set(fullKey, promise);
    return promise;
  }

  return doFetch();
}

export default {
  // ── Plans ──
  /**
   * Retrieve list of available subscription plans.
   * Uses the cached endpoint '/plans.php' with TTL defined in CACHE_TTL.
   */
  getPlans() {
    return apiFetch('/plans.php');
  },
  // ── Auth Siswa ──
  async login(email, password) {
    const res = await apiFetch('/auth.php?action=login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
    // Simpan token di sessionStorage (cookie di-set oleh backend)
    saveAuthTokens(res.token, res.csrf_token, res.refresh_token);
    return res;
  },

  async register(userData) {
    const res = await apiFetch('/auth.php?action=register', {
      method: 'POST',
      body: JSON.stringify(userData)
    });
    saveAuthTokens(res.token, res.csrf_token, res.refresh_token);
    return res;
  },

  getProfile() {
    return apiFetch('/auth.php?action=me');
  },

  async logout() {
    try {
      if (sessionStorage.getItem('admin_token') || sessionStorage.getItem(ADMIN_TOKEN_KEY)) {
        await apiFetch('/admin.php?action=logout', { method: 'POST' });
      } else {
        await apiFetch('/auth.php?action=logout', { method: 'POST' });
      }
    } finally {
      clearAuthTokens();
      clearAdminTokens();
      clearApiCache(); // Bersihkan cache saat logout
      // Hapus legacy localStorage token jika masih ada
      localStorage.removeItem('auth_token');
    }
  },

  async refreshToken() {
    const refreshToken = sessionStorage.getItem('ep_refresh');
    if (!refreshToken) throw new Error('Tidak ada refresh token');
    const res = await apiFetch('/auth.php?action=refresh', {
      method: 'POST',
      body: JSON.stringify({ refresh_token: refreshToken })
    });
    saveAuthTokens(res.token, res.csrf_token, res.refresh_token);
    return res;
  },

  // ── Progress & Kuis ──
  saveProgress(_subtes, _bab, _score, _mastery) {
    // Placeholder — endpoint belum diimplementasikan
    return Promise.resolve();
  },

  saveQuizResult(quiz_type, subtes, score, correct, total, duration_sec, answers) {
    return apiFetch('/quiz.php?action=submit', {
      method: 'POST',
      body: JSON.stringify({ quiz_type, subtes, score, correct, total, duration_sec, answers })
    });
  },

  startDiagnostic() {
    return apiFetch('/diagnostic.php?action=start', { method: 'GET' });
  },

  submitDiagnostic(answers) {
    return apiFetch('/diagnostic.php?action=submit', {
      method: 'POST',
      body: JSON.stringify({ answers })
    });
  },

  // ── Payment ──
  checkout(plan_id) {
    return apiFetch('/payment.php?action=create', {
      method: 'POST',
      body: JSON.stringify({ plan_id })
    });
  },

  // ── Auth Admin ──
  async adminLogin(username, password) {
    const res = await apiFetch('/admin.php?action=login', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });
    saveAdminTokens(res.token, res.csrf_token);
    return res;
  },

  // ── Quiz ──
  getQuizQuestions(limit = 10, subtes = '') {
    return apiFetch(`/quiz.php?action=questions&limit=${limit}&subtes=${subtes}`);
  },
  submitQuiz(data) {
    return apiFetch('/quiz.php?action=submit', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },
  quizStart(data = {}) {
    return apiFetch('/quiz.php?action=start', { method: 'POST', body: JSON.stringify(data) });
  },
  quizSave(attemptId, answers) {
    return apiFetch('/quiz.php?action=save', {
      method: 'POST',
      body: JSON.stringify({ attempt_id: attemptId, answers })
    });
  },
  getMaterials(subtes = '') {
    return apiFetch(`/materials.php?action=list&subtes=${subtes}`);
  },

  // ── Admin Endpoints ──
  getAdminDashboard()                  { return apiFetch('/admin.php?action=stats'); },
  getAdminOrders(page = 1, status = '', student_id = '', search = '') {
    return apiFetch(`/admin.php?action=orders&page=${page}&status=${status}&student_id=${encodeURIComponent(student_id)}&search=${encodeURIComponent(search)}`);
  },
  createAdminOrder(data) {
    return apiFetch('/admin.php?action=orders', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminOrderStatus(orderId, status) {
    return apiFetch('/admin.php?action=orders', { method: 'PUT', body: JSON.stringify({ order_id: orderId, status }) });
  },
  deleteAdminOrder(orderId) {
    return apiFetch(`/admin.php?action=orders&order_id=${orderId}`, { method: 'DELETE' });
  },
  getAdminStudents(page = 1, search = '', plan = '') {
    return apiFetch(`/admin.php?action=students&page=${page}&search=${search}&plan=${plan}`);
  },
  createAdminStudent(data) {
    return apiFetch('/admin.php?action=students', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminStudent(id, data) {
    return apiFetch('/admin.php?action=students', { method: 'PUT', body: JSON.stringify({ id, ...data }) });
  },
  deleteAdminStudent(id) {
    return apiFetch(`/admin.php?action=students&id=${id}`, { method: 'DELETE' });
  },
  getAdminQuestions(page = 1, subtes = '') {
    return apiFetch(`/admin.php?action=questions&page=${page}&subtes=${subtes}`);
  },
  createAdminQuestion(data) {
    return apiFetch('/admin.php?action=questions', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminQuestion(id, data) {
    return apiFetch('/admin.php?action=questions', { method: 'PUT', body: JSON.stringify({ id, ...data }) });
  },
  deleteAdminQuestion(id) {
    return apiFetch(`/admin.php?action=questions&id=${id}`, { method: 'DELETE' });
  },
  getAdminMaterials() {
    return apiFetch('/admin.php?action=materials');
  },
  createAdminMaterial(data) {
    return apiFetch('/admin.php?action=materials', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminMaterial(id, data) {
    return apiFetch('/admin.php?action=materials', { method: 'PUT', body: JSON.stringify({ id, ...data }) });
  },
  deleteAdminMaterial(id) {
    return apiFetch(`/admin.php?action=materials&id=${id}`, { method: 'DELETE' });
  },
  getAdminPlans()            { return apiFetch('/admin.php?action=plans'); },
  createAdminPlan(data)      { return apiFetch('/admin.php?action=plans', { method: 'POST', body: JSON.stringify(data) }); },
  updateAdminPlan(id, data)  { return apiFetch('/admin.php?action=plans', { method: 'PUT', body: JSON.stringify({ id, ...data }) }); },
  deleteAdminPlan(id)        { return apiFetch(`/admin.php?action=plans&id=${id}`, { method: 'DELETE' }); },
  getEntitlementsDictionary() { return apiFetch('/admin.php?action=entitlements_dictionary'); },
  getAdminAffiliates()       { return apiFetch('/admin_affiliate.php'); },
  createAdminAffiliate(data) { return apiFetch('/admin_affiliate.php', { method: 'POST', body: JSON.stringify({ action: 'create_affiliate', ...data }) }); },
  updateAdminAffiliate(id, data) { return apiFetch('/admin_affiliate.php', { method: 'POST', body: JSON.stringify({ action: 'update_affiliate', id, ...data }) }); },
  deleteAdminAffiliate(id)   { return apiFetch('/admin_affiliate.php', { method: 'POST', body: JSON.stringify({ action: 'delete_affiliate', id }) }); },
  getAdminCommissions()      { return apiFetch('/admin.php?action=commissions'); },
  getAdminPayouts()          { return apiFetch('/admin_affiliate.php?action=payouts'); },
  approvePayout(id)          { return apiFetch('/admin_affiliate.php', { method: 'POST', body: JSON.stringify({ action: 'approve_payout', payout_id: id }) }); },
  rejectPayout(id, reason)   { return apiFetch('/admin_affiliate.php', { method: 'POST', body: JSON.stringify({ action: 'reject_payout', payout_id: id, reason }) }); },
  payoutCommission(id, ref)  { return apiFetch('/admin.php?action=payout_commission', { method: 'POST', body: JSON.stringify({ commission_id: id, payout_reference: ref }) }); },

  // ── Staff Management ──
  createAdminStaff(payload) { return apiFetch('/admin.php?action=staff', { method: 'POST', body: JSON.stringify(payload) }); },
  getAdminStaff()      { return apiFetch('/admin.php?action=staff'); },
  updateAdminStaff(id, payload) { return apiFetch('/admin.php?action=staff', { method: 'PUT', body: JSON.stringify({ id, ...payload }) }); },
  deleteAdminStaff(id) { return apiFetch(`/admin.php?action=staff&id=${id}`, { method: 'DELETE' }); },
  getAdminTenants()    { return apiFetch('/admin.php?action=tenants'); },

  // ── Student Potential Path (SPP) ──
  sppCreateAttempt() {
    return apiFetch('/spp.php?action=create_attempt', { method: 'POST' });
  },
  sppSubmitAttempt(attemptId, responses, durationSeconds) {
    return apiFetch(`/spp.php?action=submit_attempt&id=${attemptId}`, {
      method: 'POST',
      body: JSON.stringify({ responses, duration_seconds: durationSeconds })
    });
  },
  sppGetResult(attemptId) {
    return apiFetch(`/spp.php?action=get_result&id=${attemptId}`);
  },
  sppGetSchoolAggregate(schoolName) {
    return apiFetch(`/spp.php?action=get_school_aggregate&school_name=${encodeURIComponent(schoolName)}`);
  },

  // ── Analytics ──
  trackEvent(eventName, payload = {}) {
    // Analytics abstraction as requested. Currently a no-op / local mock.
    if (import.meta.env.DEV) {
      console.log(`[Analytics] ${eventName}`, payload);
    }
  }
};
