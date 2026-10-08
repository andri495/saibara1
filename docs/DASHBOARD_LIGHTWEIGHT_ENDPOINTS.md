# 🚀 Dashboard Lightweight Endpoints

Endpoint-endpoint terpisah untuk performa lebih baik. Setiap endpoint hanya mengambil data yang dibutuhkan.

---

## 📋 Daftar Endpoint Baru

### **1. Summary Only** (Paling Ringan)

```
GET /api/dashboard-web/summary
```

### **2. Retribusi Breakdown**

```
GET /api/dashboard-web/retribusi-breakdown
```

### **3. OPD Performance**

```
GET /api/dashboard-web/opd-performance
```

### **4. Realisasi vs Target**

```
GET /api/dashboard-web/realisasi-vs-target
```

---

## 🎯 Perbandingan

| Endpoint                   | Query Count | Response Size | Use Case               |
| -------------------------- | ----------- | ------------- | ---------------------- |
| **`/` (OLD)**              | 10+ queries | ~500KB        | Full dashboard (HEAVY) |
| **`/summary`**             | 3 queries   | ~1KB          | Summary cards only     |
| **`/retribusi-breakdown`** | 1 query     | ~2KB          | Retribusi chart        |
| **`/opd-performance`**     | 2 queries   | ~10KB         | OPD table              |
| **`/realisasi-vs-target`** | 4 queries   | ~15KB         | Realisasi section      |

---

## 📖 Detail Endpoint

### **1. GET /api/dashboard-web/summary**

**Deskripsi:** Mendapatkan summary metrics saja (total transaksi & pendapatan)

**Query Parameters:**

```
start_date: 2026-01-01 (optional, default: awal bulan ini)
end_date: 2026-03-31 (optional, default: akhir bulan ini)
```

**Headers:**

```
X-API-TOKEN: {your_api_token}
Accept: application/json
```

**Response Example:**

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

**Use Case:**

- Dashboard summary cards
- Quick metrics display
- Mobile app summary screen

---

### **2. GET /api/dashboard-web/retribusi-breakdown**

**Deskripsi:** Mendapatkan breakdown per jenis retribusi saja

**Query Parameters:**

```
start_date: 2026-01-01 (optional)
end_date: 2026-03-31 (optional)
```

**Headers:**

```
X-API-TOKEN: {your_api_token}
Accept: application/json
```

**Response Example:**

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

**Use Case:**

- Pie chart retribusi
- Retribusi breakdown table
- Statistics by retribusi type

---

### **3. GET /api/dashboard-web/opd-performance**

**Deskripsi:** Mendapatkan performa OPD vs target saja

**Query Parameters:**

```
start_date: 2026-01-01 (optional, default: awal tahun)
end_date: 2026-12-31 (optional, default: akhir tahun)
```

**Headers:**

```
X-API-TOKEN: {your_api_token}
Accept: application/json
```

**Response Example:**

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

**Use Case:**

- OPD performance table
- Progress bars per OPD
- Achievement ranking

---

### **4. GET /api/dashboard-web/realisasi-vs-target**

**Deskripsi:** Mendapatkan realisasi vs target (per OPD & per retribusi)

**Query Parameters:**

```
start_date: 2026-01-01 (optional, default: awal tahun)
end_date: 2026-12-31 (optional, default: akhir tahun)
```

**Headers:**

```
X-API-TOKEN: {your_api_token}
Accept: application/json
```

**Response Example:**

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

**Use Case:**

- Realisasi dashboard section
- Target achievement charts
- Performance comparison

---

## 🔄 Migration Guide

### **Before (Heavy):**

```javascript
// 1 request, 10+ queries, ~500KB response
const response = await fetch("/api/dashboard-web", {
    headers: { "X-API-TOKEN": token },
});
const data = await response.json();
```

### **After (Lightweight):**

