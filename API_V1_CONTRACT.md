# EduPath API V1 Contract (Foundation)

Dokumen ini mendefinisikan standar request/response dan daftar endpoint konseptual untuk EduPath B2C Web-first & Mobile-first SaaS Platform.

## 1. Global Standard

### Headers
- `Authorization`: `Bearer <token>` (Fallback untuk Mobile API, Web App menggunakan HttpOnly Cookie secara otomatis).
- `X-CSRF-Token`: Wajib dikirimkan pada semua request bermutasi (POST, PUT, DELETE, PATCH).
- `Content-Type`: `application/json`
- `Accept`: `application/json`

### Standard Response Format

**Success (200 OK, 201 Created)**
```json
{
  "success": true,
  "data": { ... } // Payload data (object atau array)
}
```

**Error (400, 401, 403, 404, 422, 429, 500)**
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE_CONSTANT",
    "message": "Human readable error message for the user"
  }
}
```

---

## 2. API Endpoints

### 2.1 AUTHENTICATION (`/api/v1/auth`)
- `POST /api/v1/auth/login`: Menerima email & password. Mengembalikan `ep_access_token` (Cookie) dan `csrf_token` (JSON response).
- `POST /api/v1/auth/register`: Menerima data pendaftaran (nama, email, password, dll). Mengatur cookie.
- `POST /api/v1/auth/logout`: Menghapus cookie `ep_access_token` & `ep_csrf_token`.
- `GET /api/v1/auth/me`: Mengembalikan data profile student yang sedang login beserta status active subscription dasar.
- `POST /api/v1/auth/refresh`: Menerima `refresh_token` untuk menerbitkan session cookie baru.

### 2.2 STUDENT PROFILE (`/api/v1/students`)
- `GET /api/v1/students/profile`: Mengambil profile detail (termasuk progress stats).
- `PUT /api/v1/students/profile`: Update data profil, target PTN, password, dll.

### 2.3 SUBSCRIPTION (`/api/v1/subscriptions`)
*Subscription adalah source of truth kepemilikan paket langganan student.*
- `GET /api/v1/subscriptions/active`: Mengembalikan data subscription yang berstatus 'active' dan belum 'expired'.
- `GET /api/v1/subscriptions/history`: Mengembalikan riwayat transaksi dan subscription lama.

### 2.4 ENTITLEMENT (`/api/v1/entitlements`)
*Entitlement adalah representasi fitur apa yang boleh diakses student saat ini.*
- `GET /api/v1/entitlements`: Mengembalikan daftar key fitur (e.g. `['tryout_unlimited', 'ai_tutor']`) yang saat ini dimiliki user secara valid.
- `GET /api/v1/entitlements/check?feature={feature_key}`: Menerima status HTTP 200 jika memiliki akses, atau 403 FORBIDDEN jika tidak.

### 2.5 PAYMENT (`/api/v1/payments`)
- `GET /api/v1/payments/plans`: Mendapatkan daftar paket berlangganan yang aktif beserta harganya.
- `POST /api/v1/payments/checkout`: 
  - Input: `{ "plan_id": "uuid" }` (Harga dihitung aman di sisi backend).
  - Output: `snap_token` (untuk Midtrans popup) dan `order_id`.
- `POST /api/v1/payments/webhook`: Menerima notifikasi dari Midtrans untuk mengupdate status Order -> Create Subscription -> Provision Entitlement. (Secured by Signature).

### 2.6 ASSESSMENT (Conceptual / Draft Phase)
- `/api/v1/assessments/spp`: Endpoints untuk Student Potential Path.
- `/api/v1/assessments/quiz`: Endpoints untuk mini-kuis harian.
- `/api/v1/assessments/tryout`: Endpoints untuk Tryout akbar / CBT.

---
*Catatan: API di atas adalah draft arsitektur V1. Saat ini, sistem masih menggunakan struktur legacy (seperti `api/auth.php`, `api/payment.php`) yang sedang direfaktor secara bertahap menuju struktur V1 ini.*
