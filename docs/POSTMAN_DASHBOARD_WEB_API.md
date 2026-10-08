# 🌐 Dashboard Web API Documentation - E-Retribusi (v2.0)

**Base URL:** `https://saibara.lampungprov.go.id/api`  
**Authentication:** X-API-TOKEN Header (EXTERNAL_API_TOKEN)  
**Purpose:** API untuk integrasi dashboard ke web eksternal  
**Type:** Public API (No Login Required)  
**Version:** 2.0 (Lightweight Endpoints)  
**Last Updated:** April 17, 2026

---

## ⚡ What's New in v2.0

**NEW: Lightweight Dashboard Components!**

Sekarang tersedia 4 endpoint terpisah yang lebih ringan dan cepat:

| Endpoint               | Response Time | Size  | Improvement   |
| ---------------------- | ------------- | ----- | ------------- |
| `/summary`             | ~200-300ms    | ~1KB  | ⚡ 90% faster |
| `/retribusi-breakdown` | ~150-250ms    | ~2KB  | ⚡ 95% faster |
| `/opd-performance`     | ~300-500ms    | ~10KB | ⚡ 80% faster |
| `/realisasi-vs-target` | ~500-800ms    | ~15KB | ⚡ 70% faster |

**Benefits:**

- ✅ 60% faster overall response time
- ✅ 94% smaller payload
- ✅ Load only what you need
- ✅ Better for mobile devices
- ✅ Progressive loading support

---

## 🔐 Authentication

API ini menggunakan **EXTERNAL_API_TOKEN** authentication. Semua endpoint memerlukan token API yang valid di header.

### API Token

Token API disimpan di environment variable `EXTERNAL_API_TOKEN` di server.

**Token Value (dari .env):**

```
EXTERNAL_API_TOKEN="GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA"
```

### Using Token

Include token in `X-API-TOKEN` header for all requests:

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
```

**✅ TIDAK PERLU LOGIN** - Langsung gunakan token di header!

---

## 📋 Table of Contents

### ⚡ Lightweight Endpoints (NEW - Recommended)

1. [Get Summary Only](#1-get-summary-only-new)
2. [Get Retribusi Breakdown](#2-get-retribusi-breakdown-new)
3. [Get OPD Performance](#3-get-opd-performance-new)
4. [Get Realisasi vs Target](#4-get-realisasi-vs-target-new)

### 📊 Full Dashboard (Deprecated)

5. [Dashboard Data (DEPRECATED)](#5-dashboard-data-deprecated)

### 📑 Master Data Lists

6. [OPD List](#6-opd-list)
7. [Retribusi List](#7-retribusi-list)
8. [Lokasi List](#8-lokasi-list)
9. [Objek List](#9-objek-list)

### 📈 Detail Pages

10. [Performance Detail](#10-performance-detail)
11. [Realisasi Detail](#11-realisasi-detail)

### 🔍 Drill-down

12. [Realisasi Lokasi by OPD](#12-realisasi-lokasi-by-opd)
13. [Realisasi Retribusi by Lokasi](#13-realisasi-retribusi-by-lokasi)

### 🛠️ Helpers

14. [Get Lokasi by OPD (Helper)](#14-get-lokasi-by-opd-helper)
15. [Get Rincian by Retribusi (Helper)](#15-get-rincian-by-retribusi-helper)

---

## ⚡ LIGHTWEIGHT ENDPOINTS (NEW)

---

## 1. Get Summary Only (NEW)

⚡ **LIGHTWEIGHT** - Mendapatkan summary metrics saja (total transaksi & pendapatan).

**Performance:**

- Queries: 3
- Response size: ~1KB
- Response time: ~200-300ms

**Use case:** Summary cards, quick metrics, mobile app summary screen

### Endpoint

```
GET /api/dashboard-web/summary
```

### Headers

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Query Parameters

| Parameter    | Type   | Required | Default         | Description             |
| ------------ | ------ | -------- | --------------- | ----------------------- |
| `start_date` | string | No       | Awal bulan ini  | Start date (YYYY-MM-DD) |
| `end_date`   | string | No       | Akhir bulan ini | End date (YYYY-MM-DD)   |

### Example Request

```bash
curl -X GET "https://saibara.lampungprov.go.id/api/dashboard-web/summary?start_date=2026-01-01&end_date=2026-03-31" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### Example Response

