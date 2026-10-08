# 📚 E-Retribusi API Documentation

Dokumentasi lengkap untuk E-Retribusi API Dashboard Web Integration.

---

## 📋 Available Documentation

### 1. **Postman Collection** ⭐ (Ready to Import!)

**Files:**

- 📦 **`Dashboard_Web_API.postman_collection.json`** - Collection dengan 11 endpoints
- 🌐 **`Dashboard_Web_API.postman_environment.json`** - Environment variables
- 📖 **`POSTMAN_IMPORT_GUIDE.md`** - Panduan import & usage

**Quick Start:**

1. Import collection & environment ke Postman
2. Aktifkan environment
3. Click Send - **No Login Required!**

---

### 2. **API Documentation**

**File:** `POSTMAN_DASHBOARD_WEB_API.md`

**Contents:**

- 🔐 Authentication (X-API-TOKEN)
- 📡 11 Endpoint details dengan examples
- 🔧 Request/Response formats
- 🆘 Troubleshooting guide
- 💻 JavaScript integration examples

---

### 3. **SQL Scripts**

**Files:**

- `insert_dummy_retribution_targets.sql` - Insert dummy target data
- `debug_dashboard_targets.sql` - Debug queries untuk troubleshooting

---

## 🚀 Quick Start

### Option 1: Using Postman (Recommended)

```bash
1. Import: Dashboard_Web_API.postman_collection.json
2. Import: Dashboard_Web_API.postman_environment.json
3. Activate environment
4. Test endpoint: Get Dashboard Data
```

### Option 2: Using cURL

```bash
curl -X GET "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### Option 3: Using JavaScript

```javascript
const API_TOKEN =
    "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA";

const response = await fetch(
    "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web",
    {
        headers: {
            "X-API-TOKEN": API_TOKEN,
            Accept: "application/json",
        },
    },
);

