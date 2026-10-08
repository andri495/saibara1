<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAIBARA - {{ $object->sub_rincian_objek ?? 'Objek' }}</title>
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

  <!-- Error Message Box -->
  @if($errors->any())
  <div class="mx-auto max-w-3xl px-4 mt-4">
    <div class="rounded-lg p-4 text-sm bg-red-50 text-red-800 border border-red-200 flex items-start gap-3">
      <span class="text-lg leading-none mt-0.5">&#9888;</span>
      <div>
        <div class="font-semibold">Terjadi Kesalahan</div>
        @foreach($errors->all() as $err)
          <div class="mt-1">{{ $err }}</div>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <main class="mx-auto max-w-3xl px-4 py-8">
    <a href="/external-payments" class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Kembali ke Daftar
    </a>

    <div class="mt-4 overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-slate-200/60">
      <?php
        $img = '';
        $rawImages = $object->images ?? null;
        if ($rawImages) {
            $decoded = json_decode($rawImages, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $img = str_starts_with($decoded[0], 'http') ? $decoded[0] : asset('storage/' . $decoded[0]);
            } elseif (is_string($rawImages) && !empty($rawImages)) {
                $img = str_starts_with($rawImages, 'http') ? $rawImages : asset('storage/' . $rawImages);
            }
        }
      ?>
      <div class="aspect-video bg-gradient-to-br from-slate-100 to-slate-50 overflow-hidden">
        <img src="{{ $img ?: '/assets/icons/ic_ps_saibara.png' }}" alt="{{ $object->sub_rincian_objek ?? 'Objek' }}" class="h-full w-full object-cover {{ $img ? '' : 'p-16 object-contain' }}" onerror="this.src='/assets/icons/ic_ps_saibara.png'; this.classList.add('p-16','object-contain'); this.classList.remove('object-cover');">
      </div>
      <div class="p-6 sm:p-8">
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0a2 2 0 01-2 2H7"/></svg>
          {{ $object->opd->nama_opd ?? '-' }}
          <span class="mx-1">|</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          {{ $object->lokasi->nama_lokasi ?? '-' }}
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-2">{{ $object->sub_rincian_objek ?? 'Objek' }}</h2>
        <div class="mt-4 inline-flex items-center gap-1 rounded-xl bg-primary-50 px-4 py-2">
          <span class="text-sm text-primary-500">Harga satuan</span>
          <span class="text-2xl font-bold text-primary-700 ml-2">Rp {{ number_format($object->harga_objek ?? 0, 0, ',', '.') }}</span>
        </div>
      </div>
    </div>

    <form method="POST" action="/external/transactions" class="mt-6 rounded-2xl bg-white p-6 sm:p-8 shadow-lg ring-1 ring-slate-200/60">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="object_id" value="{{ $object->id }}" />
      <h3 class="text-lg font-bold text-slate-800 mb-4">Checkout</h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Jumlah</label>
          <input name="quantity" id="qtyInput" type="number" value="1" min="1" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:bg-white transition" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Estimasi Total</label>
          <div id="totalPreview" class="flex items-center h-[46px] rounded-xl bg-primary-50 px-4 text-lg font-bold text-primary-700">
            Rp {{ number_format($object->harga_objek ?? 0, 0, ',', '.') }}
          </div>
        </div>
      </div>

      <h4 class="text-sm font-bold text-slate-700 mb-3">Pilih Metode Pembayaran</h4>
      <div class="grid grid-cols-3 gap-3 mb-6">
        <label class="cursor-pointer" data-method="1">
          <input type="radio" name="masterTipePembayaranId" value="1" class="peer hidden" checked />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#128242;</div>
            <div class="text-sm font-semibold text-slate-700">QRIS</div>
          </div>
        </label>
        <label class="cursor-pointer" data-method="2">
          <input type="radio" name="masterTipePembayaranId" value="2" class="peer hidden" />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#127974;</div>
            <div class="text-sm font-semibold text-slate-700">Virtual Account</div>
          </div>
        </label>
        <label class="cursor-pointer" data-method="3">
          <input type="radio" name="masterTipePembayaranId" value="3" class="peer hidden" />
          <div class="peer-checked:ring-2 peer-checked:ring-primary-500 peer-checked:bg-primary-50 rounded-xl border border-slate-200 p-4 text-center transition-all hover:shadow">
            <div class="text-2xl mb-1">&#128179;</div>
            <div class="text-sm font-semibold text-slate-700">Kode Bayar</div>
          </div>
        </label>
      </div>

      <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-md hover:shadow-lg hover:from-emerald-600 hover:to-emerald-700 transition-all active:scale-[0.98]">
        Bayar Sekarang
        <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
      </button>
    </form>
  </main>

  <script>
    const qtyInput = document.getElementById('qtyInput');
    const totalPreview = document.getElementById('totalPreview');
    const unitPrice = {{ $object->harga_objek ?? 0 }};

    const labelQris = document.querySelectorAll('[data-method="1"]')[0];
    const labelVa   = document.querySelectorAll('[data-method="2"]')[0];
    const labelKode = document.querySelectorAll('[data-method="3"]')[0];
    const radioQris = labelQris.querySelector('input');
    const radioVa   = labelVa.querySelector('input');
    const radioKode = labelKode.querySelector('input');

    function updatePaymentMethods(total) {
      const hideVaKode = total < 10000;
      const hideQris   = total > 10000000;

      labelVa.classList.toggle('hidden', hideVaKode);
      labelKode.classList.toggle('hidden', hideVaKode);
      labelQris.classList.toggle('hidden', hideQris);

      if (hideVaKode) {
        if (radioVa.checked || radioKode.checked) { radioVa.checked = false; radioKode.checked = false; radioQris.checked = true; }
      }
      if (hideQris) {
        if (radioQris.checked) { radioQris.checked = false; radioVa.checked = true; }
      }
    }

    qtyInput.addEventListener('input', () => {
      const qty = Math.max(1, parseInt(qtyInput.value) || 1);
      const total = unitPrice * qty;
      totalPreview.textContent = 'Rp ' + total.toLocaleString('id-ID');
      updatePaymentMethods(total);
    });

    updatePaymentMethods(unitPrice * 1);
  </script>
</body>
</html>
