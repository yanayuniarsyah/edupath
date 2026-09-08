// =====================================================
// EduPath Backend — server/db/database.js
// JSON File Database (tanpa native module)
// =====================================================
const fs   = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');

const DATA_DIR = path.join(__dirname, '../data');
if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });

// ─────────────────────────────────────────────────
// Helper: Baca/tulis JSON file
// ─────────────────────────────────────────────────
function readDB(name) {
  const filePath = path.join(DATA_DIR, `${name}.json`);
  if (!fs.existsSync(filePath)) return [];
  try { return JSON.parse(fs.readFileSync(filePath, 'utf8')); }
  catch { return []; }
}

function writeDB(name, data) {
  fs.writeFileSync(path.join(DATA_DIR, `${name}.json`), JSON.stringify(data, null, 2));
}

function now() {
  return new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' });
}

// ─────────────────────────────────────────────────
// DB Model — Factory untuk setiap tabel
// ─────────────────────────────────────────────────
class Table {
  constructor(name) {
    this.name = name;
  }

  all()        { return readDB(this.name); }
  find(id)     { return this.all().find(r => r.id === id); }
  where(fn)    { return this.all().filter(fn); }
  findOne(fn)  { return this.all().find(fn); }

  insert(data) {
    const rows = this.all();
    const record = { id: uuidv4(), created_at: now(), ...data };
    rows.push(record);
    writeDB(this.name, rows);
    return record;
  }

  update(id, data) {
    const rows = this.all();
    const idx  = rows.findIndex(r => r.id === id);
    if (idx === -1) return null;
    rows[idx] = { ...rows[idx], ...data, updated_at: now() };
    writeDB(this.name, rows);
    return rows[idx];
  }

  upsert(matchFn, data) {
    const rows = this.all();
    const idx  = rows.findIndex(matchFn);
    if (idx !== -1) {
      rows[idx] = { ...rows[idx], ...data, updated_at: now() };
      writeDB(this.name, rows);
      return rows[idx];
    }
    return this.insert(data);
  }

  delete(id) {
    const rows = this.all().filter(r => r.id !== id);
    writeDB(this.name, rows);
  }

  count(fn) {
    if (fn) return this.all().filter(fn).length;
    return this.all().length;
  }

  avg(field, fn) {
    const rows = fn ? this.all().filter(fn) : this.all();
    if (!rows.length) return 0;
    return rows.reduce((s, r) => s + (Number(r[field]) || 0), 0) / rows.length;
  }
}

// ─────────────────────────────────────────────────
// Tabel-tabel database
// ─────────────────────────────────────────────────
const db = {
  admins:       new Table('admins'),
  students:     new Table('students'),
  progress:     new Table('progress'),
  quizResults:  new Table('quiz_results'),
  questions:    new Table('questions'),
  materials:    new Table('materials'),
  plans:        new Table('plans'),
  orders:       new Table('orders'),
  announcements:new Table('announcements'),
};

// ─────────────────────────────────────────────────
// SEED DATA AWAL
// ─────────────────────────────────────────────────
function seed() {
  // Admin default
  if (!db.admins.findOne(a => a.username === 'admin')) {
    db.admins.insert({
      username: 'admin',
      password: bcrypt.hashSync('admin123', 10),
      name: 'Administrator EduPath',
    });
    console.log('[DB] ✅ Admin dibuat: admin / admin123');
  }

  // Siswa demo
  if (!db.students.findOne(s => s.email === 'demo@edupath.id')) {
    db.students.insert({
      name: 'Siswa Demo',
      email: 'demo@edupath.id',
      password: bcrypt.hashSync('demo123', 10),
      phone: '081234567890',
      school: 'SMA Negeri 1 Jakarta',
      target_ptn: 'UI',
      plan: 'free',
      streak: 7,
      coins: 350,
      total_score: 542.5,
      is_active: true,
    });
    console.log('[DB] ✅ Siswa demo: demo@edupath.id / demo123');
  }

  // Paket harga
  if (!db.plans.all().length) {
    db.plans.insert({ name: 'Free', price: 0, duration: 30, is_active: true, features: ['Akses 3 subtes dasar', 'Student Potential Path', 'Mini Kuis 5 soal/hari'] });
    db.plans.insert({ name: 'Pro', price: 149000, duration: 30, is_active: true, features: ['Semua subtes UTBK', 'AI Adaptive Path', 'Tryout tak terbatas', 'Laporan analitik detail'] });
    db.plans.insert({ name: 'Pro Annual', price: 990000, duration: 365, is_active: true, features: ['Semua fitur Pro', 'Hemat 45%', 'Konsultasi 1-on-1 bulanan'] });
    console.log('[DB] ✅ Paket harga dibuat');
  }

  // Pengumuman contoh
  if (!db.announcements.all().length) {
    db.announcements.insert({ title: 'Selamat datang di EduPath!', content: 'Platform belajar adaptif SNBT terbaik. Mulai journey-mu sekarang!', type: 'success', is_active: true });
  }
}

seed();

module.exports = db;
