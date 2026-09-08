// =====================================================
// EduPath Backend — server/routes/admin.js
// Dashboard Admin (JSON DB)
// =====================================================
const express = require('express');
const router  = express.Router();
const bcrypt  = require('bcryptjs');
const db      = require('../db/database');
const { authAdmin } = require('../middleware/auth');

router.use(authAdmin);

// GET /api/admin/dashboard
router.get('/dashboard', (req, res) => {
  const allStudents = db.students.all();
  const totalStudents = allStudents.length;
  
  const sevenDaysAgo = new Date();
  sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
  
  const activeStudents = allStudents.filter(s => new Date(s.last_login) >= sevenDaysAgo).length;
  const paidStudents   = allStudents.filter(s => s.plan !== 'free').length;
  const totalQuizzes   = db.quizResults.count();
  const avgScore       = db.students.avg('total_score');
  
  const totalRevenue = db.orders.all()
    .filter(o => o.status === 'paid')
    .reduce((sum, o) => sum + (Number(o.amount) || 0), 0);

  const recentStudents = [...allStudents].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 10);

  res.json({
    success: true,
    stats: { totalStudents, activeStudents, paidStudents, totalQuizzes, avgScore: avgScore.toFixed(1), totalRevenue },
    recentStudents,
  });
});

// GET /api/admin/students
router.get('/students', (req, res) => {
  const { search = '', plan = '', page = 1, limit = 20 } = req.query;
  let students = db.students.all();

  if (search) {
    const s = search.toLowerCase();
    students = students.filter(x => x.name.toLowerCase().includes(s) || x.email.toLowerCase().includes(s));
  }
  if (plan) {
    students = students.filter(x => x.plan === plan);
  }

  students.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  
  const offset = (page - 1) * limit;
  const paginated = students.slice(offset, offset + limit);

  res.json({ success: true, students: paginated, total: students.length, page: Number(page), totalPages: Math.ceil(students.length / limit) });
});

// GET /api/admin/students/:id
router.get('/students/:id', (req, res) => {
  const student = db.students.find(req.params.id);
  if (!student) return res.status(404).json({ success: false, message: 'Siswa tidak ditemukan' });

  const progress    = db.progress.where(p => p.student_id === req.params.id);
  const quizHistory = db.quizResults.where(q => q.student_id === req.params.id).sort((a,b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 20);

  res.json({ success: true, student, progress, quizHistory });
});

// PUT /api/admin/students/:id
router.put('/students/:id', (req, res) => {
  db.students.update(req.params.id, req.body);
  res.json({ success: true, message: 'Data siswa diperbarui' });
});

// DELETE /api/admin/students/:id
router.delete('/students/:id', (req, res) => {
  db.students.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Siswa dinonaktifkan' });
});

// GET /api/admin/questions
router.get('/questions', (req, res) => {
  const { subtes, difficulty, page = 1, limit = 20 } = req.query;
  let questions = db.questions.all();
  
  if (subtes) questions = questions.filter(q => q.subtes === subtes);
  if (difficulty) questions = questions.filter(q => q.difficulty === difficulty);

  questions.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  
  const offset = (page - 1) * limit;
  res.json({ success: true, questions: questions.slice(offset, offset + limit) });
});

// POST /api/admin/questions
router.post('/questions', (req, res) => {
  const { subtes, bab, difficulty, irt_score, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick } = req.body;
  if (!subtes || !question || !option_a || !option_b || !option_c || !option_d || !correct) {
    return res.status(400).json({ success: false, message: 'Field wajib tidak lengkap' });
  }

  const result = db.questions.insert({
    subtes, bab, difficulty: difficulty || 'medium', irt_score: irt_score || 5,
    question, option_a, option_b, option_c, option_d, option_e: option_e || null,
    correct, explanation: explanation || null, trick: trick || null, is_active: true
  });

  res.status(201).json({ success: true, message: 'Soal ditambahkan', id: result.id });
});

// PUT /api/admin/questions/:id
router.put('/questions/:id', (req, res) => {
  db.questions.update(req.params.id, req.body);
  res.json({ success: true, message: 'Soal diperbarui' });
});

// DELETE /api/admin/questions/:id
router.delete('/questions/:id', (req, res) => {
  db.questions.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Soal dihapus' });
});

// GET /api/admin/announcements
router.get('/announcements', (req, res) => {
  let list = db.announcements.all();
  list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  res.json({ success: true, announcements: list });
});

// POST /api/admin/announcements
router.post('/announcements', (req, res) => {
  const { title, content, type } = req.body;
  const result = db.announcements.insert({ title, content, type: type || 'info', is_active: true });
  res.status(201).json({ success: true, id: result.id });
});

// DELETE /api/admin/announcements/:id
router.delete('/announcements/:id', (req, res) => {
  db.announcements.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Pengumuman dihapus' });
});

// POST /api/admin/reset-password/:id
router.post('/reset-password/:id', (req, res) => {
  const newPass = req.body.new_password || 'edupath123';
  db.students.update(req.params.id, { password: bcrypt.hashSync(newPass, 10) });
  res.json({ success: true, message: `Password direset ke: ${newPass}` });
});

module.exports = router;
