# 📊 Dashboard API Documentation - E-Retribusi

**Base URL:** `https://e-retribusi-trial.bapenda-lampungprov.com/api`  
**Authentication:** Bearer Token (JWT)  
**Last Updated:** April 7, 2026

---

## 🔐 Authentication

All endpoints require JWT authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your_jwt_token}
```

**Error Response (401 Unauthorized):**
```json
{
  "status": 401,
  "message": "You're not authenticated"
}
```

---

## 📋 Table of Contents

1. [Dashboard Overview](#1-dashboard-overview)
2. [Dashboard by OPD](#2-dashboard-by-opd)
3. [Retribution Targets (All OPDs)](#3-retribution-targets-all-opds)
4. [Retribution Targets by OPD](#4-retribution-targets-by-opd)
5. [Revenue by Lokasi for OPD](#5-revenue-by-lokasi-for-opd)
6. [Retribusi Breakdown for Lokasi](#6-retribusi-breakdown-for-lokasi)

---

## 1. Dashboard Overview

Get comprehensive dashboard data including summary metrics, top performers, and trends.

### Endpoint
```
GET /api/dashboard
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | string (YYYY-MM-DD) | No | Filter revenue from date |
| `end_date` | string (YYYY-MM-DD) | No | Filter revenue to date |

### Example Request
```dart
final response = await dio.get(
  '/api/dashboard',
  queryParameters: {
    'start_date': '2026-01-01',
    'end_date': '2026-03-31',
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved dashboard data",
  "data": {
    "summary": {
      "grand_total_transaksi": 1250,
      "grand_total_installment": 350,
      "total_semua_transaksi": 1600,
      "grand_total_pendapatan": "2500000000.00"
    },
    "status_breakdown": [
      {
        "status_id": 1,
        "status_name": "Lunas",
        "total": 1200
      },
      {
        "status_id": 2,
        "status_name": "Belum Lunas",
        "total": 400
      }
    ],
    "retribusi_breakdown": [
      {
        "retribusi_id": 1,
        "nama_retribusi": "Retribusi Jasa Umum",
        "total_pendapatan": "500000000.00",
        "total_transaksi": 300
      },
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "total_pendapatan": "1800000000.00",
        "total_transaksi": 1100
      },
      {
        "retribusi_id": 3,
        "nama_retribusi": "Retribusi Perizinan Tertentu",
        "total_pendapatan": "200000000.00",
        "total_transaksi": 200
      }
    ],
    "top_10_opd": [
      {
        "opd_id": 8,
        "nama_opd": "DINAS PERKEBUNAN",
        "total_pendapatan": "406409300.00"
      },
      {
        "opd_id": 5,
        "nama_opd": "DINAS KETAHANAN PANGAN TPH",
        "total_pendapatan": "322009500.39"
      }
    ],
    "revenue_trend": [
      {
        "tanggal": "2026-01-01",
        "total_pendapatan": "15000000.00",
        "total_transaksi": 45
      },
      {
        "tanggal": "2026-01-02",
        "total_pendapatan": "18000000.00",
        "total_transaksi": 52
      }
    ],
    "recent_activity": [
      {
        "id": 1234,
        "date": "2026-03-31",
        "opd_name": "DINAS PERKEBUNAN",
        "retribusi_name": "Retribusi Jasa Usaha",
        "total_revenue": "5000000.00",
        "total_transactions": 15,
        "is_installment": false
      }
    ],
    "master_data": {
      "total_opd": 22,
      "total_retribusi": 3,
      "total_lokasi": 150
    },
    "metadata": {
      "date_range": {
        "start_date": "2026-01-01",
        "end_date": "2026-03-31"
      },
      "available_years": [2026, 2025]
    }
  }
}
```

### Error Response (500)
```json
{
  "status": 500,
  "message": "Error retrieving dashboard data",
  "error": "Error message details"
}
```

---

## 2. Dashboard by OPD

Get detailed dashboard data for a specific OPD.

