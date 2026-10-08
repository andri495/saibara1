<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SAIBARA - External Payment</title>
  <link rel="icon" href="/assets/icons/ic_ps_saibara.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' },
          }
        }
      }
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

  <main class="mx-auto max-w-6xl px-4 py-8">
    <!-- Hero -->
    <div class="mb-8 text-center">
      <h2 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-primary-600 to-primary-800 bg-clip-text text-transparent">Objek Retribusi</h2>
      @if($objects && $objects->count() > 0)
        @php $firstObj = $objects->first(); @endphp
        <p class="mt-2 text-slate-500">{{ $firstObj->opd->nama_opd ?? '' }} &mdash; {{ $firstObj->lokasi->nama_lokasi ?? '' }}</p>
      @else
        <p class="mt-2 text-slate-500">Tidak ada objek yang tersedia</p>
      @endif
    </div>

    @if($objects && $objects->count() > 0)
    <!-- Objects Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      @foreach($objects as $item)
        @php
          $imgSrc = '/assets/icons/ic_ps_saibara.png';
          if ($item->images) {
            $parsed = is_string($item->images) ? json_decode($item->images, true) : $item->images;
            if (is_array($parsed) && count($parsed) > 0) {
              $imgSrc = str_starts_with($parsed[0], 'http') ? $parsed[0] : '/storage/' . $parsed[0];
            }
          }
        @endphp
        <a href="/external/objects/{{ $item->id }}" class="group block overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200/60 hover:shadow-xl hover:ring-primary-300 hover:-translate-y-1 transition-all duration-200">
          <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-50 overflow-hidden relative">
            <img src="{{ $imgSrc }}" alt="{{ $item->sub_rincian_objek ?? '-' }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.src='/assets/icons/ic_ps_saibara.png'; this.classList.add('p-8','object-contain');">
          </div>
          <div class="p-4">
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-2 0a2 2 0 01-2 2H7"/></svg>
              {{ $item->opd->nama_opd ?? '-' }}
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-2">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              {{ $item->lokasi->nama_lokasi ?? '-' }}
            </div>
            <div class="font-semibold text-slate-800 leading-tight line-clamp-2">{{ $item->sub_rincian_objek ?? '-' }}</div>
            <div class="mt-3 flex items-center justify-between">
              <span class="text-lg font-bold text-primary-600">Rp {{ number_format($item->harga_objek ?? 0, 0, ',', '.') }}</span>
              <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                Pilih
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
              </span>
            </div>
          </div>
        </a>
      @endforeach
    </div>

    <!-- Pagination -->
    @if($objects->hasPages())
    <div class="mt-8 flex justify-center">
      <nav class="inline-flex items-center gap-1">
        {{-- Previous --}}
        @if($objects->onFirstPage())
          <span class="px-3 py-2 rounded-lg text-sm text-slate-300 cursor-not-allowed">&laquo; Prev</span>
        @else
          <a href="{{ $objects->previousPageUrl() }}" class="px-3 py-2 rounded-lg text-sm font-medium text-primary-600 hover:bg-primary-50 transition">&laquo; Prev</a>
        @endif

        {{-- Page Numbers --}}
        @foreach($objects->getUrlRange(1, $objects->lastPage()) as $page => $url)
          @if($page == $objects->currentPage())
            <span class="px-3 py-2 rounded-lg text-sm font-bold bg-primary-600 text-white shadow">{{ $page }}</span>
          @else
            <a href="{{ $url }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-primary-50 hover:text-primary-600 transition">{{ $page }}</a>
          @endif
        @endforeach

        {{-- Next --}}
        @if($objects->hasMorePages())
          <a href="{{ $objects->nextPageUrl() }}" class="px-3 py-2 rounded-lg text-sm font-medium text-primary-600 hover:bg-primary-50 transition">Next &raquo;</a>
        @else
          <span class="px-3 py-2 rounded-lg text-sm text-slate-300 cursor-not-allowed">Next &raquo;</span>
        @endif
      </nav>
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="text-center py-16">
      <div class="text-6xl mb-4 opacity-30">&#128269;</div>
      <p class="text-slate-400 text-lg">Tidak ada objek ditemukan</p>
      <p class="text-slate-400 text-sm mt-1">Pastikan URL sudah benar: /external-payments/{opd_id}/{lokasi_id}</p>
    </div>
    @endif
  </main>
</body>
</html>
