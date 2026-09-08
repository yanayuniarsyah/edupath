// =====================================================
// EduPath Backend — server/routes/auth.js
// Login / Register (JSON DB version)
// =====================================================
const express = require('express');
const router  = express.Router();
const bcrypt  = require('bcryptjs');
const db      = require('../db/database');
const { generateToken, authStudent } = require('../middleware/auth');

// POST /api/auth/register
router.post('/register', (req, res) => {
  const { name, email, password, phone, school, target_ptn } = req.body;
  if (!name || !email || !password) return res.status(400).json({ success: false, message: 'Nama, email, dan password wajib diisi' });
  if (password.length < 6) return res.status(400).json({ success: false, message: 'Password minimal 6 karakter' });
  if (db.students.findOne(s => s.email === email)) return res.status(409).json({ success: false, message: 'Email sudah terdaftar' });

  const student = db.students.insert({
    name, email, password: bcrypt.hashSync(password, 10),
    phone: phone || '', school: school || '', target_ptn: target_ptn || 'UI',
    plan: 'free', streak: 0, coins: 0, total_score: 0, is_active: true,
  });
  const { password: _, ...safe } = student;
  const token = generateToken({ id: student.id, email, role: 'student' });
  res.status(201).json({ success: true, message: 'Registrasi berhasil!', token, user: safe });
});

// POST /api/auth/login
router.post('/login', (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) return res.status(400).json({ success: false, message: 'Email dan password wajib' });
  const student = db.students.findOne(s => s.email === email && s.is_active);
  if (!student || !bcrypt.compareSync(password, student.password)) return res.status(401).json({ success: false, message: 'Email atau password salah' });

  db.students.update(student.id, { last_login: new Date().toISOString() });
  const { password: _, ...safe } = student;
  const token = generateToken({ id: student.id, email, role: 'student' });
  res.json({ success: true, message: 'Login berhasil!', token, user: safe });
});

// POST /api/auth/admin/login
router.post('/admin/login', (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) return res.status(400).json({ success: false, message: 'Username dan password wajib' });
  const admin = db.admins.findOne(a => a.username === username);
  if (!admin || !bcrypt.compareSync(password, admin.password)) return res.status(401).json({ success: false, message: 'Username atau password salah' });

  const { password: _, ...safe } = admin;
  const token = generateToken({ id: admin.id, username, role: 'admin' }, '1d');
  res.json({ success: true, message: 'Login admin berhasil!', token, admin: safe });
});

// GET /api/auth/me
router.get('/me', authStudent, (req, res) => {
  const s = db.students.find(req.user.id);
  if (!s) return res.status(404).json({ success: false, message: 'Akun tidak ditemukan' });
  const { password: _, ...safe } = s;
  res.json({ success: true, user: safe });
});

// POST /api/auth/forgot-password
router.post('/forgot-password', (req, res) => {
  const { email } = req.body;
  if (!email) return res.status(400).json({ success: false, message: 'Email wajib diisi' });

  const student = db.students.findOne(s => s.email === email);
  if (!student) {
    return res.status(404).json({ success: false, message: 'Email tidak terdaftar' });
  }

  // Generate 6 digit token untuk simulasi (agar mudah diketik saat demo)
  const resetToken = Math.floor(100000 + Math.random() * 900000).toString();
  const expiry = Date.now() + 3600000; // 1 jam dari sekarang

  db.students.update(student.id, { reset_token: resetToken, reset_token_expiry: expiry });

  console.log(`\n[SIMULASI EMAIL] Permintaan reset password untuk ${email}. Token Anda: ${resetToken}\n`);
  
  // Dalam simulasi, token dikirim balik ke client agar mudah diuji
  res.json({ success: true, message: 'Token reset telah dikirim ke email Anda (Simulasi).', token_simulasi: resetToken });
});

// POST /api/auth/reset-password
router.post('/reset-password', (req, res) => {
  const { email, token, newPassword } = req.body;
  if (!email || !token || !newPassword) {
    return res.status(400).json({ success: false, message: 'Semua field wajib diisi' });
  }
  if (newPassword.length < 6) {
    return res.status(400).json({ success: false, message: 'Password minimal 6 karakter' });
  }

  const student = db.students.findOne(s => s.email === email && s.reset_token === token);
  if (!student) {
    return res.status(400).json({ success: false, message: 'Token tidak valid atau email salah' });
  }

  if (Date.now() > student.reset_token_expiry) {
    return res.status(400).json({ success: false, message: 'Token sudah kedaluwarsa' });
  }

  // Ubah password dan bersihkan token
  db.students.update(student.id, {
    password: bcrypt.hashSync(newPassword, 10),
    reset_token: null,
    reset_token_expiry: null
  });

  res.json({ success: true, message: 'Password berhasil diubah. Silakan login kembali.' });
});

// PUT /api/auth/profile
router.put('/profile', authStudent, (req, res) => {
  const { name, phone, school, target_ptn } = req.body;
  db.students.update(req.user.id, { name, phone, school, target_ptn });
  res.json({ success: true, message: 'Profil diperbarui' });
});

module.exports = router;