### Endpoint
```
GET /api/dashboard/opd/{opdId}
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `opdId` | integer | Yes | OPD ID |

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | string (YYYY-MM-DD) | No | Filter revenue from date |
| `end_date` | string (YYYY-MM-DD) | No | Filter revenue to date |

### Example Request
```dart
final response = await dio.get(
  '/api/dashboard/opd/8',
  queryParameters: {
    'start_date': '2026-01-01',
    'end_date': '2026-03-31',
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved dashboard data for OPD",
  "data": {
    "opd_info": {
      "id": 8,
      "nama_opd": "DINAS PERKEBUNAN"
    },
    "summary": {
      "grand_total_transaksi": 450,
      "grand_total_installment": 50,
      "total_semua_transaksi": 500,
      "total_pendapatan": "406409300.00"
    },
    "retribusi_breakdown": [
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "total_pendapatan": "406409300.00",
        "total_transaksi": 500
      }
    ],
    "lokasi_breakdown": [
      {
        "lokasi_id": 45,
        "nama_lokasi": "Pasar Tugu",
        "total_pendapatan": "150000000.00",
        "total_transaksi": 200
      }
    ],
    "revenue_trend": [
      {
        "tanggal": "2026-01-01",
        "total_pendapatan": "5000000.00",
        "total_transaksi": 15
      }
    ],
    "metadata": {
      "date_range": {
        "start_date": "2026-01-01",
        "end_date": "2026-03-31"
      }
    }
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

## 3. Retribution Targets (All OPDs)

Get target vs actual revenue comparison for all OPDs.

### Endpoint
```
GET /api/dashboard/targets
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `year` | integer | No | Target year (default: current year) |
| `start_date` | string (YYYY-MM-DD) | No | Filter revenue from date (must be in same year as `year`) |
| `end_date` | string (YYYY-MM-DD) | No | Filter revenue to date (must be in same year as `year`) |

### Important Notes
- **Targets are always for the entire year** specified in `year` parameter
- **Revenue can be filtered by date range** within that year using `start_date` and `end_date`
- Date range must be within the same year as the target year

### Example Request
```dart
// Example 1: Target 2026 vs Full Year Revenue 2026
final response1 = await dio.get(
  '/api/dashboard/targets',
  queryParameters: {
    'year': 2026,
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);

// Example 2: Target 2026 vs Q1 Revenue 2026
final response2 = await dio.get(
  '/api/dashboard/targets',
  queryParameters: {
    'year': 2026,
    'start_date': '2026-01-01',
    'end_date': '2026-03-31',
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved retribution targets with revenue comparison",
  "data": {
    "summary": {
      "grand_total_target": "3810769158.00",
      "grand_total_actual": "2023841660.40",
      "grand_achievement_percentage": "53.11",
      "grand_remaining_amount": "1786927497.60",
      "status": "below-target"
    },
    "comparison_per_opd": [
      {
        "opd_id": 8,
        "nama_opd": "DINAS PERKEBUNAN",
        "total_target": "47956777.00",
        "total_actual": "406409300.00",
        "achievement_percentage": "847.45",
        "remaining_amount": "0.00",
        "status": "achieved",
        "retribusi_breakdown": [
          {
            "retribusi_id": 1,
            "nama_retribusi": "Retribusi Jasa Umum",
            "target_amount": "0.00",
            "actual_revenue": "0.00",
            "achievement_percentage": "0.00",
            "remaining_amount": "0.00",
            "status": "below-target"
          },
          {
            "retribusi_id": 2,
            "nama_retribusi": "Retribusi Jasa Usaha",
            "target_amount": "47956777.00",
            "actual_revenue": "406409300.00",
            "achievement_percentage": "847.45",
            "remaining_amount": "0.00",
            "status": "achieved"
          },
          {
            "retribusi_id": 3,
            "nama_retribusi": "Retribusi Perizinan Tertentu",
            "target_amount": "0.00",
            "actual_revenue": "0.00",
            "achievement_percentage": "0.00",
            "remaining_amount": "0.00",
            "status": "below-target"
          }
        ]
      }
    ],
    "comparison_per_retribusi": [
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "total_target": "3783461967.00",
        "total_actual": "1965482389.44",
        "achievement_percentage": "519.49",
        "remaining_amount": "0.00",
        "status": "achieved",
        "jumlah_opd": 22
      }
    ],
    "metadata": {
      "target_year": 2026,
      "revenue_date_range": {
        "start_date": "2026-01-01",
        "end_date": "2026-03-31"
      },
      "total_target_records": 132,
      "note": "Targets are for the entire year. Revenue can be filtered by date range within that year."
    }
  }
}
```

### Error Response (400)
```json
{
  "status": 400,
  "message": "Start date must be in the same year as target year"
}
```

### Status Values
- `achieved`: Achievement >= 100%
- `on-track`: Achievement >= 75% and < 100%
- `below-target`: Achievement < 75%

---

## 4. Retribution Targets by OPD

Get target vs actual revenue comparison for a specific OPD.

### Endpoint
```
GET /api/dashboard/targets/opd/{opdId}
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `opdId` | integer | Yes | OPD ID |

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `year` | integer | No | Target year (default: current year) |
| `start_date` | string (YYYY-MM-DD) | No | Filter revenue from date (must be in same year as `year`) |
| `end_date` | string (YYYY-MM-DD) | No | Filter revenue to date (must be in same year as `year`) |

### Example Request
```dart
final response = await dio.get(
  '/api/dashboard/targets/opd/8',
  queryParameters: {
    'year': 2026,
    'start_date': '2026-01-01',
    'end_date': '2026-03-31',
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved retribution targets for OPD",
  "data": {
    "opd_info": {
      "id": 8,
      "nama_opd": "DINAS PERKEBUNAN"
    },
    "summary": {
      "total_target": "47956777.00",
      "total_actual": "406409300.00",
      "achievement_percentage": "847.45",
      "remaining_amount": "0.00",
      "status": "achieved"
    },
    "retribusi_breakdown": [
      {
        "retribusi_id": 1,
        "nama_retribusi": "Retribusi Jasa Umum",
        "target_amount": "0.00",
        "actual_revenue": "0.00",
        "achievement_percentage": "0.00",
        "remaining_amount": "0.00",
        "status": "below-target",
        "tahun": null
      },
      {
        "retribusi_id": 2,
        "nama_retribusi": "Retribusi Jasa Usaha",
        "target_amount": "47956777.00",
        "actual_revenue": "406409300.00",
        "achievement_percentage": "847.45",
        "remaining_amount": "0.00",
        "status": "achieved",
        "tahun": 2026
      },
      {
        "retribusi_id": 3,
        "nama_retribusi": "Retribusi Perizinan Tertentu",
        "target_amount": "0.00",
        "actual_revenue": "0.00",
        "achievement_percentage": "0.00",
        "remaining_amount": "0.00",
        "status": "below-target",
        "tahun": null
      }
    ],
    "metadata": {
      "target_year": 2026,
      "revenue_date_range": {
        "start_date": "2026-01-01",
        "end_date": "2026-03-31"
      },
      "note": "Targets are for the entire year. Revenue can be filtered by date range within that year."
    }
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

## 5. Revenue by Lokasi for OPD

Get revenue breakdown by location for a specific OPD with target comparison.

### Endpoint
```
GET /api/dashboard/opd/{opdId}/lokasi
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `opdId` | integer | Yes | OPD ID |

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `year` | integer | No | Year filter (default: current year) |

### Example Request
```dart
final response = await dio.get(
  '/api/dashboard/opd/8/lokasi',
  queryParameters: {
    'year': 2026,
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved revenue by lokasi for OPD",
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

## 6. Retribusi Breakdown for Lokasi

Get retribusi type breakdown for a specific location.

### Endpoint
```
GET /api/dashboard/lokasi/{lokasiId}/retribusi
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `lokasiId` | integer | Yes | Lokasi ID |

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `year` | integer | No | Year filter (default: current year) |
| `opd_id` | integer | No | Filter by OPD ID |

### Example Request
```dart
final response = await dio.get(
  '/api/dashboard/lokasi/45/retribusi',
  queryParameters: {
    'year': 2026,
    'opd_id': 8,
  },
  options: Options(
    headers: {'Authorization': 'Bearer $token'},
  ),
);
```

### Success Response (200)
```json
{
  "status": 200,
  "message": "Successfully retrieved retribusi breakdown for lokasi",
  "data": {
    "lokasi_info": {
      "id": 45,
      "nama_lokasi": "Pasar Tugu"
    },
    "year": 2026,
    "opd_id": 8,
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
  "message": "Lokasi not found"
}
```

---

## 🔧 Flutter Integration Examples

### 1. Setup Dio with Interceptor

```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  static const String baseUrl = 'https://e-retribusi-trial.bapenda-lampungprov.com/api';
  final Dio _dio;
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  ApiClient() : _dio = Dio(BaseOptions(baseUrl: baseUrl)) {
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _storage.read(key: 'jwt_token');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Handle unauthorized - redirect to login
        }
        return handler.next(error);
      },
    ));
  }

  Dio get dio => _dio;
}
```

### 2. Dashboard Service

```dart
class DashboardService {
  final ApiClient _apiClient;

  DashboardService(this._apiClient);

  Future<DashboardResponse> getDashboard({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final response = await _apiClient.dio.get(
        '/dashboard',
        queryParameters: {
          if (startDate != null) 'start_date': startDate,
          if (endDate != null) 'end_date': endDate,
        },
      );
      return DashboardResponse.fromJson(response.data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<TargetsResponse> getTargets({
    int? year,
    String? startDate,
    String? endDate,
  }) async {
    try {
      final response = await _apiClient.dio.get(
        '/dashboard/targets',
        queryParameters: {
          if (year != null) 'year': year,
          if (startDate != null) 'start_date': startDate,
          if (endDate != null) 'end_date': endDate,
        },
      );
      return TargetsResponse.fromJson(response.data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<OpdTargetsResponse> getTargetsByOpd({
    required int opdId,
    int? year,
    String? startDate,
    String? endDate,
  }) async {
    try {
      final response = await _apiClient.dio.get(
        '/dashboard/targets/opd/$opdId',
        queryParameters: {
          if (year != null) 'year': year,
          if (startDate != null) 'start_date': startDate,
          if (endDate != null) 'end_date': endDate,
        },
      );
      return OpdTargetsResponse.fromJson(response.data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  String _handleError(DioException e) {
    if (e.response != null) {
      final data = e.response!.data;
      return data['message'] ?? 'An error occurred';
    }
    return 'Network error';
  }
}
```

### 3. Model Classes

```dart
class DashboardResponse {
  final int status;
  final String message;
  final DashboardData data;

  DashboardResponse({
    required this.status,
    required this.message,
    required this.data,
  });

  factory DashboardResponse.fromJson(Map<String, dynamic> json) {
    return DashboardResponse(
      status: json['status'],
      message: json['message'],
      data: DashboardData.fromJson(json['data']),
    );
  }
}

class DashboardData {
  final Summary summary;
  final List<StatusBreakdown> statusBreakdown;
  final List<RetribusiBreakdown> retribusiBreakdown;
  final List<TopOpd> top10Opd;
  final List<RevenueTrend> revenueTrend;
  final MasterData masterData;

  DashboardData({
    required this.summary,
    required this.statusBreakdown,
    required this.retribusiBreakdown,
    required this.top10Opd,
    required this.revenueTrend,
    required this.masterData,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    return DashboardData(
      summary: Summary.fromJson(json['summary']),
      statusBreakdown: (json['status_breakdown'] as List)
          .map((e) => StatusBreakdown.fromJson(e))
          .toList(),
      retribusiBreakdown: (json['retribusi_breakdown'] as List)
          .map((e) => RetribusiBreakdown.fromJson(e))
          .toList(),
      top10Opd: (json['top_10_opd'] as List)
          .map((e) => TopOpd.fromJson(e))
          .toList(),
      revenueTrend: (json['revenue_trend'] as List)
          .map((e) => RevenueTrend.fromJson(e))
          .toList(),
      masterData: MasterData.fromJson(json['master_data']),
    );
  }
}

class Summary {
  final int grandTotalTransaksi;
  final int grandTotalInstallment;
  final int totalSemuaTransaksi;
  final double grandTotalPendapatan;

  Summary({
    required this.grandTotalTransaksi,
    required this.grandTotalInstallment,
    required this.totalSemuaTransaksi,
    required this.grandTotalPendapatan,
  });

  factory Summary.fromJson(Map<String, dynamic> json) {
    return Summary(
      grandTotalTransaksi: json['grand_total_transaksi'],
      grandTotalInstallment: json['grand_total_installment'],
      totalSemuaTransaksi: json['total_semua_transaksi'],
      grandTotalPendapatan: double.parse(json['grand_total_pendapatan']),
    );
  }
}

class TopOpd {
  final int opdId;
  final String namaOpd;
  final double totalPendapatan;

  TopOpd({
    required this.opdId,
    required this.namaOpd,
    required this.totalPendapatan,
  });

  factory TopOpd.fromJson(Map<String, dynamic> json) {
    return TopOpd(
      opdId: json['opd_id'],
      namaOpd: json['nama_opd'],
      totalPendapatan: double.parse(json['total_pendapatan']),
    );
  }
}

class TargetsResponse {
  final int status;
  final String message;
  final TargetsData data;

  TargetsResponse({
    required this.status,
    required this.message,
    required this.data,
  });

  factory TargetsResponse.fromJson(Map<String, dynamic> json) {
    return TargetsResponse(
      status: json['status'],
      message: json['message'],
      data: TargetsData.fromJson(json['data']),
    );
  }
}

class TargetsData {
  final TargetSummary summary;
  final List<OpdComparison> comparisonPerOpd;
  final List<RetribusiComparison> comparisonPerRetribusi;
  final Metadata metadata;

  TargetsData({
    required this.summary,
    required this.comparisonPerOpd,
    required this.comparisonPerRetribusi,
    required this.metadata,
  });

  factory TargetsData.fromJson(Map<String, dynamic> json) {
    return TargetsData(
      summary: TargetSummary.fromJson(json['summary']),
      comparisonPerOpd: (json['comparison_per_opd'] as List)
          .map((e) => OpdComparison.fromJson(e))
          .toList(),
      comparisonPerRetribusi: (json['comparison_per_retribusi'] as List)
          .map((e) => RetribusiComparison.fromJson(e))
          .toList(),
      metadata: Metadata.fromJson(json['metadata']),
    );
  }
}

class OpdComparison {
  final int opdId;
  final String namaOpd;
  final double totalTarget;
  final double totalActual;
  final double achievementPercentage;
  final double remainingAmount;
  final String status;
  final List<RetribusiBreakdownItem> retribusiBreakdown;

  OpdComparison({
    required this.opdId,
    required this.namaOpd,
    required this.totalTarget,
    required this.totalActual,
    required this.achievementPercentage,
    required this.remainingAmount,
    required this.status,
    required this.retribusiBreakdown,
  });

  factory OpdComparison.fromJson(Map<String, dynamic> json) {
    return OpdComparison(
      opdId: json['opd_id'],
      namaOpd: json['nama_opd'],
      totalTarget: double.parse(json['total_target']),
      totalActual: double.parse(json['total_actual']),
      achievementPercentage: double.parse(json['achievement_percentage']),
      remainingAmount: double.parse(json['remaining_amount']),
      status: json['status'],
      retribusiBreakdown: (json['retribusi_breakdown'] as List)
          .map((e) => RetribusiBreakdownItem.fromJson(e))
          .toList(),
    );
  }
}
```

### 4. Usage in Widget

```dart
class DashboardScreen extends StatefulWidget {
  @override
  _DashboardScreenState createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final DashboardService _dashboardService = DashboardService(ApiClient());
  late Future<DashboardResponse> _dashboardFuture;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  void _loadDashboard() {
    setState(() {
      _dashboardFuture = _dashboardService.getDashboard(
        startDate: '2026-01-01',
        endDate: '2026-03-31',
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Dashboard')),
      body: FutureBuilder<DashboardResponse>(
        future: _dashboardFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }

          final data = snapshot.data!.data;
          return RefreshIndicator(
            onRefresh: () async => _loadDashboard(),
            child: ListView(
              padding: EdgeInsets.all(16),
              children: [
                _buildSummaryCard(data.summary),
                SizedBox(height: 16),
                _buildTopOpdList(data.top10Opd),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildSummaryCard(Summary summary) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16),
        children: [
          Text('Total Pendapatan', style: TextStyle(fontSize: 16)),
          SizedBox(height: 8),
          Text(
            'Rp ${summary.grandTotalPendapatan.toStringAsFixed(0)}',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildTopOpdList(List<TopOpd> topOpds) {
    return Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: EdgeInsets.all(16),
            child: Text('Top 10 OPD', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ),
          ...topOpds.map((opd) => ListTile(
            title: Text(opd.namaOpd),
            trailing: Text('Rp ${opd.totalPendapatan.toStringAsFixed(0)}'),
          )),
        ],
      ),
    );
  }
}
```

---

## 📝 Notes

1. **All monetary values are returned as strings** to preserve precision. Parse them to `double` in Flutter.
2. **Date format:** Always use `YYYY-MM-DD` format for date parameters.
3. **Caching:** Dashboard data is cached on the server for 6 hours (21600 seconds).
4. **Rate Limiting:** Not currently implemented, but may be added in the future.
5. **Pagination:** Not available for dashboard endpoints. All data is returned in a single response.

---

## 🆘 Support

For API issues or questions, contact:
- **Email:** support@bapenda-lampungprov.com
- **Developer:** Backend Team

---

**Version:** 2.0  
**Last Updated:** April 7, 2026
