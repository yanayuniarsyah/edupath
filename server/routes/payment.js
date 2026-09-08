// =====================================================
// EduPath Backend — server/routes/payment.js
// Midtrans Payment Integration (Native Fetch)
// =====================================================
const express = require('express');
const router  = express.Router();
const db      = require('../db/database');
const { authStudent } = require('../middleware/auth');
const crypto = require('crypto');

// URL Midtrans (Gunakan Sandbox untuk Testing, Production untuk Live)
const MIDTRANS_IS_PRODUCTION = process.env.MIDTRANS_IS_PRODUCTION === 'true';
const MIDTRANS_API_URL = MIDTRANS_IS_PRODUCTION 
  ? 'https://app.midtrans.com/snap/v1/transactions' 
  : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
const SERVER_KEY = process.env.MIDTRANS_SERVER_KEY || '';

// POST /api/payment/checkout
// Endpoint ini dipanggil oleh frontend saat user klik "Beli"
router.post('/checkout', authStudent, async (req, res) => {
  try {
    const { plan_name, amount } = req.body;
    if (!plan_name || !amount) {
      return res.status(400).json({ success: false, message: 'plan_name dan amount wajib diisi' });
    }

    // Ambil data siswa
    const student = db.students.find(req.user.id);
    if (!student) {
      return res.status(404).json({ success: false, message: 'Siswa tidak ditemukan' });
    }

    // Buat Order ID unik
    const order_id = `EDUPATH-${Date.now()}-${Math.floor(Math.random() * 1000)}`;

    // Persiapkan payload untuk Midtrans
    const payload = {
      transaction_details: {
        order_id: order_id,
        gross_amount: amount
      },
      customer_details: {
        first_name: student.name,
        email: student.email,
        phone: student.phone || '080000000000'
      },
      item_details: [
        {
          id: `PLAN-${plan_name.toUpperCase()}`,
          price: amount,
          quantity: 1,
          name: `Paket Belajar ${plan_name}`
        }
      ]
    };

    // Encode Server Key ke Base64 untuk Auth Header
    const authString = Buffer.from(`${SERVER_KEY}:`).toString('base64');

    // Tembak API Midtrans menggunakan native fetch Node.js
    const response = await fetch(MIDTRANS_API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Basic ${authString}`
      },
      body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (response.ok && data.token) {
      // Simpan riwayat pesanan ke database (status pending)
      db.orders.insert({
        order_id,
        student_id: student.id,
        plan_name,
        amount,
        status: 'pending',
        snap_token: data.token
      });

      return res.json({ success: true, token: data.token, order_id });
    } else {
      console.error('Midtrans Error:', data);
      return res.status(500).json({ success: false, message: 'Gagal menghubungi payment gateway', error: data });
    }
  } catch (error) {
    console.error('Checkout Error:', error);
    res.status(500).json({ success: false, message: 'Terjadi kesalahan internal server' });
  }
});

// POST /api/payment/webhook
// Midtrans akan menembak URL ini secara otomatis jika pembayaran berhasil/gagal
router.post('/webhook', (req, res) => {
  const { order_id, status_code, gross_amount, signature_key, transaction_status } = req.body;

  // Verifikasi Signature Key untuk memastikan request asli dari Midtrans
  const hash = crypto.createHash('sha512');
  hash.update(`${order_id}${status_code}${gross_amount}${SERVER_KEY}`);
  const expectedSignature = hash.digest('hex');

  if (signature_key !== expectedSignature) {
    return res.status(403).json({ success: false, message: 'Invalid Signature' });
  }

  // Cari pesanan di DB
  const order = db.orders.findOne(o => o.order_id === order_id);
  if (!order) {
    return res.status(404).json({ success: false, message: 'Order tidak ditemukan' });
  }

  // Tentukan status pesanan
  let statusStr = 'pending';
  if (transaction_status === 'capture' || transaction_status === 'settlement') {
    statusStr = 'paid';
  } else if (transaction_status === 'cancel' || transaction_status === 'deny' || transaction_status === 'expire') {
    statusStr = 'failed';
  }

  // Update status pesanan di database
  db.orders.update(order.id, { status: statusStr });

  // Jika sukses terbayar, berikan hak akses Paket Pro ke siswa
  if (statusStr === 'paid') {
    db.students.update(order.student_id, { plan: 'pro' });
    console.log(`[PAYMENT] Sukses! Akun ${order.student_id} di-upgrade ke PRO.`);
  }

  // Harus merespons 200 OK ke Midtrans
  res.status(200).json({ success: true, message: 'Webhook diterima' });
});

module.exports = router;
