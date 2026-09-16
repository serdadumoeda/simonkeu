{{-- File: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - simonKeu BPVP Surakarta</title>
    
    <!-- Favicon / Logo Title Bar Icon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Google Fonts & Bootstrap CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 72px;
            --sidebar-bg: linear-gradient(180deg, #1e3c72 0%, #112244 100%);
            --sidebar-color: #cbd5e1;
            --sidebar-active-bg: rgba(255, 193, 7, 0.15);
            --sidebar-active-color: #ffc107;
            --topbar-height: 65px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            overflow-x: hidden;
        }

        /* Layout Container */
        #app-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Styling */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            transition: all 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        /* Subtle Eye-Catching Batik Parang Rusak Solo Overlay */
        #sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.06;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23ffc107' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3C!-- Diagonal Parang Rusak S-Curves --%3E%3Cpath d='M-20,20 Q10,-10 30,30 T80,40 Q100,50 130,80'/%3E%3Cpath d='M-20,80 Q10,50 30,90 T80,100 Q100,110 130,140'/%3E%3C!-- Mlinjon Diamonds --%3E%3Cpolygon points='30,15 38,30 30,45 22,30' fill='%23ffc107' fill-opacity='0.25'/%3E%3Cpolygon points='90,75 98,90 90,105 82,90' fill='%23ffc107' fill-opacity='0.25'/%3E%3C!-- Canting Accent Dots --%3E%3Ccircle cx='12' cy='35' r='2' fill='%23ffc107'/%3E%3Ccircle cx='48' cy='72' r='2' fill='%23ffc107'/%3E%3Ccircle cx='72' cy='25' r='2' fill='%23ffc107'/%3E%3Ccircle cx='108' cy='62' r='2' fill='%23ffc107'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 120px 120px;
        }

        /* Subtle Gold Accent Line on Right Edge of Sidebar */
        #sidebar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 2px;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255,193,7,0.4) 0%, rgba(255,193,7,0.08) 50%, rgba(255,193,7,0.4) 100%);
            pointer-events: none;
            z-index: 2;
        }

        .sidebar-brand, .sidebar-content, .sidebar-footer {
            position: relative;
            z-index: 1;
        }

        /* Collapsed Sidebar Mode (Desktop Icon-Only) */
        @media (min-width: 992px) {
            #app-wrapper.sidebar-collapsed #sidebar {
                width: var(--sidebar-collapsed-width);
            }

            #app-wrapper.sidebar-collapsed #sidebar .brand-title-text,
            #app-wrapper.sidebar-collapsed #sidebar .brand-badge,
            #app-wrapper.sidebar-collapsed #sidebar .sidebar-heading,
            #app-wrapper.sidebar-collapsed #sidebar .sidebar-text {
                display: none !important;
            }

            #app-wrapper.sidebar-collapsed #sidebar .sidebar-brand {
                padding: 0;
                justify-content: center;
            }

            #app-wrapper.sidebar-collapsed #sidebar .sidebar-brand .brand-logo-icon {
                margin-right: 0 !important;
            }

            #app-wrapper.sidebar-collapsed #sidebar .sidebar-link {
                justify-content: center;
                padding: 0.75rem 0;
            }

            #app-wrapper.sidebar-collapsed #sidebar .sidebar-link i {
                margin-right: 0;
                font-size: 1.35rem;
            }

            #app-wrapper.sidebar-collapsed #content-wrapper {
                margin-left: var(--sidebar-collapsed-width);
                width: calc(100% - var(--sidebar-collapsed-width));
            }
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .sidebar-brand i {
            color: #ffc107;
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 0.85rem;
        }

        .sidebar-heading {
            font-size: 0.725rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            padding: 0.75rem 1rem 0.35rem;
            margin-top: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--sidebar-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.925rem;
            border-radius: 10px;
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-link i {
            font-size: 1.15rem;
            margin-right: 0.85rem;
            transition: transform 0.2s ease;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }

        .sidebar-link:hover i {
            transform: scale(1.15);
        }

        .sidebar-link.active {
            color: var(--sidebar-active-color);
            background-color: var(--sidebar-active-bg);
            font-weight: 600;
            border-left: 4px solid #ffc107;
        }

        .sidebar-link.active i {
            color: #ffc107;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.15);
        }

        /* Page Content Area */
        #content-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: all 0.3s ease-in-out;
            width: calc(100% - var(--sidebar-width));
        }

        /* Top Bar Styling */
        .topbar {
            height: var(--topbar-height);
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            max-width: 125px;
        }

        @media (min-width: 576px) {
            .topbar-title {
                font-size: 1.2rem;
                max-width: 350px;
            }
        }

        /* Mobile Sidebar Overlay & Toggle */
        @media (max-width: 991.98px) {
            #sidebar {
                left: calc(-1 * var(--sidebar-width));
            }

            #sidebar.show {
                left: 0;
            }

            #content-wrapper {
                margin-left: 0;
                width: 100%;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(15, 23, 42, 0.5);
                backdrop-filter: blur(2px);
                z-index: 1035;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Card & Button Styles */
        .card-custom {
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out !important;
            overflow: hidden;
        }

        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .btn-custom-primary {
            background-color: #1e3c72;
            border-color: #1e3c72;
            color: #ffffff;
            transition: all 0.2s;
        }

        .btn-custom-primary:hover {
            background-color: #152b52;
            border-color: #152b52;
            color: #ffffff;
        }

        /* Stepper CSS */
        .stepper-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        .stepper-line {
            position: absolute;
            top: 24px;
            left: 5%;
            right: 5%;
            height: 4px;
            background-color: #e9ecef;
            z-index: 1;
        }

        .stepper-line-progress {
            position: absolute;
            top: 24px;
            left: 5%;
            height: 4px;
            background-color: #28a745;
            z-index: 2;
            transition: width 0.4s ease;
        }

        .stepper-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            z-index: 3;
            position: relative;
        }

        .stepper-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .stepper-item.completed .stepper-icon {
            background-color: #28a745;
            border-color: #28a745;
            color: #ffffff;
        }

        .stepper-item.active .stepper-icon {
            background-color: #007bff;
            border-color: #007bff;
            color: #ffffff;
            animation: pulse-blue 2s infinite;
        }

        .stepper-item.warning .stepper-icon {
            background-color: #dc3545;
            border-color: #dc3545;
            color: #ffffff;
        }

        .stepper-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
        }

        .stepper-item.completed .stepper-label {
            color: #28a745;
        }

        .stepper-item.active .stepper-label {
            color: #007bff;
            font-weight: 700;
        }

        .stepper-item.warning .stepper-label {
            color: #dc3545;
            font-weight: 700;
        }

        .stepper-sublabel {
            font-size: 11px;
            color: #adb5bd;
            margin-top: 2px;
        }

        @keyframes pulse-blue {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
            }
        }

        /* Green SPJ Stepper Steps */
        .stepper-item.completed-green .stepper-icon {
            background-color: #198754;
            border-color: #198754;
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);
        }

        .stepper-item.completed-green .stepper-label {
            color: #198754;
            font-weight: 700;
        }

        .stepper-item.completed-green .stepper-sublabel {
            color: #198754;
        }

        .stepper-item.active-green .stepper-icon {
            background-color: #20c997;
            border-color: #20c997;
            color: #ffffff;
            animation: pulse-green 2s infinite;
        }

        .stepper-item.active-green .stepper-label {
            color: #20c997;
            font-weight: 700;
        }

        .stepper-item.active-green .stepper-sublabel {
            color: #20c997;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
            }
        }
    </style>