```json
{
    "status": 200,
    "message": "Successfully retrieved summary data",
    "data": {
        "total_transaksi_regular": 1200,
        "total_transaksi_installment": 350,
        "grand_total_transaksi": 1550,
        "grand_total_pendapatan": "2500000000.00",
        "filters": {
            "start_date": "2026-01-01",
            "end_date": "2026-03-31"
        }
    }
}
```

---

## 2. Get Retribusi Breakdown (NEW)

⚡ **LIGHTWEIGHT** - Mendapatkan breakdown per jenis retribusi saja.

**Performance:**

- Queries: 1
- Response size: ~2KB
- Response time: ~150-250ms

**Use case:** Pie chart retribusi, retribusi breakdown table, statistics by type

### Endpoint

```
GET /api/dashboard-web/retribusi-breakdown
```

### Headers

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Query Parameters

| Parameter    | Type   | Required | Default         | Description             |
| ------------ | ------ | -------- | --------------- | ----------------------- |
| `start_date` | string | No       | Awal bulan ini  | Start date (YYYY-MM-DD) |
| `end_date`   | string | No       | Akhir bulan ini | End date (YYYY-MM-DD)   |

### Example Request

```bash
curl -X GET "https://saibara.lampungprov.go.id/api/dashboard-web/retribusi-breakdown?start_date=2026-01-01&end_date=2026-03-31" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### Example Response

```json
{
    "status": 200,
    "message": "Successfully retrieved retribusi breakdown",
    "data": [
        {
            "retribusi_id": 1,
            "nama_retribusi": "Retribusi Jasa Umum",
            "total_transaksi": 450,
            "total_pendapatan": "800000000.00"
        },
        {
            "retribusi_id": 2,
            "nama_retribusi": "Retribusi Jasa Usaha",
            "total_transaksi": 850,
            "total_pendapatan": "1500000000.00"
        },
        {
            "retribusi_id": 3,
            "nama_retribusi": "Retribusi Perizinan Tertentu",
            "total_transaksi": 250,
            "total_pendapatan": "200000000.00"
        }
    ],
    "filters": {
        "start_date": "2026-01-01",
        "end_date": "2026-03-31"
    }
}
```

---

## 3. Get OPD Performance (NEW)

⚡ **LIGHTWEIGHT** - Mendapatkan performa OPD vs target saja.

**Performance:**

- Queries: 2
- Response size: ~10KB
- Response time: ~300-500ms

**Use case:** OPD performance table, progress bars per OPD, achievement ranking

### Endpoint

```
GET /api/dashboard-web/opd-performance
```

### Headers

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Query Parameters

| Parameter    | Type   | Required | Default         | Description             |
| ------------ | ------ | -------- | --------------- | ----------------------- |
| `start_date` | string | No       | Awal tahun ini  | Start date (YYYY-MM-DD) |
| `end_date`   | string | No       | Akhir tahun ini | End date (YYYY-MM-DD)   |

**Note:** Start date dan end date harus dalam tahun yang sama.

### Example Request

```bash
curl -X GET "https://saibara.lampungprov.go.id/api/dashboard-web/opd-performance?start_date=2026-01-01&end_date=2026-12-31" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### Example Response

```json
{
    "status": 200,
    "message": "Successfully retrieved OPD performance",
    "data": [
        {
            "opd_id": 8,
            "nama_opd": "DINAS PERDAGANGAN",
            "target": "5000000000.00",
            "realisasi": "5500000000.00",
            "percentage": "110.00",
            "status": "achieved"
        },
        {
            "opd_id": 12,
            "nama_opd": "DINAS PERHUBUNGAN",
            "target": "3000000000.00",
            "realisasi": "2400000000.00",
            "percentage": "80.00",
            "status": "on-track"
        }
    ],
    "filters": {
        "start_date": "2026-01-01",
        "end_date": "2026-12-31",
        "year": 2026
    },
    "error": null
}
```

---

## 4. Get Realisasi vs Target (NEW)

⚡ **LIGHTWEIGHT** - Mendapatkan realisasi vs target (per OPD & per retribusi).

**Performance:**

- Queries: 4
- Response size: ~15KB
- Response time: ~500-800ms

**Use case:** Realisasi dashboard section, target achievement charts, performance comparison

### Endpoint

