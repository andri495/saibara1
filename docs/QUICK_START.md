# ⚡ Quick Start - Dashboard Web API

Get started in **3 minutes**!

---

## 🎯 What You Need

✅ **Postman** (Download: https://www.postman.com/downloads/)  
✅ **2 Files** dari folder `docs/`:

- `Dashboard_Web_API.postman_collection.json`
- `Dashboard_Web_API.postman_environment.json`

---

## 🚀 3 Steps to Success

### Step 1: Import Collection (30 seconds)

1. Open **Postman**
2. Click **Import** button
3. Drag & drop: `Dashboard_Web_API.postman_collection.json`
4. Click **Import**

✅ Done! Collection "**E-Retribusi Dashboard Web API**" ready.

---

### Step 2: Import Environment (30 seconds)

1. Click **Environments** (⚙️ icon di sidebar)
2. Click **Import**
3. Select: `Dashboard_Web_API.postman_environment.json`
4. Click **Import**

✅ Done! Environment ready with API token.

---

### Step 3: Test API (1 minute)

1. **Activate Environment:**
    - Top-right dropdown → Select "**E-Retribusi Dashboard Web API - Production**"

2. **Open Request:**
    - Collection → **1. Dashboard Data** → **Get Dashboard Data**

3. **Send Request:**
    - Click **Send** button

4. **See Result:**
    - Status: **200 OK** ✅
    - Response: Dashboard data in JSON

✅ **SUCCESS!** API is working!

---

## 🎉 You're Ready!

**No login required!** All 11 endpoints are ready to use:

```
✅ Get Dashboard Data
✅ Get OPD List
✅ Get Retribusi List
✅ Get Lokasi List
✅ Get Objek List
✅ Get Performance Detail
✅ Get Realisasi Detail
✅ Get Realisasi Lokasi by OPD
✅ Get Realisasi Retribusi by Lokasi
✅ Get Lokasi by OPD (Helper)
✅ Get Rincian by Retribusi (Helper)
```

---

## 💻 Integration Code

### JavaScript/Fetch

```javascript
const API_TOKEN =
    "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA";

async function getDashboard() {
    const response = await fetch(
        "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web",
        {
            headers: {
                "X-API-TOKEN": API_TOKEN,
                Accept: "application/json",
            },
        },
    );
    return await response.json();
}

// Usage
const data = await getDashboard();
console.log(data);
```

### cURL

```bash
curl -X GET \
  "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web" \
  -H "X-API-TOKEN: GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA" \
  -H "Accept: application/json"
```

### PHP

```php
<?php
$apiToken = "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA";
$url = "https://e-retribusi-trial.bapenda-lampungprov.com/api/dashboard-web";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-TOKEN: $apiToken",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

print_r($data);
?>
```

### Python

```python
import requests

API_TOKEN = "GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA"
BASE_URL = 'https://saibara.lampungprov.go.id/api'

headers = {
    "X-API-TOKEN": API_TOKEN,
    "Accept": "application/json"
}

response = requests.get(BASE_URL + '/dashboard-web', headers=headers)
response = requests.get(url, headers=headers)
data = response.json()

print(data)
```

---

## 📚 Next Steps

1. ✅ **Explore Endpoints** - Try all 11 endpoints in Postman
2. ✅ **Read Full Docs** - Check `POSTMAN_DASHBOARD_WEB_API.md`
3. ✅ **Customize Queries** - Edit query parameters
4. ✅ **Integrate to App** - Use code examples above
5. ✅ **Save Examples** - Save responses in Postman

---

## 🆘 Need Help?

- **Import Guide:** `POSTMAN_IMPORT_GUIDE.md`
- **Full API Docs:** `POSTMAN_DASHBOARD_WEB_API.md`
- **Documentation Index:** `README.md`

---

## 🔑 API Token

**Header Name:** `X-API-TOKEN`  
**Token Value:** `GVRf:nCpI1Y2cQu;D7=JyiPU34KrL>s5<@Wjok0xSNazXl?MgBwvEZbOqd9FtTmA`

**⚠️ Keep it secret!** Don't commit to public repos.

---

**That's it! Start building! 🚀**
