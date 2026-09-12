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
 *
 * @param {string} endpoint - Path API
 * @param {object} options  - Opsi fetch
 * @returns {Promise<any>}
 */
export async function apiFetch(endpoint, options = {}) {
  const isAdminRoute = endpoint.includes('admin.php');

  const token    = isAdminRoute ? sessionStorage.getItem(ADMIN_TOKEN_KEY) : legacyGetToken();
  const csrf     = isAdminRoute ? sessionStorage.getItem(ADMIN_CSRF_KEY)  : getCsrfToken();

  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  // Bearer token — backend menggunakan ini sebagai fallback jika cookie tidak ada
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // CSRF token — wajib untuk semua mutating requests (POST/PUT/DELETE)
  if (csrf && options.method && options.method !== 'GET') {
    headers['X-CSRF-Token'] = csrf;
  }

  const response = await fetch(`${BASE_URL}${endpoint}`, {
    ...options,
    headers,
    credentials: 'include',   // Kirim & terima HttpOnly cookie
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || data.message || 'Terjadi kesalahan pada server');
  }

  return data;
}

export default {
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
      await apiFetch('/auth.php?action=logout', { method: 'POST' });
    } finally {
      clearAuthTokens();
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
  getMaterials(subtes = '') {
    return apiFetch(`/materials.php?action=list&subtes=${subtes}`);
  },

  // ── Admin Endpoints ──
  getAdminDashboard()                  { return apiFetch('/admin.php?action=stats'); },
  getAdminOrders(page = 1, status = '') {
    return apiFetch(`/admin.php?action=orders&page=${page}&status=${status}`);
  },
  getAdminStudents(page = 1, search = '', plan = '') {
    return apiFetch(`/admin.php?action=students&page=${page}&search=${search}&plan=${plan}`);
  },
  createAdminStudent(data) {
    return apiFetch('/admin.php?action=students', { method: 'POST', body: JSON.stringify(data) });
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
  getAdminPlans()            { return apiFetch('/admin.php?action=plans'); },
  createAdminPlan(data)      { return apiFetch('/admin.php?action=plans', { method: 'POST', body: JSON.stringify(data) }); },
  updateAdminPlan(id, data)  { return apiFetch('/admin.php?action=plans', { method: 'PUT', body: JSON.stringify({ id, ...data }) }); },
  deleteAdminPlan(id)        { return apiFetch(`/admin.php?action=plans&id=${id}`, { method: 'DELETE' }); },
  getEntitlementsDictionary() { return apiFetch('/admin.php?action=entitlements_dictionary'); },

  // ── Password Reset ──
  forgotPassword(email) {
    return apiFetch('/password_reset.php?action=forgot', {
      method: 'POST',
      body: JSON.stringify({ email })
    });
  },
  resetPassword(email, token, newPassword) {
    return apiFetch('/password_reset.php?action=reset', {
      method: 'POST',
      body: JSON.stringify({ email, token, new_password: newPassword })
    });
  },
  updateStudentPassword(currentPassword, newPassword) {
    return apiFetch('/auth.php?action=update_password', {
      method: 'POST',
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
    });
  },

  // Affiliate Endpoints
  getAffiliateProfile() {
    return apiFetch('/affiliate.php');
  },
  joinAffiliate() {
    return apiFetch('/affiliate.php', { method: 'POST' });
  },

  // ── Stub (belum diimplementasikan) ──
  createAdminStaff(payload) { return apiFetch('/admin.php?action=staff', { method: 'POST', body: JSON.stringify(payload) }); },
  getAdminStaff()      { return apiFetch('/admin.php?action=staff'); },
  updateAdminStaff(id, payload) { return apiFetch('/admin.php?action=staff', { method: 'PUT', body: JSON.stringify({ id, ...payload }) }); },
  deleteAdminStaff(id) { return apiFetch(`/admin.php?action=staff&id=${id}`, { method: 'DELETE' }); },
  getAdminMaterials()  { return Promise.resolve([]); },

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