```
GET /api/dashboard-web/realisasi-vs-target
```

### Headers

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Query Parameters

| Parameter    | Type   | Required | Default         | Description             |
| ------------ | ------ | -------- | --------------- | ----------------------- |
| `start_date` | string | No       | Awal tahun ini  | Start date (YYYY-MM-DD) |
| `end_date`   | string | No       | Akhir tahun ini | End date (YYYY-MM-DD)   |

**Note:** Start date dan end date harus dalam tahun yang sama.

### Example Request

```bash
curl -X GET "https://saibara.lampungprov.go.id/api/dashboard-web/realisasi-vs-target?start_date=2026-01-01&end_date=2026-12-31" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### Example Response

```json
{
    "status": 200,
    "message": "Successfully retrieved realisasi vs target",
    "data": {
        "totalTarget": 15000000000.0,
        "totalRealisasi": 13500000000.0,
        "realisasiPercentage": 90.0,
        "realisasiByOpd": [
            {
                "opd_id": 8,
                "nama_opd": "DINAS PERDAGANGAN",
                "target": "5000000000.00",
                "realisasi": "5500000000.00",
                "percentage": "110.00",
                "status": "achieved"
            }
        ],
        "realisasiByRetribusi": [
            {
                "retribusi_id": 1,
                "nama_retribusi": "Retribusi Jasa Umum",
                "target": "6000000000.00",
                "realisasi": "5400000000.00",
                "percentage": "90.00",
                "status": "on-track"
            }
        ]
    },
    "filters": {
        "start_date": "2026-01-01",
        "end_date": "2026-12-31",
        "year": 2026
    },
    "error": null
}
```

---

## 📊 FULL DASHBOARD (DEPRECATED)

---

## 5. Dashboard Data (DEPRECATED)

⚠️ **DEPRECATED** - Gunakan lightweight endpoints di atas untuk performa lebih baik.

Mendapatkan semua data dashboard lengkap (summary, retribusi breakdown, OPD performance, realisasi vs target).

### Endpoint

```
GET /api/dashboard-web
```

### Query Parameters

| Parameter              | Type                | Required | Description                                              |
| ---------------------- | ------------------- | -------- | -------------------------------------------------------- |
| `start_date`           | string (YYYY-MM-DD) | No       | Summary start date (default: awal bulan ini)             |
| `end_date`             | string (YYYY-MM-DD) | No       | Summary end date (default: akhir bulan ini)              |
| `target_start_date`    | string (YYYY-MM-DD) | No       | OPD Performance start date (default: awal tahun ini)     |
| `target_end_date`      | string (YYYY-MM-DD) | No       | OPD Performance end date (default: akhir tahun ini)      |
| `realisasi_start_date` | string (YYYY-MM-DD) | No       | Realisasi vs Target start date (default: awal tahun ini) |
| `realisasi_end_date`   | string (YYYY-MM-DD) | No       | Realisasi vs Target end date (default: akhir tahun ini)  |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web`

**Query Params:**

```
start_date: 2026-01-01
end_date: 2026-03-31
target_start_date: 2026-01-01
target_end_date: 2026-12-31
realisasi_start_date: 2026-01-01
realisasi_end_date: 2026-12-31
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
Content-Type: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved dashboard data",
    "data": {
        "summary": {
            "total_transaksi_regular": 1200,
            "total_transaksi_installment": 350,
            "grand_total_transaksi": 1550,
            "grand_total_pendapatan": "2500000000.00"
        },
        "retribusi_breakdown": [
            {
                "retribusi_id": 1,
                "nama_retribusi": "Retribusi Jasa Umum",
                "total_transaksi": 300,
                "total_pendapatan": "500000000.00"
            },
            {
                "retribusi_id": 2,
                "nama_retribusi": "Retribusi Jasa Usaha",
                "total_transaksi": 1100,
                "total_pendapatan": "1800000000.00"
            },
            {
                "retribusi_id": 3,
                "nama_retribusi": "Retribusi Perizinan Tertentu",
                "total_transaksi": 150,
                "total_pendapatan": "200000000.00"
            }
        ],
        "opd_performance": {
            "data": [
                {
                    "opd_id": 8,
                    "nama_opd": "DINAS PERKEBUNAN",
                    "target": "47956777.00",
                    "realisasi": "406409300.00",
                    "percentage": "847.45",
                    "status": "achieved"
                }
            ],
            "filters": {
                "start_date": "2026-01-01",
                "end_date": "2026-12-31",
                "year": 2026
            },
            "error": null
        },
        "realisasi_vs_target": {
            "summary": {
                "total_target": "3810769158.00",
                "total_realisasi": "2023841660.40",
                "percentage": "53.11"
            },
            "by_opd": [
                {
                    "opd_id": 8,
                    "nama_opd": "DINAS PERKEBUNAN",
                    "target": "47956777.00",
                    "realisasi": "406409300.00",
                    "percentage": "847.45",
                    "status": "achieved"
                }
            ],
            "by_retribusi": [
                {
                    "retribusi_id": 2,
                    "nama_retribusi": "Retribusi Jasa Usaha",
                    "target": "3783461967.00",
                    "realisasi": "1965482389.44",
                    "percentage": "519.49",
                    "status": "achieved"
                }
            ],
            "filters": {
                "start_date": "2026-01-01",
                "end_date": "2026-12-31",
                "year": 2026
            },
            "error": null
        },
        "metadata": {
            "date_range": {
                "start_date": "2026-01-01",
                "end_date": "2026-03-31"
            },
            "cache_version": 1
        }
    }
}
```

