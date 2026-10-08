<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chief Information</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
      background-color: white;
      width: 100%;
      max-width: 600px;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
      0% {
        transform: translateY(50px);
        opacity: 0;
      }
      100% {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .header {
      text-align: center;
      margin-bottom: 35px;
    }

    .avatar {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 45px;
      color: white;
      font-weight: bold;
      animation: scaleIn 0.5s ease-out;
      box-shadow: 0 8px 25px rgba(13, 110, 253, 0.4);
    }

    @keyframes scaleIn {
      0% {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
      }
      50% {
        transform: scale(1.1) rotate(10deg);
      }
      100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
      }
    }

    h1 {
      font-size: 28px;
      color: #333;
      margin-bottom: 8px;
    }

    .subtitle {
      color: #999;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .info-grid {
      display: grid;
      gap: 20px;
      margin-bottom: 30px;
    }

    .info-card {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 20px;
      border-radius: 12px;
      border-left: 4px solid #0d6efd;
      transition: all 0.3s ease;
      animation: fadeIn 0.6s ease-out backwards;
    }

    .info-card:nth-child(1) { animation-delay: 0.1s; }
    .info-card:nth-child(2) { animation-delay: 0.2s; }
    .info-card:nth-child(3) { animation-delay: 0.3s; }
    .info-card:nth-child(4) { animation-delay: 0.4s; }
    .info-card:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeIn {
      0% {
        opacity: 0;
        transform: translateX(-20px);
      }
      100% {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .info-card:hover {
      transform: translateX(5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .info-label {
      font-size: 12px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .info-value {
      font-size: 16px;
      color: #333;
      font-weight: 500;
      word-wrap: break-word;
    }

    .nested-section {
      margin-top: 30px;
      padding-top: 30px;
      border-top: 2px solid #e0e0e0;
    }

    .section-title {
      font-size: 18px;
      color: #0d6efd;
      margin-bottom: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      border-radius: 2px;
    }

    .badge {
      display: inline-block;
      padding: 6px 14px;
      background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
      color: white;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      margin-top: 5px;
    }

    .timestamp {
      font-size: 13px;
      color: #999;
      font-style: italic;
    }

    @media (max-width: 600px) {
      .container {
        padding: 30px 20px;
      }

      h1 {
        font-size: 24px;
      }

      .avatar {
        width: 80px;
        height: 80px;
        font-size: 35px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="avatar">{{ strtoupper(substr($chief->chief_name ?? 'C', 0, 1)) }}</div>
      <h1>{{ $chief->chief_name ?? 'N/A' }}</h1>
      <p class="subtitle">Informasi Penandatangan</p>
    </div>

    <div class="info-grid">

      <div class="info-card">
        <div class="info-label">NIP (Nomor Induk Pegawai)</div>
        <div class="info-value">{{ $chief->chief_nip ?? 'N/A' }}</div>
      </div>

      <div class="info-card">
        <div class="info-label">Role</div>
        <div class="info-value">
          {{ $chief->masterRole->nama_role ?? 'N/A' }}
        </div>
      </div>
    </div>

    <div class="nested-section">
      <div class="section-title">Informasi OPD</div>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-label">Nama OPD</div>
          <div class="info-value">{{ $chief->masterOpd->nama_opd ?? 'N/A' }}</div>
        </div>

      </div>
    </div>

    <div class="nested-section">
      <div class="section-title">Timestamps</div>
      <div class="info-grid">
        <div class="info-card">
          <div class="info-label">Created At</div>
          <div class="info-value timestamp">{{ $chief->created_at ? $chief->created_at->format('F d, Y - H:i:s') : 'N/A' }}</div>
        </div>

        <div class="info-card">
          <div class="info-label">Last Updated</div>
          <div class="info-value timestamp">{{ $chief->updated_at ? $chief->updated_at->format('F d, Y - H:i:s') : 'N/A' }}</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>