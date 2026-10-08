# Dashboard API Documentation (Revenue-Based)

## Overview

Dashboard API versi baru yang menggunakan tabel `revenues` untuk performa lebih cepat. Tabel `revenues` berisi data yang sudah di-aggregate per hari, sehingga query dashboard jauh lebih efisien dibanding query langsung ke tabel transaksi.

---

## Base URL

```
/api/dashboard
```

---

## Authentication

Semua endpoint memerlukan:
- **JWT Token** di header `Authorization: Bearer {token}`
- **Client Secret** di header
- **App Version** di header

---

## Endpoints

### 1. Get Dashboard Summary

Mendapatkan ringkasan dashboard untuk semua OPD.

#### **Endpoint:**
```
GET /api/dashboard/revenue
```

#### **Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | No | Tanggal mulai filter (format: YYYY-MM-DD) |
| `end_date` | date | No | Tanggal akhir filter (format: YYYY-MM-DD) |

#### **Request Example:**

```bash
GET /api/dashboard/revenue?start_date=2024-01-01&end_date=2024-12-31
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### **Response Success (200):**

```json
{
  "status": 200,
  "message": "Successfully retrieved dashboard data from revenue table",
  "data": {
    "grand_total_transaksi": 1250,
    "grand_total_installment": 85,
    "pendapatan_per_opd": [
      {
        "opd_id": 1,
        "nama_opd": "Dinas Perhubungan",
        "total_pendapatan": 150000000.00
      },
      {
        "opd_id": 2,
        "nama_opd": "Dinas Pariwisata",
        "total_pendapatan": 85000000.00
      }
    ],
    "grand_total_pendapatan": 235000000.00,
    "pendapatan_per_retribusi": [
      {
        "retribusi_id": 1,
        "nama_retribusi": "Retribusi Jasa Umum",
        "total_pendapatan": 100000000.00,
        "total_transaksi": 500
      },
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "total_pendapatan": 85000000.00,
        "total_transaksi": 450
      },
      {
        "retribusi_id": 3,
        "nama_retribusi": "Retribusi Perizinan Tertentu",
        "total_pendapatan": 50000000.00,
        "total_transaksi": 385
      }
    ],
    "top_10_opd_by_revenue": [
      {
        "opd_id": 1,
        "nama_opd": "Dinas Perhubungan",
        "total_pendapatan": 150000000.00
      },
      {
        "opd_id": 2,
        "nama_opd": "Dinas Pariwisata",
        "total_pendapatan": 85000000.00
      }
    ],
    "revenue_trend": [
      {
        "tanggal": "2024-01-01",
        "total_pendapatan": 5000000.00,
        "total_transaksi": 25
      },
      {
        "tanggal": "2024-01-02",
        "total_pendapatan": 6500000.00,
        "total_transaksi": 32
      }
    ],
    "total_data_points": 365
  }
}
```

#### **Response Error (401):**

```json
{
  "status": 401,
  "message": "You're not authenticated"
}
```

#### **Response Error (500):**

```json
{
  "status": 500,
  "message": "Error retrieving dashboard data",
  "error": "Database connection failed"
}
```

---

### 2. Get Dashboard by OPD

Mendapatkan ringkasan dashboard untuk OPD tertentu.

#### **Endpoint:**
```
GET /api/dashboard/revenue/opd/{opdId}
```

#### **Path Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `opdId` | integer | Yes | ID dari OPD yang ingin ditampilkan |

#### **Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | No | Tanggal mulai filter (format: YYYY-MM-DD) |
| `end_date` | date | No | Tanggal akhir filter (format: YYYY-MM-DD) |

#### **Request Example:**

```bash
GET /api/dashboard/revenue/opd/5?start_date=2024-01-01&end_date=2024-03-31
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### **Response Success (200):**

```json
{
  "status": 200,
  "message": "Successfully retrieved dashboard data for OPD from revenue table",
  "data": {
    "opd_info": {
      "id": 5,
      "nama_opd": "Dinas Perhubungan"
    },
    "grand_total_transaksi": 450,
    "grand_total_installment": 25,
    "total_pendapatan": 75000000.00,
    "pendapatan_per_retribusi": [
      {
        "retribusi_id": 1,
        "nama_retribusi": "Retribusi Jasa Umum",
        "total_pendapatan": 50000000.00,
        "total_transaksi": 300
      },
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "total_pendapatan": 25000000.00,
        "total_transaksi": 175
      }
    ],
    "revenue_trend": [
      {
        "tanggal": "2024-01-01",
        "total_pendapatan": 2500000.00,
        "total_transaksi": 15
      },
      {
        "tanggal": "2024-01-02",
        "total_pendapatan": 3200000.00,
        "total_transaksi": 18
      }
    ]
  }
}
```

#### **Response Error (404):**

```json
{
  "status": 404,
  "message": "OPD not found"
}
```

#### **Response Error (401):**

```json
{
  "status": 401,
  "message": "You're not authenticated"
}
```