### Error Response (401)

```json
{
    "status": 401,
    "message": "You're not authenticated"
}
```

### Validation Notes

- If `target_start_date` and `target_end_date` are in different years, error message will be set and dates reset to current year
- If `realisasi_start_date` and `realisasi_end_date` are in different years, error message will be set and dates reset to current year

---

## 2. OPD List

Mendapatkan daftar OPD dengan fitur search dan pagination.

### Endpoint

```
GET /api/dashboard-web/opd
```

### Query Parameters

| Parameter  | Type    | Required | Description                  |
| ---------- | ------- | -------- | ---------------------------- |
| `search`   | string  | No       | Search by nama OPD           |
| `per_page` | integer | No       | Items per page (default: 15) |
| `page`     | integer | No       | Page number (default: 1)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/opd`

**Query Params:**

```
search: DINAS
per_page: 20
page: 1
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved OPD list",
    "data": [
        {
            "id": 1,
            "nama_opd": "DINAS TENAGA KERJA",
            "created_at": "2026-01-01T00:00:00.000000Z",
            "updated_at": "2026-01-01T00:00:00.000000Z"
        },
        {
            "id": 8,
            "nama_opd": "DINAS PERKEBUNAN",
            "created_at": "2026-01-01T00:00:00.000000Z",
            "updated_at": "2026-01-01T00:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 20,
        "total": 22
    }
}
```

---

## 3. Retribusi List

Mendapatkan daftar jenis retribusi dengan fitur search dan pagination.

### Endpoint

```
GET /api/dashboard-web/retribusi
```

### Query Parameters

| Parameter  | Type    | Required | Description                  |
| ---------- | ------- | -------- | ---------------------------- |
| `search`   | string  | No       | Search by nama retribusi     |
| `per_page` | integer | No       | Items per page (default: 15) |
| `page`     | integer | No       | Page number (default: 1)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/retribusi`

**Query Params:**

```
search: Jasa Usaha
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved Retribusi list",
    "data": [
        {
            "id": 2,
            "nama_retribusi": "Retribusi Jasa Usaha",
            "created_at": "2026-01-01T00:00:00.000000Z",
            "updated_at": "2026-01-01T00:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
```

---

## 4. Lokasi List

Mendapatkan daftar lokasi objek retribusi dengan fitur search dan pagination.

### Endpoint

```
GET /api/dashboard-web/lokasi
```

### Query Parameters

