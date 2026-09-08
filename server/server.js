// =====================================================
// EduPath Backend — server/server.js
// Entry point — Express API Server
// =====================================================
const express = require('express');
const cors    = require('cors');
const path    = require('path');
const fs      = require('fs');
const helmet  = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

// (Deprecated) JSON DB - Akan dihapus perlahan setelah semua route migrasi ke Prisma
const dataDir = path.join(__dirname, 'data');
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir, { recursive: true });
require('./db/database');
const db = require('./db/database');

const app  = express();
const PORT = process.env.PORT || 3001;

// Keamanan: Set security HTTP headers
app.use(helmet());

// Keamanan: Rate limiting (Maks 100 request per 15 menit dari IP yang sama)
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, 
  max: 100, 
  message: { success: false, message: 'Terlalu banyak request dari IP ini, coba lagi nanti.' }
});
app.use('/api', limiter);

app.use(cors({
  origin: [
    'http://localhost:5173', 'http://localhost:5174', 'http://localhost:5175',
    'http://localhost:5176', 'http://localhost:5177', 'https://edupath.elyana.biz.id',
  ],
  credentials: true,
}));

app.use(express.json({ limit: '5mb' }));
app.use(express.urlencoded({ extended: true }));

const distPath = path.join(__dirname, '..', 'dist');
if (fs.existsSync(distPath)) {
  app.use(express.static(distPath));
}

app.use('/api/auth',     require('./routes/auth'));
app.use('/api/progress', require('./routes/progress'));
app.use('/api/admin',    require('./routes/admin'));
app.use('/api/payment',  require('./routes/payment'));

// GET /api/plans
app.get('/api/plans', (req, res) => {
  const plans = db.plans.where(p => p.is_active);
  plans.sort((a, b) => a.price - b.price);
  res.json({ success: true, plans });
});

// GET /api/announcements
app.get('/api/announcements', (req, res) => {
  let list = db.announcements.where(a => a.is_active);
  list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  res.json({ success: true, announcements: list.slice(0, 5) });
});

// GET /api/questions/random
app.get('/api/questions/random', (req, res) => {
  const subtes = req.query.subtes || null;
  const n      = Math.min(parseInt(req.query.n) || 5, 20);
  
  let q = db.questions.where(x => x.is_active);
  if (subtes) q = q.filter(x => x.subtes === subtes);

  q.sort(() => 0.5 - Math.random());
  q = q.slice(0, n);

  const sanitized = q.map(({ correct, ...rest }) => rest);
  res.json({ success: true, questions: sanitized });
});

app.get('/api/health', (req, res) => {
  res.json({ success: true, status: 'OK', timestamp: new Date().toISOString(), version: '1.0.0' });
});

app.get('*', (req, res) => {
  if (req.path.startsWith('/api')) {
    return res.status(404).json({ success: false, message: 'Endpoint tidak ditemukan' });
  }
  const indexPath = path.join(distPath, 'index.html');
  if (fs.existsSync(indexPath)) {
    res.sendFile(indexPath);
  } else {
    res.json({ success: true, message: 'EduPath API berjalan. Jalankan npm run build untuk frontend.' });
  }
});

app.listen(PORT, () => {
  console.log(`🚀 EduPath Backend API Running at http://localhost:${PORT}`);
});

module.exports = app;
