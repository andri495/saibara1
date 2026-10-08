{{-- 
    Navbar Component
    Navigation bar untuk authenticated users
--}}
<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo & Brand -->
        <div class="navbar-brand">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <img src="{{ asset('assets/icons/ic_ps_saibara.png') }}" alt="Logo" class="brand-logo">
                <span class="brand-text">SAIBARA</span>
            </a>
        </div>

        <!-- Navigation Menu -->
        <ul class="navbar-menu">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                    📊 Dashboard
                </a>
            </li>

            <!-- Master Data Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle {{ request()->routeIs('master.*') ? 'active' : '' }}">
                    🗂️ Master Data
                </a>
                <ul class="dropdown-menu dropdown-wide">
                    <li class="dropdown-header">Retribusi</li>
                    <li><a href="{{ route('master.retribusi.index') }}" class="dropdown-item">📋 Retribusi</a></li>
                    <li><a href="{{ route('master.rincian-retribusi.index') }}" class="dropdown-item">📝 Rincian Retribusi</a></li>
                    <li><a href="{{ route('master.sub-rincian-retribusi.index') }}" class="dropdown-item">📄 Sub Rincian Retribusi</a></li>
                    <li class="dropdown-divider"></li>
                    <li class="dropdown-header">Organisasi</li>
                    <li><a href="{{ route('master.opd.index') }}" class="dropdown-item">🏢 OPD</a></li>
                    <li><a href="{{ route('master.lokasi-objek.index') }}" class="dropdown-item">📍 Lokasi Objek</a></li>
                    <li><a href="{{ route('master.chief.index') }}" class="dropdown-item">👔 Kepala Dinas</a></li>
                    <li class="dropdown-divider"></li>
                    <li class="dropdown-header">Objek &amp; Layanan</li>
                    <li><a href="{{ route('master.objek.index') }}" class="dropdown-item">🏛️ Master Objek</a></li>
                    <li><a href="{{ route('master.tpj.index') }}" class="dropdown-item">🏪 TPJ</a></li>
                    <li><a href="{{ route('master.satuan.index') }}" class="dropdown-item">📏 Satuan</a></li>
                    <li class="dropdown-divider"></li>
                    <li class="dropdown-header">Pembayaran &amp; Sistem</li>
                    <li><a href="{{ route('master.payment.index') }}" class="dropdown-item">💳 Tipe Pembayaran</a></li>
                    <li><a href="{{ route('master.payment-status.index') }}" class="dropdown-item">✅ Status Pembayaran</a></li>
                    <li><a href="{{ route('master.document.index') }}" class="dropdown-item">📄 Dokumen</a></li>
                    <li><a href="{{ route('master.role.index') }}" class="dropdown-item">🔑 Role</a></li>
                </ul>
            </li>

            <!-- Keuangan Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle {{ request()->routeIs('revenue*') || request()->routeIs('retribution-target*') ? 'active' : '' }}">
                    💰 Keuangan
                </a>
                <ul class="dropdown-menu">
                    <li class="dropdown-header">Pendapatan</li>
                    <li><a href="{{ route('revenue.index') }}" class="dropdown-item">💵 Data Pendapatan (Agregat)</a></li>
                    <li class="dropdown-divider"></li>
                    <li class="dropdown-header">Target Retribusi</li>
                    <li><a href="{{ route('retribution-target.index') }}" class="dropdown-item">🎯 Daftar Target</a></li>
                    <li><a href="{{ route('retribution-target.create') }}" class="dropdown-item">➕ Tambah Target</a></li>
                </ul>
            </li>
        </ul>

        <!-- User Menu -->
        <div class="navbar-user">
            <div class="user-dropdown">
                <button class="user-toggle" id="userMenuToggle">
                    <span class="user-avatar">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    <span class="user-name">{{ Auth::user()->name }}</span>
                </button>
                <ul class="user-menu" id="userMenu">
                    <li class="user-info">
                        <strong>{{ Auth::user()->name }}</strong>
                        <small>{{ Auth::user()->email }}</small>
                    </li>
                    <li class="menu-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST" id="logoutForm">
                            @csrf
                            <button type="submit" class="logout-btn">
                                <span class="icon">🚪</span> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span class="hamburger"></span>
        </button>
    </div>
</nav>