| Parameter  | Type    | Required | Description                  |
| ---------- | ------- | -------- | ---------------------------- |
| `search`   | string  | No       | Search by nama lokasi        |
| `per_page` | integer | No       | Items per page (default: 15) |
| `page`     | integer | No       | Page number (default: 1)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/lokasi`

**Query Params:**

```
search: Pasar
per_page: 10
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved Lokasi list",
    "data": [
        {
            "id": 45,
            "nama_lokasi": "Pasar Tugu",
            "created_at": "2026-01-01T00:00:00.000000Z",
            "updated_at": "2026-01-01T00:00:00.000000Z"
        },
        {
            "id": 46,
            "nama_lokasi": "Pasar Bambu Kuning",
            "created_at": "2026-01-01T00:00:00.000000Z",
            "updated_at": "2026-01-01T00:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 10,
        "total": 50
    }
}
```

---

## 5. Objek List

Mendapatkan daftar objek retribusi dengan fitur search, filter, dan pagination.

### Endpoint

```
GET /api/dashboard-web/objek
```

### Query Parameters

| Parameter      | Type    | Required | Description                  |
| -------------- | ------- | -------- | ---------------------------- |
| `search`       | string  | No       | Search by nama objek         |
| `opd_id`       | integer | No       | Filter by OPD ID             |
| `retribusi_id` | integer | No       | Filter by Retribusi ID       |
| `lokasi_id`    | integer | No       | Filter by Lokasi ID          |
| `per_page`     | integer | No       | Items per page (default: 15) |
| `page`         | integer | No       | Page number (default: 1)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/objek`

**Query Params:**

```
search: Kios
opd_id: 8
retribusi_id: 2
per_page: 20
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved Objek list",
    "data": [
        {
            "id": 123,
            "nama_objek": "Kios A1",
            "master_opd_id": 8,
            "master_retribusi_id": 2,
            "master_lokasi_id": 45,
            "opd": {
                "id": 8,
                "nama_opd": "DINAS PERKEBUNAN"
            },
            "retribusi": {
                "id": 2,
                "nama_retribusi": "Retribusi Jasa Usaha"
            },
            "lokasi": {
                "id": 45,
                "nama_lokasi": "Pasar Tugu"
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 55
    }
}
```

---

## 6. Performance Detail

Mendapatkan detail lengkap performa OPD vs Target (semua OPD).

### Endpoint

```
GET /api/dashboard-web/performance-detail
```

### Query Parameters

| Parameter    | Type                | Required | Description                         |
| ------------ | ------------------- | -------- | ----------------------------------- |
| `tahun`      | integer             | No       | Year filter (default: current year) |
| `start_date` | string (YYYY-MM-DD) | No       | Start date (default: awal tahun)    |
| `end_date`   | string (YYYY-MM-DD) | No       | End date (default: akhir tahun)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/performance-detail`

**Query Params:**

```
tahun: 2026
start_date: 2026-01-01
end_date: 2026-03-31
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved performance detail",
    "data": {
        "opd_performance": [
            {
                "opd_id": 13,
                "nama_opd": "DINAS KEHUTANAN",
                "target": "9470132.00",
                "realisasi": "215925000.00",
                "percentage": "2280.06",
                "status": "achieved"
            },
            {
                "opd_id": 3,
                "nama_opd": "DINAS PETERNAKAN DAN KESWAN",
                "target": "8566860.00",
                "realisasi": "214008000.00",
                "percentage": "2498.09",
                "status": "achieved"
            }
        ],
        "filters": {
            "tahun": 2026,
            "start_date": "2026-01-01",
            "end_date": "2026-03-31"
        },
        "error": null
    }
}
```

### Validation

- If `start_date` and `end_date` are in different years, error message will be returned and dates reset to current year

---

## 7. Realisasi Detail

Mendapatkan detail lengkap realisasi vs target (per OPD dan per jenis retribusi).

### Endpoint

```
GET /api/dashboard-web/realisasi-detail
```

### Query Parameters

| Parameter    | Type                | Required | Description                         |
| ------------ | ------------------- | -------- | ----------------------------------- |
| `tahun`      | integer             | No       | Year filter (default: current year) |
| `start_date` | string (YYYY-MM-DD) | No       | Start date (default: awal tahun)    |
| `end_date`   | string (YYYY-MM-DD) | No       | End date (default: akhir tahun)     |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/realisasi-detail`

**Query Params:**

