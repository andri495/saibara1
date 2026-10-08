# 📦 Postman Collection Import Guide

Panduan lengkap untuk import dan menggunakan Dashboard Web API di Postman.

---

## 📋 Files yang Tersedia

1. **`Dashboard_Web_API.postman_collection.json`** - Collection dengan 11 endpoints
2. **`Dashboard_Web_API.postman_environment.json`** - Environment variables (base_url & api_token)
3. **`POSTMAN_DASHBOARD_WEB_API.md`** - Dokumentasi lengkap API

---

## 🚀 Cara Import ke Postman

### Step 1: Import Collection

1. Buka **Postman**
2. Click **Import** button (kiri atas)
3. Pilih **File** tab
4. Drag & drop atau browse file: `Dashboard_Web_API.postman_collection.json`
5. Click **Import**

✅ Collection "**E-Retribusi Dashboard Web API**" akan muncul di sidebar

---

### Step 2: Import Environment

1. Click **Environments** di sidebar kiri (icon ⚙️)
2. Click **Import** button
3. Pilih file: `Dashboard_Web_API.postman_environment.json`
4. Click **Import**

✅ Environment "**E-Retribusi Dashboard Web API - Production**" akan tersedia

---

### Step 3: Aktifkan Environment

1. Di kanan atas Postman, click dropdown **"No Environment"**
2. Pilih **"E-Retribusi Dashboard Web API - Production"**

✅ Environment sudah aktif dengan variables:
- `base_url`: `https://e-retribusi-trial.bapenda-lampungprov.com/api`
- `api_token`: `GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA`

---

## 🎯 Cara Menggunakan

### Test API (No Login Required!)

1. Buka collection **"E-Retribusi Dashboard Web API"**
2. Expand folder **"1. Dashboard Data"**
3. Click request **"Get Dashboard Data"**
4. Click **Send** button

✅ Response akan muncul dengan status **200 OK**

**Headers otomatis ter-set:**
```
X-API-TOKEN: {{api_token}}
Accept: application/json
```

---

## 📂 Struktur Collection

```
E-Retribusi Dashboard Web API/
├── 1. Dashboard Data/
│   └── Get Dashboard Data
├── 2. Master Data Lists/
│   ├── Get OPD List
│   ├── Get Retribusi List
│   ├── Get Lokasi List
│   └── Get Objek List
├── 3. Performance & Realisasi/
│   ├── Get Performance Detail
│   └── Get Realisasi Detail
├── 4. OPD Drill-down/
│   ├── Get Realisasi Lokasi by OPD
│   └── Get Realisasi Retribusi by Lokasi
└── 5. Helper Endpoints/
    ├── Get Lokasi by OPD
    └── Get Rincian by Retribusi
```

**Total:** 11 endpoints siap pakai!

---

## 🔧 Customize Query Parameters

Setiap request sudah dilengkapi dengan query parameters yang bisa di-edit:

### Example: Get Dashboard Data

**Query Params (bisa diubah):**
```
start_date: 2026-01-01
end_date: 2026-03-31
target_start_date: 2026-01-01
target_end_date: 2026-12-31
realisasi_start_date: 2026-01-01
realisasi_end_date: 2026-12-31
```

**Cara Edit:**
1. Click tab **Params** di request
2. Edit value sesuai kebutuhan
3. Click **Send**

---

## 🌐 Ganti Environment (Development/Staging)

Jika ingin test di environment lain:

### Option 1: Edit Environment Variable

1. Click **Environments** → **E-Retribusi Dashboard Web API - Production**
2. Edit `base_url`:
   - **Production:** `https://e-retribusi-trial.bapenda-lampungprov.com/api`
   - **Development:** `http://localhost:8000/api`
3. Click **Save**

### Option 2: Duplicate Environment

1. Right-click environment → **Duplicate**
2. Rename: "E-Retribusi Dashboard Web API - Development"
3. Edit `base_url` ke local/staging URL
4. Switch environment di dropdown