const data = await response.json();
console.log(data);
```

---

## 🔐 Authentication

**Type:** Static API Token (No Login Required)

**Header:**

```
X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA
```

**Source:** Environment variable `EXTERNAL_API_TOKEN` di `.env`

---

## 📡 Available Endpoints

### 1. Dashboard Data

- `GET /api/dashboard-web` - Get complete dashboard data

### 2. Master Data Lists

- `GET /api/dashboard-web/opd` - Get OPD list
- `GET /api/dashboard-web/retribusi` - Get Retribusi list
- `GET /api/dashboard-web/lokasi` - Get Lokasi list
- `GET /api/dashboard-web/objek` - Get Objek list

### 3. Performance & Realisasi

- `GET /api/dashboard-web/performance-detail` - Get performance detail
- `GET /api/dashboard-web/realisasi-detail` - Get realisasi detail

### 4. OPD Drill-down

- `GET /api/dashboard-web/opd/{opdId}/lokasi` - Get realisasi by lokasi
- `GET /api/dashboard-web/opd/{opdId}/lokasi/{lokasiId}/retribusi` - Get retribusi breakdown

### 5. Helper Endpoints

- `GET /api/dashboard-web/helper/lokasi-by-opd` - Get lokasi by OPD
- `GET /api/dashboard-web/helper/rincian-by-retribusi` - Get rincian by retribusi

**Total:** 11 endpoints

---

## 📊 Response Format

All endpoints return JSON with consistent structure:

```json
{
    "status": 200,
    "message": "Success message",
    "data": {
        // Response data here
    }
}
```

**Error Response:**

```json
{
    "status": 401,
    "message": "Invalid or missing API token"
}
```

---

## 🔧 Features

✅ **No Login Required** - Static token authentication  
✅ **11 Endpoints** - Complete dashboard functionality  
✅ **Pagination Support** - For list endpoints  
✅ **Search & Filter** - Flexible data querying  
✅ **Date Range Filter** - Customizable time periods  
✅ **Caching** - 6 hours cache for performance  
✅ **Error Handling** - Comprehensive error messages  
✅ **CORS Enabled** - Ready for web integration

---

## 📖 Documentation Files

| File                                         | Description                | Type     |
| -------------------------------------------- | -------------------------- | -------- |
| `Dashboard_Web_API.postman_collection.json`  | Postman collection         | JSON     |
| `Dashboard_Web_API.postman_environment.json` | Postman environment        | JSON     |
| `POSTMAN_IMPORT_GUIDE.md`                    | Import & usage guide       | Markdown |
| `POSTMAN_DASHBOARD_WEB_API.md`               | Complete API documentation | Markdown |
| `README.md`                                  | This file                  | Markdown |

---

## 🎯 Use Cases

### 1. External Web Integration

Integrate dashboard ke website eksternal dengan simple API calls.

### 2. Mobile App Backend

Gunakan sebagai backend untuk mobile app (Flutter, React Native, etc).

### 3. Data Analytics

Pull data untuk analytics dashboard atau reporting tools.

### 4. Third-party Integration

Integrate dengan sistem third-party yang membutuhkan dashboard data.

---

## 🔄 API vs Web Dashboard

| Feature            | Web Dashboard  | Dashboard Web API    |
| ------------------ | -------------- | -------------------- |
| **Authentication** | Session Cookie | X-API-TOKEN Header   |
| **Response Type**  | HTML Views     | JSON                 |
| **Use Case**       | Browser UI     | External Integration |
| **Login Required** | Yes            | No (Static Token)    |
| **Endpoints**      | 11 routes      | 11 API endpoints     |
| **Data**           | Identical      | Identical            |

---

## 🆘 Support

### Documentation

- **Full API Docs:** `POSTMAN_DASHBOARD_WEB_API.md`
- **Import Guide:** `POSTMAN_IMPORT_GUIDE.md`

### Code Reference

- **Controller:** `app/Http/Controllers/Api/DashboardWebController.php`
- **Routes:** `routes/api.php` (prefix: `/api/dashboard-web`)
- **Middleware:** `app/Http/Middleware/ValidateApiToken.php`

### Troubleshooting

Check `POSTMAN_DASHBOARD_WEB_API.md` section "🆘 Troubleshooting" for common issues.

---

## 📦 Download & Import

### For Postman Users:

1. Download files:
    - `Dashboard_Web_API.postman_collection.json`
    - `Dashboard_Web_API.postman_environment.json`

2. Import to Postman:
    - Collection: Import → File → Select collection JSON
    - Environment: Environments → Import → Select environment JSON

3. Start testing!

### For Developers:

1. Read: `POSTMAN_DASHBOARD_WEB_API.md`
2. Copy integration code examples
3. Use API token from `.env` file
4. Start integrating!

---

## 🔒 Security Notes

⚠️ **IMPORTANT:**

- **API Token** adalah static dan tidak expire
- **JANGAN** commit token ke public repository
- **JANGAN** share environment file dengan token
- **GUNAKAN** environment variables untuk token
- **ROTASI** token secara berkala untuk security

---

## 📝 Version History

### Version 1.0 (April 10, 2026)

- ✅ Initial release
- ✅ 11 endpoints available
- ✅ X-API-TOKEN authentication
- ✅ Complete Postman collection
- ✅ Full documentation

---

## 🚀 Getting Started Checklist

- [ ] Read `POSTMAN_IMPORT_GUIDE.md`
- [ ] Import Postman collection
- [ ] Import Postman environment
- [ ] Test "Get Dashboard Data" endpoint
- [ ] Read `POSTMAN_DASHBOARD_WEB_API.md` for details
- [ ] Integrate to your application
- [ ] Test all required endpoints
- [ ] Implement error handling

---

## 💡 Tips

1. **Use Postman Collection** - Fastest way to test API
2. **Save Examples** - Save successful responses in Postman
3. **Check Documentation** - Full details in `POSTMAN_DASHBOARD_WEB_API.md`
4. **Use Variables** - Leverage Postman environment variables
5. **Test First** - Always test in Postman before coding

---

**Happy Coding! 🎉**

\*\*## 🌐 Base URL

```
https://saibara.lampungprov.go.id/api
```

**Prefix:** `/dashboard-web`  
**Auth:** `X-API-TOKEN` Header  
**Version:** 1.0