```
tahun: 2026
start_date: 2026-01-01
end_date: 2026-12-31
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved realisasi detail",
    "data": {
        "summary": {
            "total_target": "3810769158.00",
            "total_realisasi": "2023841660.40",
            "percentage": "53.11"
        },
        "by_opd": [
            {
                "opd_id": 13,
                "nama_opd": "DINAS KEHUTANAN",
                "target": "9470132.00",
                "realisasi": "215925000.00",
                "percentage": "2280.06",
                "status": "achieved"
            }
        ],
        "by_retribusi": [
            {
                "retribusi_id": 2,
                "nama_retribusi": "Retribusi Jasa Usaha",
                "target": "3783461967.00",
                "realisasi": "1965482389.44",
                "percentage": "519.49",
                "status": "achieved"
            },
            {
                "retribusi_id": 3,
                "nama_retribusi": "Retribusi Perizinan Tertentu",
                "target": "8904435.00",
                "realisasi": "40260804.00",
                "percentage": "452.14",
                "status": "achieved"
            },
            {
                "retribusi_id": 1,
                "nama_retribusi": "Retribusi Jasa Umum",
                "target": "18402756.00",
                "realisasi": "23874000.00",
                "percentage": "129.73",
                "status": "achieved"
            }
        ],
        "filters": {
            "tahun": 2026,
            "start_date": "2026-01-01",
            "end_date": "2026-12-31"
        },
        "error": null
    }
}
```

---

## 8. Realisasi Lokasi by OPD

Mendapatkan realisasi pendapatan per lokasi untuk OPD tertentu.

### Endpoint

```
GET /api/dashboard-web/opd/{opdId}/lokasi
```

### Path Parameters

| Parameter | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| `opdId`   | integer | Yes      | OPD ID      |

### Query Parameters

| Parameter | Type    | Required | Description                         |
| --------- | ------- | -------- | ----------------------------------- |
| `tahun`   | integer | No       | Year filter (default: current year) |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/opd/8/lokasi`

**Query Params:**

```
tahun: 2026
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved realisasi by lokasi for OPD",
    "data": {
        "opd_info": {
            "id": 8,
            "nama_opd": "DINAS PERKEBUNAN"
        },
        "year": 2026,
        "summary": {
            "total_target": "47956777.00",
            "total_realisasi": "406409300.00",
            "total_percentage": "847.45"
        },
        "lokasi_data": [
            {
                "lokasi_id": 45,
                "nama_lokasi": "Pasar Tugu",
                "target": "20000000.00",
                "realisasi": "180000000.00",
                "total_transaksi": 250,
                "percentage": "900.00",
                "status": "achieved"
            },
            {
                "lokasi_id": 46,
                "nama_lokasi": "Pasar Bambu Kuning",
                "target": "15000000.00",
                "realisasi": "120000000.00",
                "total_transaksi": 180,
                "percentage": "800.00",
                "status": "achieved"
            }
        ]
    }
}
```

### Error Response (404)

```json
{
    "status": 404,
    "message": "OPD not found"
}
```

---

## 9. Realisasi Retribusi by Lokasi

Mendapatkan breakdown realisasi per jenis retribusi untuk lokasi tertentu.

### Endpoint

```
GET /api/dashboard-web/opd/{opdId}/lokasi/{lokasiId}/retribusi
```

### Path Parameters

| Parameter  | Type    | Required | Description |
| ---------- | ------- | -------- | ----------- |
| `opdId`    | integer | Yes      | OPD ID      |
| `lokasiId` | integer | Yes      | Lokasi ID   |

### Query Parameters

| Parameter | Type    | Required | Description                         |
| --------- | ------- | -------- | ----------------------------------- |
| `tahun`   | integer | No       | Year filter (default: current year) |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/opd/8/lokasi/45/retribusi`

**Query Params:**

```
tahun: 2026
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved retribusi breakdown for lokasi",
    "data": {
        "opd_info": {
            "id": 8,
            "nama_opd": "DINAS PERKEBUNAN"
        },
        "lokasi_info": {
            "id": 45,
            "nama_lokasi": "Pasar Tugu"
        },
        "year": 2026,
        "summary": {
            "total_target": "20000000.00",
            "total_realisasi": "180000000.00",
            "total_transaksi": 250,
            "total_percentage": "900.00",
            "status": "achieved"
        },
        "retribusi_breakdown": [
            {
                "retribusi_id": 1,
                "nama_retribusi": "Retribusi Jasa Umum",
                "target": "0.00",
                "realisasi": "0.00",
                "total_transaksi": 0,
                "percentage": "0.00",
                "status": "below-target"
            },
            {
                "retribusi_id": 2,
                "nama_retribusi": "Retribusi Jasa Usaha",
                "target": "20000000.00",
                "realisasi": "180000000.00",
                "total_transaksi": 250,
                "percentage": "900.00",
                "status": "achieved"
            },
            {
                "retribusi_id": 3,
                "nama_retribusi": "Retribusi Perizinan Tertentu",
                "target": "0.00",
                "realisasi": "0.00",
                "total_transaksi": 0,
                "percentage": "0.00",
                "status": "below-target"
            }
        ]
    }
}
```

