{{-- File: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - simonKeu BPVP Surakarta</title>
    
    <!-- Google Fonts & Bootstrap CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333333;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-bottom: 3px solid #ffc107;
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.2s ease-in-out;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        .nav-item.active .nav-link {
            color: #ffc107 !important;
            border-bottom: 2px solid #ffc107;
        }

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

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom p-3 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('dashboard') }}">
                <i class="bi bi-wallet2 me-2"></i> simonKeu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item {{ Route::is('dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('pengajuan.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('pengajuan.index') }}">
                            <i class="bi bi-file-earmark-text me-1"></i> Daftar Pengajuan
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('anggaran.index') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('anggaran.index') }}">
                            <i class="bi bi-journal-check me-1"></i> Informasi Anggaran
                        </a>
                    </li>
                    @if(Auth::user()->role == 'Admin Keuangan')
                        <li class="nav-item {{ Route::is('users.*') ? 'active' : '' }}">
                            <a class="nav-link text-warning fw-semibold" href="{{ route('users.index') }}">
                                <i class="bi bi-people-fill me-1"></i> Kelola User
                            </a>
                        </li>
                    @endif
                </ul>
                <div class="d-flex align-items-center">
                    
                    <!-- Lonceng Notifikasi Dropdown -->
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-light btn-sm rounded-pill position-relative dropdown-toggle border-0" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill fs-5"></i>
                            @if(count($unreadNotifications) > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="margin-top: 5px; margin-left: -5px;">
                                    {{ count($unreadNotifications) }}
                                </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-0" aria-labelledby="notificationDropdown" style="width: 320px; font-size: 13px;">
                            <li class="bg-primary text-white p-2 rounded-top fw-bold text-center">
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

                    <span class="text-white me-3 bg-white bg-opacity-10 py-1 px-3 rounded-pill small">
                        <i class="bi bi-person-circle me-1"></i> <strong>{{ Auth::user()->name }}</strong>
                        ({{ Auth::user()->role }})
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        @yield('content')
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