```javascript
// 4 parallel requests, 3-4 queries each, ~28KB total
const [summary, retribusi, opd, realisasi] = await Promise.all([
    fetch("/api/dashboard-web/summary", {
        headers: { "X-API-TOKEN": token },
    }),
    fetch("/api/dashboard-web/retribusi-breakdown", {
        headers: { "X-API-TOKEN": token },
    }),
    fetch("/api/dashboard-web/opd-performance", {
        headers: { "X-API-TOKEN": token },
    }),
    fetch("/api/dashboard-web/realisasi-vs-target", {
        headers: { "X-API-TOKEN": token },
    }),
]);

const summaryData = await summary.json();
const retribusiData = await retribusi.json();
const opdData = await opd.json();
const realisasiData = await realisasi.json();
```

---

## 💡 Best Practices

### **1. Load Only What You Need**

```javascript
// Jika hanya perlu summary
const summary = await fetch("/api/dashboard-web/summary");

// Jika perlu summary + retribusi
const [summary, retribusi] = await Promise.all([
    fetch("/api/dashboard-web/summary"),
    fetch("/api/dashboard-web/retribusi-breakdown"),
]);
```

### **2. Progressive Loading**

```javascript
// Load summary first (fastest)
const summary = await fetch("/api/dashboard-web/summary");
displaySummary(summary);

// Then load others in background
Promise.all([
    fetch("/api/dashboard-web/retribusi-breakdown"),
    fetch("/api/dashboard-web/opd-performance"),
    fetch("/api/dashboard-web/realisasi-vs-target"),
]).then(([retribusi, opd, realisasi]) => {
    displayRetribusi(retribusi);
    displayOpd(opd);
    displayRealisasi(realisasi);
});
```

### **3. Conditional Loading**

```javascript
// Load based on user interaction
if (userClickedOpdTab) {
    const opd = await fetch("/api/dashboard-web/opd-performance");
    displayOpdTable(opd);
}

if (userClickedRealisasiTab) {
    const realisasi = await fetch("/api/dashboard-web/realisasi-vs-target");
    displayRealisasiChart(realisasi);
}
```

---

## 📊 Performance Comparison

### **Full Dashboard (OLD):**

```
Endpoint: GET /api/dashboard-web
Queries: 10+ database queries
Response Time: ~2-3 seconds
Response Size: ~500KB
Cache: 6 hours
```

### **Lightweight Endpoints (NEW):**

```
Endpoint: GET /api/dashboard-web/summary
Queries: 3 database queries
Response Time: ~200-300ms
Response Size: ~1KB
Cache: 6 hours

Endpoint: GET /api/dashboard-web/retribusi-breakdown
Queries: 1 database query
Response Time: ~150-250ms
Response Size: ~2KB
Cache: 6 hours

Endpoint: GET /api/dashboard-web/opd-performance
Queries: 2 database queries
Response Time: ~300-500ms
Response Size: ~10KB
Cache: 6 hours

Endpoint: GET /api/dashboard-web/realisasi-vs-target
Queries: 4 database queries
Response Time: ~500-800ms
Response Size: ~15KB
Cache: 6 hours
```

**Total (4 endpoints parallel):**

- **Response Time:** ~800ms (fastest endpoint determines total time)
- **Total Size:** ~28KB (vs 500KB)
- **Improvement:** 94% smaller, 60% faster

---

## ✅ Benefits

1. **⚡ Faster Load Time** - Only load what you need
2. **📉 Reduced Bandwidth** - Smaller response sizes
3. **🔄 Better UX** - Progressive loading possible
4. **💰 Lower Server Load** - Fewer queries per request
5. **🎯 Flexible** - Mix and match endpoints
6. **📱 Mobile Friendly** - Smaller payloads for mobile

---

## 🔧 Backward Compatibility

**Old endpoint masih tersedia:**

```
GET /api/dashboard-web
```

Tapi sekarang **DEPRECATED** dan akan return summary only untuk performa.

**Recommendation:** Migrate ke endpoint baru untuk performa optimal.

---

## 📝 Notes

- Semua endpoint menggunakan **cache 6 jam**
- Semua endpoint require **X-API-TOKEN** header
- Semua endpoint support **date range filtering**
- Response format konsisten (status, message, data)

---

**Base URL Production:** `https://saibara.lampungprov.go.id/api`

---

**Version:** 2.0  
**Last Updated:** April 17, 2026  
**Migration:** Recommended for all new integrations
