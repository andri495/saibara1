<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SAIBARA') - Satu Aplikasi Belanja Retribusi Daerah</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/icons/ic_ps_saibara.png') }}">
    
    <!-- CSS Files - Terpisah untuk scalability -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Main Container -->
    <div id="app">
        <!-- Navigation Bar -->
        @auth
            @include('layouts.partials.navbar')
        @endauth

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Content -->
            @yield('content')
        </main>

        <!-- Footer -->
        @auth
            @include('layouts.partials.footer')
        @endauth
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" aria-live="polite" aria-atomic="false"></div>

    <!-- JavaScript Files - Terpisah untuk scalability -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/utils.js') }}"></script>

    <script>
        function showToast(message, type, duration) {
            type = type || 'info';
            duration = duration || 4000;

            var icons = {
                success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
                error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
                warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
            };

            var container = document.getElementById('toast-container');
            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.innerHTML =
                '<div class="toast-icon">' + (icons[type] || icons.info) + '</div>' +
                '<div class="toast-body"><p class="toast-message">' + message + '</p></div>' +
                '<button class="toast-close" onclick="dismissToast(this.parentElement)">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button>' +
                '<div class="toast-progress"><div class="toast-progress-bar" style="animation-duration:' + duration + 'ms"></div></div>';

            container.appendChild(toast);

            // Trigger animation
            requestAnimationFrame(function() {
                requestAnimationFrame(function() { toast.classList.add('toast-show'); });
            });

            // Auto dismiss
            var timer = setTimeout(function() { dismissToast(toast); }, duration);
            toast._timer = timer;
        }

        function dismissToast(toast) {
            if (!toast || toast._dismissed) return;
            toast._dismissed = true;
            clearTimeout(toast._timer);
            toast.classList.remove('toast-show');
            toast.classList.add('toast-hide');
            setTimeout(function() { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 400);
        }

        // Fire session toasts on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                showToast(@json(session('error')), '{{ session('toast_type', 'error') }}');
            @endif
            @if(session('warning'))
                showToast(@json(session('warning')), 'warning');
            @endif
            @if(session('info'))
                showToast(@json(session('info')), 'info');
            @endif
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast(@json($error), 'error');
                @endforeach
            @endif
        });
    </script>

    @stack('scripts')
</body>
</html>