### Error Response (404)

```json
{
    "status": 404,
    "message": "OPD or Lokasi not found"
}
```

---

## 10. Get Lokasi by OPD (Helper)

Helper endpoint untuk mendapatkan daftar lokasi berdasarkan OPD (untuk dropdown).

### Endpoint

```
GET /api/dashboard-web/helper/lokasi-by-opd
```

### Query Parameters

| Parameter | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| `opd_id`  | integer | Yes      | OPD ID      |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/helper/lokasi-by-opd`

**Query Params:**

```
opd_id: 8
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved lokasi list",
    "data": [
        {
            "id": 45,
            "nama_lokasi": "Pasar Tugu"
        },
        {
            "id": 46,
            "nama_lokasi": "Pasar Bambu Kuning"
        }
    ]
}
```

### Error Response (400)

```json
{
    "status": 400,
    "message": "OPD ID is required"
}
```

---

## 11. Get Rincian by Retribusi (Helper)

Helper endpoint untuk mendapatkan daftar rincian retribusi berdasarkan jenis retribusi (untuk dropdown).

### Endpoint

```
GET /api/dashboard-web/helper/rincian-by-retribusi
```

### Query Parameters

| Parameter      | Type    | Required | Description  |
| -------------- | ------- | -------- | ------------ |
| `retribusi_id` | integer | Yes      | Retribusi ID |

### Example Request (Postman)

**Method:** GET  
**URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/helper/rincian-by-retribusi`

**Query Params:**

```
retribusi_id: 2
```