---

## 📝 Response Examples

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
    "retribusi_breakdown": [...],
    "opd_performance": {...},
    "realisasi_vs_target": {...}
  }
}
```

### Error Response (401)
```json
{
  "message": "Invalid or missing API token"
}
```

---

## 🔐 Security Notes

### API Token

Token disimpan di environment variable sebagai **secret type**.

**⚠️ JANGAN SHARE:**
- Environment file dengan token ke public repository
- Screenshot Postman yang menampilkan token
- Token di documentation public

**✅ BEST PRACTICE:**
- Simpan token di environment variable
- Gunakan `.gitignore` untuk file environment
- Share collection tanpa environment (atau dengan token placeholder)

---

## 🆘 Troubleshooting

### Error: "Invalid or missing API token"

**Solution:**
1. Check environment sudah aktif (kanan atas Postman)
2. Verify `api_token` variable sudah ter-set
3. Check header `X-API-TOKEN` ada di request

### Error: "Could not get any response"

**Solution:**
1. Check internet connection
2. Verify `base_url` benar
3. Test URL di browser: `https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web`

### Response kosong atau error 404

**Solution:**
1. Check endpoint path benar
2. Verify OPD ID / Lokasi ID exists di database
3. Check query parameters format (YYYY-MM-DD untuk date)

---

## 📊 Testing Workflow

### Recommended Testing Order:

1. ✅ **Get Dashboard Data** - Test koneksi & token
2. ✅ **Get OPD List** - Lihat daftar OPD
3. ✅ **Get Realisasi Lokasi by OPD** - Test dengan OPD ID dari step 2
4. ✅ **Get Performance Detail** - Test filtering by date
5. ✅ **Get Helper Endpoints** - Test dropdown data

---

## 🔄 Update Collection

Jika ada update API:

1. Download file collection terbaru
2. Di Postman: Right-click collection → **Replace**
3. Pilih file baru
4. Click **Replace**

✅ Collection akan ter-update tanpa kehilangan environment settings

---

## 📦 Export Collection (untuk sharing)

### Export Collection Only:

1. Right-click collection → **Export**
2. Pilih **Collection v2.1**
3. Save file

### Export dengan Environment:

1. Export collection (step di atas)
2. Export environment: Environments → ... → **Export**
3. **⚠️ HAPUS** `api_token` value sebelum share!

---

## 💡 Tips & Tricks

### 1. Save Response as Example

Setelah get response sukses:
1. Click **Save Response**
2. Pilih **Save as example**
3. Response akan tersimpan untuk reference

### 2. Use Variables in Body/Params

Bisa gunakan environment variables di mana saja:
```
{{base_url}}/dashboard-web/opd/{{opd_id}}/lokasi
```

### 3. Pre-request Scripts

Untuk auto-generate date:
```javascript
// Set today as start_date
pm.environment.set("start_date", new Date().toISOString().split('T')[0]);
```

### 4. Tests Scripts

Auto-validate response:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.data).to.exist;
});
```

---

## 📚 Additional Resources

- **Full Documentation:** `POSTMAN_DASHBOARD_WEB_API.md`
- **API Controller:** `app/Http/Controllers/Api/DashboardWebController.php`
- **Routes:** `routes/api.php` (prefix: `/api/dashboard-web`)

---

## ✅ Quick Start Checklist

- [ ] Import collection JSON
- [ ] Import environment JSON
- [ ] Activate environment
- [ ] Test "Get Dashboard Data" endpoint
- [ ] Verify response status 200
- [ ] Explore other endpoints
- [ ] Save useful responses as examples

---

**Happy Testing! 🚀**

**Version:** 1.0  
**Last Updated:** April 10, 2026  
**Support:** Check `POSTMAN_DASHBOARD_WEB_API.md` for detailed API documentation