</head>

<body>

    @php
        // Mengambil notifikasi khusus user yang login
        $unreadNotifications = collect([]);
        $allNotifications = collect([]);
        
        if (Auth::check()) {
            try {
                $unreadNotifications = \App\Models\Notification::where('user_id', Auth::id())
                    ->where('is_read', false)
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                $allNotifications = \App\Models\Notification::where('user_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            } catch (\Throwable $e) {
                $unreadNotifications = collect([]);
                $allNotifications = collect([]);
            }
        }
    @endphp

    <div id="app-wrapper">
        <!-- Sidebar Overlay (for Mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar Navigation -->
        <aside id="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand" title="simonKeu BPVP Surakarta">
                <div class="brand-logo-icon me-2.5 d-flex align-items-center">
                    <svg width="28" height="30" viewBox="0 0 76 82" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="12" y="12" width="52" height="64" rx="8" fill="#0f172a"/>
                        <rect x="23" y="27" width="24" height="4.5" rx="2" fill="#ffffff"/>
                        <rect x="23" y="36" width="30" height="4.5" rx="2" fill="#ffffff"/>
                        <rect x="23" y="45" width="18" height="4.5" rx="2" fill="#ffffff"/>
                        <circle cx="56" cy="14" r="7" fill="#ffb700"/>
                        <path d="M20 57 L34 70 L58 44" stroke="#38bdf8" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="fs-4 fw-extrabold brand-title-text" style="font-weight: 800; letter-spacing: -0.02em;">
                    <span class="text-white">simon</span><span style="color: #38bdf8;">Keu</span>
                </span>
                <span class="badge bg-warning text-dark ms-2 py-1 px-2 brand-badge" style="font-size: 10px !important;">BPVP</span>
            </a>

            <div class="sidebar-content">
                <div class="sidebar-heading">Menu Utama</div>
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                            <i class="bi bi-speedometer2"></i>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::is('pengajuan.*') ? 'active' : '' }}" href="{{ route('pengajuan.index') }}" title="Daftar Pengajuan">
                            <i class="bi bi-file-earmark-text"></i>
                            <span class="sidebar-text">Daftar Pengajuan</span>
                        </a>
                    </li>
                </ul>

                @if(Auth::check() && Auth::user()->role == 'Admin Keuangan')
                    <div class="sidebar-heading">Pengaturan Admin</div>
                    <ul class="sidebar-menu">
                        <li class="sidebar-item">
                            <a class="sidebar-link {{ Route::is('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" title="Kelola User">
                                <i class="bi bi-people-fill"></i>
                                <span class="sidebar-text">Kelola User</span>
                            </a>
                        </li>
                    </ul>
                @endif
            </div>
        </aside>

        <!-- Page Content Wrapper -->
        <div id="content-wrapper">
            <!-- Top Navbar -->
            <header class="topbar px-2 px-sm-4">
                <div class="d-flex align-items-center me-2 overflow-hidden">
                    <button class="btn btn-light border me-2 shadow-sm p-1.5 px-2" id="sidebarToggle" type="button" title="Sembunyikan / Tampilkan Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h1 class="topbar-title me-2 text-truncate" title="@yield('title', 'Dashboard')">@yield('title', 'Dashboard')</h1>
                    @php
                        $taHeader = request('tahun', date('Y'));
                    @endphp
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small fw-semibold text-nowrap">
                        <i class="bi bi-calendar-event me-1 d-none d-sm-inline"></i> TA {{ $taHeader == 'semua' ? 'Semua' : $taHeader }}
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                    <!-- Lonceng Notifikasi Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle position-relative p-2 border shadow-sm" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px;">
                            <i class="bi bi-bell-fill text-secondary fs-6"></i>
                            @if(count($unreadNotifications) > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="margin-top: 6px; margin-left: -6px;">
                                    {{ count($unreadNotifications) }}
                                </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-0" aria-labelledby="notificationDropdown" style="width: 300px; font-size: 13px;">
                            <li class="bg-primary text-white p-2.5 rounded-top fw-bold text-center">
                                Pemberitahuan
                            </li>
                            @forelse($allNotifications as $notif)
                                <li class="border-bottom p-2 bg-white">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="pe-2">
                                            <span class="fw-bold d-block text-dark mb-1">
                                                @if(!$notif->is_read)
                                                    <span class="badge bg-danger rounded-circle p-1 me-1" style="width: 6px; height: 6px; display: inline-block;"></span>
                                                @endif
                                                {{ $notif->title }}
                                            </span>
                                            <span class="text-muted d-block small" style="line-height: 1.3;">{{ $notif->message }}</span>
                                            <span class="text-secondary d-block mt-1" style="font-size: 10px;">{{ $notif->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if(!$notif->is_read)
                                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" title="Tandai dibaca">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="p-3 text-center text-muted">
                                    <i class="bi bi-bell-slash fs-4 d-block mb-1"></i> Tidak ada pemberitahuan baru
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <!-- User Profile Dropdown Menu -->
                    @if(Auth::check())
                        <div class="dropdown">
                            <button class="btn btn-light border rounded-pill p-1.5 px-sm-3 py-sm-1.5 d-flex align-items-center gap-1.5 gap-sm-2 dropdown-toggle shadow-sm" type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 12px;">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span class="fw-semibold text-dark small me-1 d-none d-md-inline">{{ Auth::user()->name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2" aria-labelledby="userProfileDropdown" style="min-width: 240px; font-size: 13px;">
                                <!-- User Info Header in Dropdown -->
                                <li class="px-3 py-2 border-bottom mb-1 bg-light bg-opacity-50">
                                    <div class="fw-bold text-dark text-truncate">{{ Auth::user()->name }}</div>
                                    <div class="text-muted small" style="font-size: 11px;">{{ Auth::user()->email }}</div>
                                    @php
                                        $badgeRoleClass = match(Auth::user()->role) {
                                            'Kepala Balai' => 'bg-danger text-white border border-danger',
                                            'Admin Keuangan' => 'bg-purple text-white',
                                            default => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeRoleClass }} px-2 py-0.5 rounded-pill mt-1" style="font-size: 10px;">
                                        {{ Auth::user()->role }} @if(Auth::user()->bidang && Auth::user()->bidang != 'None') ({{ Auth::user()->bidang }}) @endif
                                    </span>
                                </li>
                                
                                <!-- Account Settings Item -->
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center text-dark" href="#" data-bs-toggle="modal" data-bs-target="#accountSettingsModal">
                                        <i class="bi bi-gear me-2 text-primary fs-6"></i>
                                        <span class="fw-medium">Pengaturan Akun</span>
                                    </a>
                                </li>
                                
                                <li><hr class="dropdown-divider my-1"></li>
                                
                                <!-- Logout Item -->
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="w-100">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 d-flex align-items-center text-danger">
                                            <i class="bi bi-box-arrow-right me-2 fs-6"></i>
                                            <span class="fw-semibold">Keluar (Logout)</span>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endif
                </div>
            </header>

            <!-- Main Content Body -->
            <main class="p-3 p-md-4 flex-grow-1">
                @if(session()->has('impersonator_id'))
                    <div class="alert alert-warning border-warning border-2 shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div class="d-flex align-items-center">
                            <div class="p-2 bg-warning text-dark rounded-circle me-3">
                                <i class="bi bi-incognito fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">MODE INTIP SUPERADMIN AKTIF</h6>
                                <span class="small text-dark">
                                    Anda saat ini sedang mengakses sistem sebagai <strong>{{ Auth::user()->name }}</strong> 
                                    <span class="badge bg-dark text-white ms-1">{{ Auth::user()->role }}</span>
                                </span>
                            </div>
                        </div>
                        <form action="{{ route('users.stopImpersonate') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 py-2 fw-semibold shadow-sm text-nowrap">
                                <i class="bi bi-box-arrow-left me-1"></i> Kembali ke Akun Admin Super
                            </button>
                        </form>
                    </div>
                @endif
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="footer py-3 px-4 border-top bg-white text-muted small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    <strong>simonKeu</strong> &copy; {{ date('Y') }} BPVP Surakarta
                </div>
                <div class="text-secondary" style="font-size: 11px;">
                    Kementerian Ketenagakerjaan Republik Indonesia
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Pengaturan Akun (Profil Saya) -->
    @if(Auth::check())
    <div class="modal fade" id="accountSettingsModal" tabindex="-1" aria-labelledby="accountSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-primary text-white p-3">
                    <h5 class="modal-title fw-bold fs-6" id="accountSettingsModalLabel">
                        <i class="bi bi-person-gear me-2"></i> Pengaturan Akun Profil Saya
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control rounded-3" value="{{ Auth::user()->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Alamat Email</label>
                            <input type="email" name="email" class="form-control rounded-3" value="{{ Auth::user()->email }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">No. WhatsApp Aktif <span class="text-danger">*</span></label>
                            <input type="text" name="no_wa" class="form-control rounded-3" value="{{ Auth::user()->no_wa }}" placeholder="Contoh: 628123456789" required>
                            <div class="form-text text-muted" style="font-size: 11px;">Digunakan untuk penerimaan notifikasi pesan berkas SPJ.</div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Role / Hak Akses</label>
                                <input type="text" class="form-control rounded-3 bg-light text-muted small" value="{{ Auth::user()->role }}" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Bidang Kerja</label>
                                <input type="text" class="form-control rounded-3 bg-light text-muted small" value="{{ Auth::user()->bidang ?? 'Tidak Ada' }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Biarkan kosong jika tidak ingin mengedit">
                            <div class="form-text text-muted" style="font-size: 11px;">Isi minimal 4 karakter hanya jika ingin mengganti kata sandi.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle & Tooltip Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable Bootstrap Tooltips globally
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            const appWrapper = document.getElementById('app-wrapper');
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');

            // Restore collapsed state on desktop from localStorage
            if (window.innerWidth >= 992) {
                const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
                if (isCollapsed && appWrapper) {
                    appWrapper.classList.add('sidebar-collapsed');
                }
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (window.innerWidth >= 992) {
                        // Desktop mode: Toggle collapsed icon-only mode
                        appWrapper.classList.toggle('sidebar-collapsed');
                        const collapsed = appWrapper.classList.contains('sidebar-collapsed');
                        localStorage.setItem('sidebar_collapsed', collapsed);
                    } else {
                        // Mobile mode: Toggle slide-in drawer
                        if (sidebar && sidebarOverlay) {
                            sidebar.classList.toggle('show');
                            sidebarOverlay.classList.toggle('show');
                        }
                    }
                });
            }

            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    if (sidebar) sidebar.classList.remove('show');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('show');
                });
            }
        });
    </script>
</body>

</html>