**Headers:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
Accept: application/json
```

### Success Response (200)

```json
{
    "status": 200,
    "message": "Successfully retrieved rincian list",
    "data": [
        {
            "id": 10,
            "nama_rincian": "Retribusi Pasar"
        },
        {
            "id": 11,
            "nama_rincian": "Retribusi Terminal"
        }
    ]
}
```

### Error Response (400)

```json
{
    "status": 400,
    "message": "Retribusi ID is required"
}
```

---

## 🔧 Postman Collection Setup

### 1. Create Environment

**Environment Name:** E-Retribusi Dashboard Web API

**Variables:**

```
base_url: https://e-retribusi-trial.bapenda-lampungprov.com/api
api_token: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
```

### 2. No Login Required! ✅

**TIDAK PERLU LOGIN** - Langsung gunakan API token yang sudah tersedia.

Token sudah di-set di environment variable `api_token`.

### 3. Collection Headers

Add these headers to all requests:

```
X-API-TOKEN: {{api_token}}
Accept: application/json
Content-Type: application/json
```

---

## 📊 Sample Postman Collection JSON

```json
{
    "info": {
        "name": "E-Retribusi Dashboard Web API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "variable": [
        {
            "key": "base_url",
            "value": "https://e-retribusi-trial.bapenda-lampungprov.com/api"
        },
        {
            "key": "api_token",
            "value": "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA"
        }
    ],
    "item": [
        {
            "name": "Dashboard Web API",
            "item": [
                {
                    "name": "Get Dashboard Data",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "X-API-TOKEN",
                                "value": "{{api_token}}"
                            },
                            {
                                "key": "Accept",
                                "value": "application/json"
                            }
                        ],
                        "url": {
                            "raw": "{{base_url}}/dashboard-web?start_date=2026-01-01&end_date=2026-03-31",
                            "host": ["{{base_url}}"],
                            "path": ["dashboard-web"],
                            "query": [
                                {
                                    "key": "start_date",
                                    "value": "2026-01-01"
                                },
                                {
                                    "key": "end_date",
                                    "value": "2026-03-31"
                                }
                            ]
                        }
                    }
                },
                {
                    "name": "Get OPD List",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "X-API-TOKEN",
                                "value": "{{api_token}}"
                            },
                            {
                                "key": "Accept",
                                "value": "application/json"
                            }
                        ],
                        "url": {
                            "raw": "{{base_url}}/dashboard-web/opd?search=DINAS&per_page=20",
                            "host": ["{{base_url}}"],
                            "path": ["dashboard-web", "opd"],
                            "query": [
                                {
                                    "key": "search",
                                    "value": "DINAS"
                                },
                                {
                                    "key": "per_page",
                                    "value": "20"
                                }
                            ]
                        }
                    }
                },
                {
                    "name": "Get Realisasi Lokasi by OPD",
                    "request": {
                        "method": "GET",
                        "header": [
                            {
                                "key": "X-API-TOKEN",
                                "value": "{{api_token}}"
                            },
                            {
                                "key": "Accept",
                                "value": "application/json"
                            }
                        ],
                        "url": {
                            "raw": "{{base_url}}/dashboard-web/opd/8/lokasi?tahun=2026",
                            "host": ["{{base_url}}"],
                            "path": ["dashboard-web", "opd", "8", "lokasi"],
                            "query": [
                                {
                                    "key": "tahun",
                                    "value": "2026"
                                }
                            ]
                        }
                    }
                }
            ]
        }
    ]
}
```

---

## 📝 Important Notes

### 1. Authentication

- **No login required** - Use `EXTERNAL_API_TOKEN` from .env
- Token is static and does not expire
- Include token in `X-API-TOKEN` header for all requests
- Token value: `GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA`

### 2. Date Range Validation

- **Performance Detail** and **Realisasi Detail** validate that start_date and end_date must be in the same year
- If different years detected, error message returned and dates reset to current year range

### 3. Caching

- Dashboard data is cached for 6 hours (21600 seconds)
- Cache key includes date range to ensure fresh data for different filters
- Cache version can be incremented to invalidate all caches

### 4. Pagination

- Default per_page: 15
- Maximum per_page: 100
- Use `page` parameter for navigation

### 5. Status Values

- `achieved`: Percentage >= 100%
- `on-track`: Percentage >= 75% and < 100%
- `below-target`: Percentage < 75%

---

## 🆘 Troubleshooting

### Error: 401 Unauthorized

**Solution:** Invalid or missing `X-API-TOKEN` header. Ensure header is set correctly with the token from .env file.

### Error: 404 Not Found

**Solution:** Check OPD ID, Lokasi ID, or Retribusi ID exists in database.

### Error: 500 Internal Server Error

**Solution:** Check server logs for detailed error message.

### Empty data arrays

**Solution:**

- Verify data exists in database for selected filters
- Check date range is valid
- Ensure OPD/Lokasi/Retribusi IDs are correct

---

## 🔄 Comparison with Web Dashboard

| Feature            | Web Dashboard  | Dashboard Web API        |
| ------------------ | -------------- | ------------------------ |
| **Authentication** | Session Cookie | X-API-TOKEN Header       |
| **Response Type**  | HTML Views     | JSON                     |
| **Use Case**       | Browser UI     | External Web Integration |
| **Login Required** | Yes            | No (Static Token)        |
| **Endpoints**      | 11 routes      | 11 API endpoints         |
| **Data**           | Identical      | Identical                |
| **Caching**        | Yes (6 hours)  | Yes (6 hours)            |
| **Pagination**     | Server-side    | API pagination           |

---

## 📦 Integration Example (JavaScript/Fetch)

```javascript
// API Token (dari .env EXTERNAL_API_TOKEN)
const API_TOKEN =
    "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA";

// Get dashboard data - NO LOGIN REQUIRED!
async function getDashboardData() {
    const response = await fetch(
        "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web?start_date=2026-01-01&end_date=2026-03-31",
        {
            headers: {
                "X-API-TOKEN": API_TOKEN,
                Accept: "application/json",
            },
        },
    );

    return await response.json();
}

// Get OPD list
async function getOpdList(search = "") {
    const url = new URL(
        "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web/opd",
    );
    if (search) url.searchParams.append("search", search);

    const response = await fetch(url, {
        headers: {
            "X-API-TOKEN": API_TOKEN,
            Accept: "application/json",
        },
    });

    return await response.json();
}

// Usage - Langsung panggil tanpa login!
const dashboardData = await getDashboardData();
console.log(dashboardData);

const opdList = await getOpdList("DINAS");
console.log(opdList);
```

---

**Version:** 1.0  
**Last Updated:** April 10, 2026  
**Controller:** `App\Http\Controllers\Api\DashboardWebController`  
**Routes Prefix:** `/api/dashboard-web`
