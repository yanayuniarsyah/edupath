// =====================================================
// EduPath Frontend — src/api.js
// Fetch-based HTTP Client untuk komunikasi dengan Backend
// =====================================================

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3001/api';

/**
 * Wrapper untuk native fetch dengan penambahan token Authorization
 * @param {string} endpoint - Path API (misalnya: /auth/login)
 * @param {object} options - Opsi fetch (method, body, dll)
 * @returns {Promise<any>}
 */
export async function apiFetch(endpoint, options = {}) {
  const isAdminRoute = endpoint.includes('/admin');
  const token = isAdminRoute ? sessionStorage.getItem('admin_token') : localStorage.getItem('auth_token');
  
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.message || 'Terjadi kesalahan pada server');
  }

  return data;
}

export default {
  // ── Auth Siswa ──
  login(email, password) {
    return apiFetch('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
  },
  register(userData) {
    return apiFetch('/auth/register', {
      method: 'POST',
      body: JSON.stringify(userData)
    });
  },
  getProfile() {
    return apiFetch('/auth/me');
  },
  forgotPassword(email) {
    return apiFetch('/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email })
    });
  },
  resetPassword(email, token, newPassword) {
    return apiFetch('/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({ email, token, newPassword })
    });
  },

  // ── Progress & Kuis ──
  saveProgress(subtes, bab, score, mastery) {
    return apiFetch('/progress', {
      method: 'POST',
      body: JSON.stringify({ subtes, bab, score, mastery })
    });
  },
  saveQuizResult(quiz_type, subtes, score, correct, total, duration_sec, answers) {
    return apiFetch('/progress/quiz', {
      method: 'POST',
      body: JSON.stringify({ quiz_type, subtes, score, correct, total, duration_sec, answers })
    });
  },

  // ── Payment ──
  checkout(plan_name, amount) {
    return apiFetch('/payment/checkout', {
      method: 'POST',
      body: JSON.stringify({ plan_name, amount })
    });
  },

  // ── Auth Admin ──
  adminLogin(username, password) {
    return apiFetch('/auth/admin/login', {
      method: 'POST',
      body: JSON.stringify({ username, password })
    });
  },

  // ── Admin Endpoints ──
  getAdminDashboard() {
    return apiFetch('/admin/dashboard');
  },
  getAdminOrders(page = 1, status = '') {
    return apiFetch(`/admin/orders?page=${page}&status=${status}`);
  },
  getAdminStudents(page = 1, search = '', plan = '') {
    return apiFetch(`/admin/students?page=${page}&search=${search}&plan=${plan}`);
  },
  getAdminQuestions(page = 1, subtes = '') {
    return apiFetch(`/admin/questions?page=${page}&subtes=${subtes}`);
  },
  createAdminQuestion(data) {
    return apiFetch('/admin/questions', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },
  updateAdminQuestion(id, data) {
    return apiFetch(`/admin/questions/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },
  deleteAdminQuestion(id) {
    return apiFetch(`/admin/questions/${id}`, {
      method: 'DELETE'
    });
  },
  getAdminMaterials(page = 1, subtes = '') {
    return apiFetch(`/admin/materials?page=${page}&subtes=${subtes}`);
  },
  createAdminMaterial(data) {
    return apiFetch('/admin/materials', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },
  updateAdminMaterial(id, data) {
    return apiFetch(`/admin/materials/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  },
  deleteAdminMaterial(id) {
    return apiFetch(`/admin/materials/${id}`, {
      method: 'DELETE'
    });
  },
  getAdminStaff() {
    return apiFetch(`/admin/staff`);
  },
  createAdminStaff(data) {
    return apiFetch('/admin/staff', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminStaff(id, data) {
    return apiFetch(`/admin/staff/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  },
  deleteAdminStaff(id) {
    return apiFetch(`/admin/staff/${id}`, { method: 'DELETE' });
  },
  getAdminPlans() {
    return apiFetch(`/admin/plans`);
  },
  createAdminPlan(data) {
    return apiFetch('/admin/plans', { method: 'POST', body: JSON.stringify(data) });
  },
  updateAdminPlan(id, data) {
    return apiFetch(`/admin/plans/${id}`, { method: 'PUT', body: JSON.stringify(data) });
  },
  deleteAdminPlan(id) {
    return apiFetch(`/admin/plans/${id}`, { method: 'DELETE' });
  }
};
