// =====================================================
// EduPath Backend — server/routes/progress.js
// Progress Belajar & Kuis (JSON DB)
// =====================================================
const express = require('express');
const router  = express.Router();
const db      = require('../db/database');
const { authStudent } = require('../middleware/auth');

router.use(authStudent);

// GET /api/progress
router.get('/', (req, res) => {
  const progress = db.progress.where(p => p.student_id === req.user.id);
  progress.sort((a, b) => new Date(b.last_study) - new Date(a.last_study));
  res.json({ success: true, progress });
});

// POST /api/progress
router.post('/', (req, res) => {
  const { subtes, bab, score, mastery } = req.body;
  if (!subtes || !bab) return res.status(400).json({ success: false, message: 'subtes dan bab wajib' });

  const prog = db.progress.upsert(
    p => p.student_id === req.user.id && p.subtes === subtes && p.bab === bab,
    { student_id: req.user.id, subtes, bab, score: score ?? 0, mastery: mastery ?? 0, last_study: new Date().toISOString() }
  );
  
  // Kalau upsert (insert), kita perlu set attempts manual kalau mau, tapi cukup sederhana dulu.

  // Update total score rata-rata
  const avg = db.progress.avg('score', p => p.student_id === req.user.id);
  db.students.update(req.user.id, { total_score: avg });

  res.json({ success: true, message: 'Progress tersimpan' });
});

// GET /api/progress/summary
router.get('/summary', (req, res) => {
  const myProgress = db.progress.where(p => p.student_id === req.user.id);
  const summaryMap = {};
  
  for (const p of myProgress) {
    if (!summaryMap[p.subtes]) {
      summaryMap[p.subtes] = { subtes: p.subtes, total_bab: 0, sum_score: 0, mastered_bab: 0, last_study: p.last_study };
    }
    summaryMap[p.subtes].total_bab++;
    summaryMap[p.subtes].sum_score += Number(p.score) || 0;
    if (p.mastery >= 2) summaryMap[p.subtes].mastered_bab++;
    if (new Date(p.last_study) > new Date(summaryMap[p.subtes].last_study)) summaryMap[p.subtes].last_study = p.last_study;
  }

  const summary = Object.values(summaryMap).map(s => ({
    ...s, avg_score: s.total_bab ? (s.sum_score / s.total_bab) : 0
  }));

  res.json({ success: true, summary });
});

// POST /api/progress/quiz
router.post('/quiz', (req, res) => {
  const { quiz_type, subtes, score, correct, total, duration_sec, answers } = req.body;
  if (!quiz_type || score === undefined) return res.status(400).json({ success: false, message: 'quiz_type dan score wajib' });

  const result = db.quizResults.insert({
    student_id: req.user.id, quiz_type, subtes: subtes || null,
    score, correct: correct || 0, total: total || 0, duration_sec: duration_sec || 0, answers
  });

  const student = db.students.find(req.user.id);
  if (student) {
    db.students.update(req.user.id, { 
      streak: (student.streak || 0) + 1, 
      coins: (student.coins || 0) + 10 + (correct * 2) 
    });
  }

  res.status(201).json({ success: true, message: 'Hasil kuis tersimpan', id: result.id });
});

// GET /api/progress/quiz-history
router.get('/quiz-history', (req, res) => {
  let history = db.quizResults.where(q => q.student_id === req.user.id);
  history.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  res.json({ success: true, history: history.slice(0, 20) });
});

module.exports = router;