---

## Data Structure

### Revenue Table Schema

```sql
CREATE TABLE `revenues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `master_opd_id` bigint NOT NULL,
  `master_retribusi_id` bigint NOT NULL,
  `total_transactions` int NOT NULL DEFAULT '0',
  `total_revenue` decimal(18,2) NOT NULL DEFAULT '0.00',
  `is_installment` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_combination` (`date`,`master_opd_id`,`master_retribusi_id`,`is_installment`)
);
```

### Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `date` | date | Tanggal transaksi |
| `master_opd_id` | bigint | ID OPD (Foreign Key ke `master_opd`) |
| `master_retribusi_id` | bigint | ID Tipe Retribusi (Foreign Key ke `master_retribusi`) |
| `total_transactions` | int | Jumlah transaksi pada tanggal tersebut |
| `total_revenue` | decimal(18,2) | Total pendapatan pada tanggal tersebut |
| `is_installment` | tinyint | 0 = Regular, 1 = Installment |

---

## Performance Comparison

### Old Dashboard (TransaksiController)

**Route:** `GET /api/transaksi/dashboard`

**Query Count:** ~10-15 queries dengan multiple JOINs
- Query ke `transactions`
- Query ke `transaction_objects`
- Query ke `installment_transactions`
- Query ke `master_objek`
- Query ke `master_opd`

**Average Response Time:** 2-5 seconds (tergantung jumlah data)

### New Dashboard (DashboardController)

**Route:** `GET /api/dashboard/revenue`

**Query Count:** ~4-6 queries sederhana
- Query langsung ke `revenues` (sudah di-aggregate)
- Minimal JOINs

**Average Response Time:** 200-500ms

**Performance Improvement:** **4-10x lebih cepat** ⚡

---

## Usage Examples

### Example 1: Get Dashboard for Current Month

```javascript
// JavaScript/Flutter
const today = new Date();
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

const response = await fetch(
  `/api/dashboard/revenue?start_date=${firstDay.toISOString().split('T')[0]}&end_date=${lastDay.toISOString().split('T')[0]}`,
  {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
);

const data = await response.json();
console.log('Total Pendapatan Bulan Ini:', data.data.grand_total_pendapatan);
```

### Example 2: Get Dashboard for Specific OPD (Year to Date)

```javascript
const year = 2024;
const response = await fetch(
  `/api/dashboard/revenue/opd/5?start_date=${year}-01-01&end_date=${year}-12-31`,
  {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
);

const data = await response.json();
console.log('Pendapatan OPD:', data.data.total_pendapatan);
console.log('Breakdown:', data.data.pendapatan_per_retribusi);
```

### Example 3: Get Revenue Trend for Chart

```javascript
const response = await fetch(
  `/api/dashboard/revenue?start_date=2024-01-01&end_date=2024-12-31`,
  {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
);

const data = await response.json();
const chartData = data.data.revenue_trend.map(item => ({
  x: item.tanggal,
  y: item.total_pendapatan
}));

// Use chartData for Chart.js, ApexCharts, etc.
```

---

## Migration Guide

### From Old Dashboard to New Dashboard

**Old Code:**
```dart
// Flutter
final response = await http.get(
  Uri.parse('$baseUrl/api/transaksi/dashboard'),
  headers: {'Authorization': 'Bearer $token'}
);
```

**New Code:**
```dart
// Flutter
final response = await http.get(
  Uri.parse('$baseUrl/api/dashboard/revenue'),
  headers: {'Authorization': 'Bearer $token'}
);
```

### Key Differences

| Old Dashboard | New Dashboard |
|---------------|---------------|
| `total_transaksi` (per status) | `grand_total_transaksi` (total saja) |
| `total_installment` (per status) | `grand_total_installment` (total saja) |
| `top_10_objek` | Tidak ada (fokus ke OPD) |
| - | `pendapatan_per_retribusi` (baru) |
| - | `top_10_opd_by_revenue` (baru) |
| - | `revenue_trend` (baru) |

---

## Important Notes

### ⚠️ Data Synchronization

Tabel `revenues` harus di-sync secara berkala dari tabel transaksi. Pastikan:

1. **Daily Sync Job** berjalan setiap hari untuk update data kemarin
2. **Backfill** dilakukan untuk data historis
3. **Real-time sync** (optional) jika butuh data real-time

### 📊 Data Accuracy

- Data di `revenues` adalah snapshot harian
- Untuk data real-time, gunakan endpoint lama (`/api/transaksi/dashboard`)
- Untuk reporting dan analytics, gunakan endpoint baru (`/api/dashboard/revenue`)

### 🔄 Backward Compatibility

Endpoint lama masih tersedia dan tidak akan dihapus:
- `GET /api/transaksi/dashboard` - Masih berfungsi
- `GET /api/transaksi/dashboard/opd/{opdId}` - Masih berfungsi

---

## Support

Untuk pertanyaan atau issue, hubungi tim development.

**Last Updated:** March 10, 2026
