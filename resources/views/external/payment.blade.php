<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAIBARA - Pembayaran</title>
  <link rel="icon" href="/assets/icons/ic_ps_saibara.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' } } } }
    }
  </script>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-indigo-50 text-slate-900 min-h-screen">

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-slate-200">
    <div class="mx-auto max-w-6xl flex items-center gap-3 px-4 py-3">
      <img src="/assets/icons/ic_ps_saibara.png" alt="SAIBARA" class="h-9 w-9 rounded-lg shadow-sm">
      <div>
        <h1 class="text-lg font-bold text-primary-700 leading-tight">SAIBARA</h1>
        <p class="text-xs text-slate-400">External Payment Portal</p>
      </div>
    </div>
  </header>

  @php
    $statusId = $transaksi->master_status_pembayaran_id ?? 1;
    $statusMap = [
      1 => ['label' => 'MENUNGGU',           'color' => 'slate',   'icon' => 'clock'],
      2 => ['label' => 'AKTIF',              'color' => 'blue',    'icon' => 'active'],
      3 => ['label' => 'TERBAYAR',           'color' => 'emerald', 'icon' => 'check'],
      4 => ['label' => 'DIBATALKAN PENGGUNA','color' => 'red',     'icon' => 'x'],
      5 => ['label' => 'DITOLAK OPD',        'color' => 'red',     'icon' => 'x'],
      6 => ['label' => 'KEDALUWARSA',        'color' => 'amber',   'icon' => 'clock'],
      7 => ['label' => 'SELESAI',            'color' => 'emerald', 'icon' => 'check'],
      8 => ['label' => 'ANGSURAN BERJALAN',  'color' => 'blue',    'icon' => 'active'],
      9 => ['label' => 'ANGSURAN SELESAI',   'color' => 'emerald', 'icon' => 'check'],
    ];
    $status = $statusMap[$statusId] ?? $statusMap[1];
    $canPay = in_array($statusId, [1, 2]);
    $isPaid = in_array($statusId, [3, 7, 9]);
    $hasPaymentData = $transaksi->qr_url || $transaksi->va || $transaksi->kode_bayar;
  @endphp

  <main class="mx-auto max-w-3xl px-4 py-8">
    <div class="flex items-center justify-between">
      <a href="/external-payments" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Daftar
      </a>
      <button onclick="window.location.reload()" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50 hover:text-primary-600 transition-all active:scale-95">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20.49 9A9 9 0 015.64 5.64L4 4m16 16l-1.64-1.64A9 9 0 014.51 15"/></svg>
        Refresh Status
      </button>
    </div>

    <!-- Status Banner -->
    @if($isPaid)
    <div class="mt-4 rounded-2xl bg-emerald-50 p-6 shadow-lg ring-1 ring-emerald-200 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2 class="text-2xl font-extrabold text-emerald-800">Pembayaran Berhasil</h2>
      <p class="text-sm text-emerald-600 mt-1">Transaksi telah lunas. Terima kasih!</p>
      @if($transaksi->payment_date)
      <p class="text-xs text-emerald-500 mt-2">Dibayar pada: {{ \Carbon\Carbon::parse($transaksi->payment_date)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
      @endif
    </div>
    @elseif(in_array($statusId, [4, 5]))
    <div class="mt-4 rounded-2xl bg-red-50 p-6 shadow-lg ring-1 ring-red-200 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </div>
      <h2 class="text-2xl font-extrabold text-red-800">Transaksi {{ $status['label'] }}</h2>
      <p class="text-sm text-red-600 mt-1">Transaksi ini tidak dapat dilanjutkan.</p>
    </div>
    @elseif($statusId == 6)
    <div class="mt-4 rounded-2xl bg-amber-50 p-6 shadow-lg ring-1 ring-amber-200 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-4">
        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h2 class="text-2xl font-extrabold text-amber-800">Transaksi Kedaluwarsa</h2>
      <p class="text-sm text-amber-600 mt-1">Transaksi ini telah melewati batas waktu pembayaran.</p>
    </div>
    @endif

    <!-- Transaction Summary -->
    <div class="mt-4 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-slate-200/60">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-extrabold text-slate-900">Detail Transaksi</h2>
        @php
          $badgeColors = [
            'slate'   => 'bg-slate-100 text-slate-700',
            'blue'    => 'bg-blue-100 text-blue-700',
            'emerald' => 'bg-emerald-100 text-emerald-700',
            'red'     => 'bg-red-100 text-red-700',
            'amber'   => 'bg-amber-100 text-amber-700',
          ];
          $badgeClass = $badgeColors[$status['color']] ?? 'bg-slate-100 text-slate-700';
        @endphp
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $badgeClass }}">
          {{ $status['label'] }}
        </span>
      </div>
      <div class="rounded-xl bg-slate-50 p-4 space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-slate-500">Reference No</span>
          <span class="font-mono font-semibold text-slate-800">{{ $transaksi->reference_no }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-slate-500">OPD</span>
          <span class="font-medium text-slate-800">{{ $transaksi->transaksiObject->first()->object->opd->nama_opd ?? '-' }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-slate-500">Lokasi</span>
          <span class="font-medium text-slate-800">{{ $transaksi->transaksiObject->first()->object->lokasi->nama_lokasi ?? '-' }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-slate-500">Objek</span>
          <span class="font-medium text-slate-800">{{ $transaksi->transaksiObject->first()->object->sub_rincian_objek ?? '-' }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-slate-500">Jumlah</span>
          <span class="font-medium text-slate-800">{{ $transaksi->transaksiObject->first()->tpj_quantity ?? '-' }}</span>
        </div>
        <div class="border-t border-slate-200 pt-2 mt-2 flex justify-between">
          <span class="text-sm font-semibold text-slate-600">Total</span>
          <span class="text-xl font-bold text-primary-700">Rp {{ number_format($transaksi->total_amount ?? 0, 0, ',', '.') }}</span>
        </div>
      </div>
    </div>

    @if($canPay && $hasPaymentData)
    <!-- Existing Payment Data (server-rendered on refresh) -->
    <div class="mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-emerald-200">
      <div class="text-center">
        @if($transaksi->qr_url)
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">QRIS</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Scan QR Code berikut untuk melakukan pembayaran</p>
        <div class="inline-block rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-inner">
          <img src="{{ $transaksi->qr_url }}" alt="QR Code" class="w-64 h-64 object-contain" />
        </div>
        <div class="mt-4">
          <p class="text-xs text-slate-400">Total: <span class="font-bold text-primary-700 text-base">Rp {{ number_format($transaksi->total_amount, 0, ',', '.') }}</span></p>
        </div>
        @elseif($transaksi->va)
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">Virtual Account</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Transfer ke nomor Virtual Account berikut</p>
        <div class="inline-block rounded-2xl bg-slate-50 border border-slate-200 px-8 py-5">
          <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Nomor VA</p>
          <p class="text-3xl font-bold tracking-widest text-slate-900 font-mono">{{ $transaksi->va }}</p>
        </div>
        <div class="mt-3">
          <button onclick="navigator.clipboard.writeText('{{ $transaksi->va }}').then(()=>alert('Berhasil disalin!'))" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
            Salin Nomor VA
          </button>
        </div>
        <div class="mt-4">
          <p class="text-xs text-slate-400">Total: <span class="font-bold text-primary-700 text-base">Rp {{ number_format($transaksi->total_amount, 0, ',', '.') }}</span></p>
        </div>
        @elseif($transaksi->kode_bayar)
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">Kode Bayar</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Gunakan kode berikut untuk pembayaran di minimarket</p>
        <div class="inline-block rounded-2xl bg-slate-50 border border-slate-200 px-8 py-5">
          <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Kode Bayar</p>
          <p class="text-3xl font-bold tracking-widest text-slate-900 font-mono">{{ $transaksi->kode_bayar }}</p>
        </div>
        <div class="mt-3">
          <button onclick="navigator.clipboard.writeText('{{ $transaksi->kode_bayar }}').then(()=>alert('Berhasil disalin!'))" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
            Salin Kode Bayar
          </button>
        </div>
        <div class="mt-4">
          <p class="text-xs text-slate-400">Total: <span class="font-bold text-primary-700 text-base">Rp {{ number_format($transaksi->total_amount, 0, ',', '.') }}</span></p>
        </div>
        @endif
        <button onclick="window.location.reload()" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20.49 9A9 9 0 015.64 5.64L4 4m16 16l-1.64-1.64A9 9 0 014.51 15"/></svg>
          Sudah bayar? Cek status
        </button>
      </div>
    </div>
    @elseif($canPay)
    <!-- Payment Method Selection (hidden after payment generated) -->
    <form id="payForm" method="POST" action="/external/payment/transaction" class="mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-slate-200/60">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="reference_no" value="{{ $transaksi->reference_no }}" />
      <h3 class="text-lg font-bold text-slate-800 mb-4">Metode Pembayaran</h3>

      <div class="grid grid-cols-3 gap-3 mb-6">
        <label class="cursor-pointer" data-method="1">
          <input type="radio" name="masterTipePembayaranId" value="1" class="peer hidden" {{ ($transaksi->master_tipe_pembayaran_id ?? 1) == 1 ? 'checked' : '' }} />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#128242;</div>
            <div class="text-sm font-semibold text-slate-700">QRIS</div>
          </div>
        </label>
        <label class="cursor-pointer" data-method="2">
          <input type="radio" name="masterTipePembayaranId" value="2" class="peer hidden" {{ ($transaksi->master_tipe_pembayaran_id ?? 0) == 2 ? 'checked' : '' }} />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#127974;</div>
            <div class="text-sm font-semibold text-slate-700">Virtual Account</div>
          </div>
        </label>
        <label class="cursor-pointer" data-method="3">
          <input type="radio" name="masterTipePembayaranId" value="3" class="peer hidden" {{ ($transaksi->master_tipe_pembayaran_id ?? 0) == 3 ? 'checked' : '' }} />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#128179;</div>
            <div class="text-sm font-semibold text-slate-700">Kode Bayar</div>
          </div>
        </label>
      </div>

      <button type="submit" id="payBtn" class="w-full rounded-xl bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-3.5 text-sm font-semibold text-white shadow-md hover:shadow-lg hover:from-primary-700 hover:to-primary-800 transition-all active:scale-[0.98]">
        <span id="payLabel">Generate Pembayaran</span>
        <span id="paySpinner" class="hidden">
          <svg class="inline w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="opacity-75"/></svg>
          Memproses...
        </span>
      </button>
    </form>

    <!-- Loading overlay -->
    <div id="loadingOverlay" class="hidden mt-6 rounded-2xl bg-white p-8 shadow-lg ring-1 ring-slate-200/60 text-center">
      <svg class="mx-auto w-10 h-10 animate-spin text-primary-500 mb-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="opacity-75"/></svg>
      <p class="text-sm text-slate-500">Memproses pembayaran...</p>
    </div>

    <!-- Error Panel -->
    <div id="errorPanel" class="hidden mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-red-200">
      <div class="flex items-center gap-3 mb-3">
        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100">
          <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-red-800">Pembayaran Gagal</h3>
          <p id="errorMsg" class="text-sm text-red-600 mt-0.5"></p>
        </div>
      </div>
      <p class="text-sm text-slate-500">Silakan coba lagi atau pilih metode pembayaran lain.</p>
    </div>

    <!-- QRIS Result -->
    <div id="resultQris" class="hidden mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-emerald-200">
      <div class="text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">QRIS Berhasil Dibuat</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Scan QR Code berikut untuk melakukan pembayaran</p>
        <div class="inline-block rounded-2xl border-2 border-slate-200 bg-white p-4 shadow-inner">
          <img id="qrisImage" src="" alt="QR Code" class="w-64 h-64 object-contain" />
        </div>
        <div class="mt-4 space-y-1">
          <p class="text-xs text-slate-400">Total: <span id="qrisTotal" class="font-bold text-primary-700 text-base"></span></p>
        </div>
        <button onclick="window.location.reload()" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20.49 9A9 9 0 015.64 5.64L4 4m16 16l-1.64-1.64A9 9 0 014.51 15"/></svg>
          Sudah bayar? Cek status
        </button>
      </div>
    </div>

    <!-- VA Result -->
    <div id="resultVa" class="hidden mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-emerald-200">
      <div class="text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">Virtual Account Berhasil Dibuat</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Transfer ke nomor Virtual Account berikut</p>
        <div class="inline-block rounded-2xl bg-slate-50 border border-slate-200 px-8 py-5">
          <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Nomor VA</p>
          <p id="vaNumber" class="text-3xl font-bold tracking-widest text-slate-900 font-mono"></p>
        </div>
        <div class="mt-3">
          <button onclick="copyToClipboard(document.getElementById('vaNumber').textContent)" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
            Salin Nomor VA
          </button>
        </div>
        <div class="mt-4 space-y-1">
          <p class="text-xs text-slate-400">Total: <span id="vaTotal" class="font-bold text-primary-700 text-base"></span></p>
        </div>
        <button onclick="window.location.reload()" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20.49 9A9 9 0 015.64 5.64L4 4m16 16l-1.64-1.64A9 9 0 014.51 15"/></svg>
          Sudah bayar? Cek status
        </button>
      </div>
    </div>

    <!-- Kode Bayar Result -->
    <div id="resultKodeBayar" class="hidden mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-emerald-200">
      <div class="text-center">
        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-1.5 mb-4">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span class="text-sm font-semibold text-emerald-700">Kode Bayar Berhasil Dibuat</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Gunakan kode berikut untuk pembayaran di minimarket</p>
        <div class="inline-block rounded-2xl bg-slate-50 border border-slate-200 px-8 py-5">
          <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Kode Bayar</p>
          <p id="kodeBayarNumber" class="text-3xl font-bold tracking-widest text-slate-900 font-mono"></p>
        </div>
        <div class="mt-3">
          <button onclick="copyToClipboard(document.getElementById('kodeBayarNumber').textContent)" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>
            Salin Kode Bayar
          </button>
        </div>
        <div class="mt-4 space-y-1">
          <p class="text-xs text-slate-400">Total: <span id="kodeBayarTotal" class="font-bold text-primary-700 text-base"></span></p>
        </div>
        <button onclick="window.location.reload()" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20.49 9A9 9 0 015.64 5.64L4 4m16 16l-1.64-1.64A9 9 0 014.51 15"/></svg>
          Sudah bayar? Cek status
        </button>
      </div>
    </div>
    @endif
  </main>

  @if($canPay && !$hasPaymentData)
  <script>
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text.trim()).then(() => {
        alert('Berhasil disalin!');
      });
    }

    function formatRupiah(amount) {
      return 'Rp ' + Number(amount).toLocaleString('id-ID');
    }

    const form = document.getElementById('payForm');
    const payBtn = document.getElementById('payBtn');
    const payLabel = document.getElementById('payLabel');
    const paySpinner = document.getElementById('paySpinner');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const errorPanel = document.getElementById('errorPanel');

    // Hide payment methods based on total amount
    (function() {
      const total = {{ $transaksi->total_amount ?? 0 }};
      const labelQris = document.querySelector('[data-method="1"]');
      const labelVa   = document.querySelector('[data-method="2"]');
      const labelKode = document.querySelector('[data-method="3"]');
      const radioQris = labelQris.querySelector('input');
      const radioVa   = labelVa.querySelector('input');
      const radioKode = labelKode.querySelector('input');

      if (total < 10000) {
        labelVa.classList.add('hidden');
        labelKode.classList.add('hidden');
        if (radioVa.checked || radioKode.checked) { radioVa.checked = false; radioKode.checked = false; radioQris.checked = true; }
      }
      if (total > 10000000) {
        labelQris.classList.add('hidden');
        if (radioQris.checked) { radioQris.checked = false; radioVa.checked = true; }
      }
    })();

    function showError(msg) {
      document.getElementById('errorMsg').textContent = msg;
      errorPanel.classList.remove('hidden');
    }

    function showPaymentResult(json, method) {
      const data = json.data || {};
      form.classList.add('hidden');

      if (method === 1) {
        const qrUrl = data.qr_url || '';
        if (qrUrl) {
          document.getElementById('qrisImage').src = qrUrl;
          document.getElementById('qrisTotal').textContent = formatRupiah(data.total_amount || 0);
          document.getElementById('resultQris').classList.remove('hidden');
        } else {
          showError('QR Code tidak tersedia.');
        }
      } else if (method === 2) {
        const va = data.va || '';
        if (va) {
          document.getElementById('vaNumber').textContent = va;
          document.getElementById('vaTotal').textContent = formatRupiah(data.total_amount || 0);
          document.getElementById('resultVa').classList.remove('hidden');
        } else {
          showError('Nomor Virtual Account tidak tersedia.');
        }
      } else if (method === 3) {
        const kode = data.kode_bayar || '';
        if (kode) {
          document.getElementById('kodeBayarNumber').textContent = kode;
          document.getElementById('kodeBayarTotal').textContent = formatRupiah(data.total_amount || 0);
          document.getElementById('resultKodeBayar').classList.remove('hidden');
        } else {
          showError('Kode Bayar tidak tersedia.');
        }
      }
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      payLabel.classList.add('hidden');
      paySpinner.classList.remove('hidden');
      payBtn.disabled = true;
      loadingOverlay.classList.remove('hidden');
      errorPanel.classList.add('hidden');
      document.getElementById('resultQris').classList.add('hidden');
      document.getElementById('resultVa').classList.add('hidden');
      document.getElementById('resultKodeBayar').classList.add('hidden');

      const fd = new FormData(form);
      const method = Number(fd.get('masterTipePembayaranId'));
      const payload = {
        reference_no: fd.get('reference_no'),
        masterTipePembayaranId: method,
      };

      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': fd.get('_token') },
          body: JSON.stringify(payload)
        });
        const json = await res.json();

        loadingOverlay.classList.add('hidden');

        if (!res.ok || (json.status && json.status >= 400)) {
          showError(json.message || 'Gagal membuat pembayaran. Silakan coba lagi.');
          form.classList.remove('hidden');
          payLabel.classList.remove('hidden');
          paySpinner.classList.add('hidden');
          payBtn.disabled = false;
        } else {
          showPaymentResult(json, method);
        }
      } catch (err) {
        loadingOverlay.classList.add('hidden');
        showError('Terjadi kesalahan jaringan. Silakan coba lagi.');
        form.classList.remove('hidden');
        payLabel.classList.remove('hidden');
        paySpinner.classList.add('hidden');
        payBtn.disabled = false;
      }
    });

  </script>
  @endif
</body>
</html>
