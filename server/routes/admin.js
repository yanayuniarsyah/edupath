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

// GET /api/admin/orders
router.get('/orders', (req, res) => {
  const { page = 1, limit = 20, status = '' } = req.query;
  let orders = db.orders.all();

  if (status) {
    orders = orders.filter(o => o.status === status);
  }

  // Join dengan nama siswa agar lebih informatif
  const enrichedOrders = orders.map(o => {
    const student = db.students.find(o.student_id);
    return {
      ...o,
      student_name: student ? student.name : 'Unknown Student',
      student_email: student ? student.email : ''
    };
  });

  enrichedOrders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  
  const offset = (page - 1) * limit;
  const paginated = enrichedOrders.slice(offset, offset + limit);

  res.json({ 
    success: true, 
    orders: paginated, 
    total: enrichedOrders.length, 
    page: Number(page), 
    totalPages: Math.ceil(enrichedOrders.length / limit) 
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
  const { subtes, bab, difficulty, irt_score, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, usage_type, cognitive_level } = req.body;
  if (!subtes || !question || !option_a || !option_b || !option_c || !option_d || !correct) {
    return res.status(400).json({ success: false, message: 'Field wajib tidak lengkap' });
  }

  const result = db.questions.insert({
    subtes, bab, difficulty: difficulty || 'medium', irt_score: irt_score || 5,
    question, option_a, option_b, option_c, option_d, option_e: option_e || null,
    correct, explanation: explanation || null, trick: trick || null,
    usage_type: usage_type || 'latihan',
    cognitive_level: cognitive_level || 'C3',
    is_active: true
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

// GET /api/admin/materials
router.get('/materials', (req, res) => {
  const { subtes, page = 1, limit = 20 } = req.query;
  let materials = db.materials.all();
  
  if (subtes) materials = materials.filter(m => m.subtes === subtes);

  materials.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  
  const offset = (page - 1) * limit;
  res.json({ success: true, materials: materials.slice(offset, offset + limit) });
});

// POST /api/admin/materials
router.post('/materials', (req, res) => {
  const { title, content, subtes, teacher_name } = req.body;
  if (!title || !content || !subtes) {
    return res.status(400).json({ success: false, message: 'Field wajib tidak lengkap' });
  }

  const result = db.materials.insert({
    title, content, subtes, teacher_name: teacher_name || '', is_active: true
  });

  res.status(201).json({ success: true, message: 'Materi ditambahkan', id: result.id });
});

// PUT /api/admin/materials/:id
router.put('/materials/:id', (req, res) => {
  db.materials.update(req.params.id, req.body);
  res.json({ success: true, message: 'Materi diperbarui' });
});

// DELETE /api/admin/materials/:id
router.delete('/materials/:id', (req, res) => {
  db.materials.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Materi dihapus' });
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

// ==========================================
// PLANS / PAKET BERLANGGANAN
// ==========================================
router.get('/plans', (req, res) => {
  const plans = db.plans.all();
  res.json({ success: true, plans });
});

router.post('/plans', (req, res) => {
  const { name, price, duration, features } = req.body;
  const result = db.plans.insert({ name, price: Number(price), duration: Number(duration), features, is_active: true });
  res.status(201).json({ success: true, id: result.id });
});

router.put('/plans/:id', (req, res) => {
  const { name, price, duration, features, is_active } = req.body;
  db.plans.update(req.params.id, { name, price: Number(price), duration: Number(duration), features, is_active });
  res.json({ success: true, message: 'Paket diperbarui' });
});

router.delete('/plans/:id', (req, res) => {
  db.plans.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Paket dinonaktifkan' });
});

// ==========================================
// STAFF / ADMIN / GURU
// ==========================================
router.get('/staff', (req, res) => {
  let staff = db.admins.all();
  staff = staff.map(s => {
    const { password, ...rest } = s;
    return rest;
  });
  res.json({ success: true, staff });
});

router.post('/staff', (req, res) => {
  const { username, name, role, password } = req.body;
  if (!username || !password || !name) {
    return res.status(400).json({ success: false, message: 'Field wajib tidak lengkap' });
  }
  if (db.admins.findOne(s => s.username === username)) {
    return res.status(400).json({ success: false, message: 'Username sudah digunakan' });
  }
  const result = db.admins.insert({
    username,
    name,
    role: role || 'teacher',
    password: bcrypt.hashSync(password, 10),
    is_active: true
  });
  res.status(201).json({ success: true, id: result.id });
});

router.put('/staff/:id', (req, res) => {
  const { username, name, role, password, is_active } = req.body;
  const updateData = { username, name, role };
  if (is_active !== undefined) updateData.is_active = is_active;
  if (password) {
    updateData.password = bcrypt.hashSync(password, 10);
  }
  db.admins.update(req.params.id, updateData);
  res.json({ success: true, message: 'Staff diperbarui' });
});

router.delete('/staff/:id', (req, res) => {
  db.admins.update(req.params.id, { is_active: false });
  res.json({ success: true, message: 'Staff dinonaktifkan' });
});

module.exports = router;
