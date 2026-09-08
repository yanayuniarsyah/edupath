// =====================================================
// EduPath Backend — server/routes/auth.js
// Login / Register (Prisma DB version)
// =====================================================
const express = require('express');
const router  = express.Router();
const bcrypt  = require('bcryptjs');
const prisma  = require('../prismaClient');
const { generateToken, authStudent } = require('../middleware/auth');

// POST /api/auth/register
router.post('/register', async (req, res) => {
  try {
    const { name, email, password, phone, school, target_ptn } = req.body;
    if (!name || !email || !password) return res.status(400).json({ success: false, message: 'Nama, email, dan password wajib diisi' });
    if (password.length < 6) return res.status(400).json({ success: false, message: 'Password minimal 6 karakter' });
    
    const existing = await prisma.student.findUnique({ where: { email } });
    if (existing) return res.status(409).json({ success: false, message: 'Email sudah terdaftar' });

    const hashedPassword = bcrypt.hashSync(password, 10);
    const student = await prisma.student.create({
      data: {
        name, email, password: hashedPassword,
        phone: phone || '', school: school || '', target_ptn: target_ptn || 'UI',
        plan: 'free', streak: 0, coins: 0, total_score: 0, is_active: true,
      }
    });

    const { password: _, ...safe } = student;
    const token = generateToken({ id: student.id, email, role: 'student' });
    res.status(201).json({ success: true, message: 'Registrasi berhasil!', token, user: safe });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Terjadi kesalahan server' });
  }
});

// POST /api/auth/login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) return res.status(400).json({ success: false, message: 'Email dan password wajib' });
    
    const student = await prisma.student.findUnique({ where: { email } });
    if (!student || !student.is_active || !bcrypt.compareSync(password, student.password)) {
      return res.status(401).json({ success: false, message: 'Email atau password salah' });
    }

    await prisma.student.update({
      where: { id: student.id },
      data: { last_login: new Date() }
    });

    const { password: _, ...safe } = student;
    const token = generateToken({ id: student.id, email, role: 'student' });
    res.json({ success: true, message: 'Login berhasil!', token, user: safe });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Terjadi kesalahan server' });
  }
});

// POST /api/auth/admin/login
router.post('/admin/login', async (req, res) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) return res.status(400).json({ success: false, message: 'Username dan password wajib' });
    
    const admin = await prisma.admin.findUnique({ where: { username } });
    if (!admin || !bcrypt.compareSync(password, admin.password)) {
      return res.status(401).json({ success: false, message: 'Username atau password salah' });
    }

    const { password: _, ...safe } = admin;
    const token = generateToken({ id: admin.id, username, role: 'admin' }, '1d');
    res.json({ success: true, message: 'Login admin berhasil!', token, admin: safe });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Terjadi kesalahan server' });
  }
});

// GET /api/auth/me
router.get('/me', authStudent, async (req, res) => {
  try {
    const s = await prisma.student.findUnique({ where: { id: req.user.id } });
    if (!s) return res.status(404).json({ success: false, message: 'Akun tidak ditemukan' });
    const { password: _, ...safe } = s;
    res.json({ success: true, user: safe });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Terjadi kesalahan server' });
  }
});

// PUT /api/auth/profile
router.put('/profile', authStudent, async (req, res) => {
  try {
    const { name, phone, school, target_ptn } = req.body;
    await prisma.student.update({
      where: { id: req.user.id },
      data: { name, phone, school, target_ptn }
    });
    res.json({ success: true, message: 'Profil diperbarui' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Terjadi kesalahan server' });
  }
});

module.exports = router;
