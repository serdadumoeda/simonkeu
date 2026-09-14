{{-- File: resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - simonKeu BPVP Surakarta</title>
    <!-- Google Fonts & Bootstrap CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1e3c72;
            background-image: linear-gradient(135deg, rgba(20, 38, 73, 0.82) 0%, rgba(29, 66, 138, 0.88) 100%), 
                              url("{{ asset('images/bg-login.jpg') }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 0;
            overflow-x: hidden;
            position: relative;
        }

        /* Decorative background elements */
        body::before {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            top: -120px;
            left: -120px;
            z-index: 1;
        }

        body::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(255, 193, 7, 0.03);
            bottom: -200px;
            right: -200px;
            z-index: 1;
        }

        .login-container {
            z-index: 10;
            width: 100%;
            max-width: 480px;
            padding: 15px;
        }

        .card-login {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        /* Branding Logo Header Styling */
        .brand-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-icon-wrapper {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .brand-title {
            font-size: 2.85rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }

        .brand-title .simon {
            color: #1b2e4b;
        }

        .brand-title .keu {
            color: #1d66f2;
        }

        .system-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.5rem 0;
            gap: 12px;
        }

        .system-line {
            height: 1.5px;
            flex: 1;
            background-color: #64748b;
            opacity: 0.4;
            max-width: 95px;
        }

        .system-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #334155;
            letter-spacing: 0.02em;
        }

        .brand-subtitle {
            font-size: 0.925rem;
            font-weight: 600;
            color: #334155;
            line-height: 1.45;
            max-width: 370px;
            margin: 0 auto;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-custom .form-control {
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            padding: 13px 15px 13px 45px;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
            background-color: #f8fafc;
        }

        .input-group-custom .form-control:focus {
            background-color: #ffffff;
            border-color: #1d66f2;
            box-shadow: 0 0 0 4px rgba(29, 102, 242, 0.12);
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.15rem;
            z-index: 10;
            transition: color 0.2s;
        }

        .input-group-custom .form-control:focus + .input-icon {
            color: #1d66f2;
        }

        .btn-login {
            background: linear-gradient(135deg, #1b2e4b 0%, #1d66f2 100%);
            border: none;
            border-radius: 12px;
            padding: 13px;
            font-weight: 700;
            font-size: 1.025rem;
            color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(29, 102, 242, 0.35);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(29, 102, 242, 0.45);
            background: linear-gradient(135deg, #15243b 0%, #1554cd 100%);
            color: #ffffff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card card-login p-4 p-md-5">
            <div class="brand-header">
                <!-- SVG Logo Icon based on provided image -->
                <div class="brand-icon-wrapper">
                    <svg width="76" height="82" viewBox="0 0 76 82" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Main Document Shape -->
                        <rect x="12" y="12" width="52" height="64" rx="8" fill="#14223d"/>
                        <!-- White Document Lines -->
                        <rect x="23" y="27" width="24" height="4.5" rx="2" fill="#ffffff"/>
                        <rect x="23" y="36" width="30" height="4.5" rx="2" fill="#ffffff"/>
                        <rect x="23" y="45" width="18" height="4.5" rx="2" fill="#ffffff"/>
                        <!-- Top Right Yellow Dot Badge -->
                        <circle cx="56" cy="14" r="7" fill="#ffb700"/>
                        <!-- Blue Checkmark -->
                        <path d="M20 57 L34 70 L58 44" stroke="#1d66f2" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>

                <!-- Brand Title (simon in dark navy, Keu in bright blue) -->
                <div class="brand-title">
                    <span class="simon">simon</span><span class="keu">Keu</span>
                </div>

                <!-- Sistem Divider -->
                <div class="system-divider">
                    <div class="system-line"></div>
                    <span class="system-label">Sistem</span>
                    <div class="system-line"></div>
                </div>

                <!-- Subtitle Description -->
                <div class="brand-subtitle mt-2">
                    Monitoring dan Pengendalian Dokumen Keuangan<br>di BPVP Surakarta
                </div>
            </div>

            {{-- Menampilkan error jika login gagal --}}
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 px-3 mb-4 small d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                    <span class="text-danger">{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="" method="POST">
                @csrf
                
                <div class="input-group-custom">
                    <input type="text" name="name" class="form-control" placeholder="Nama Pengguna / Email" value="{{ old('name') }}" required autofocus>
                    <i class="bi bi-person input-icon"></i>
                </div>

                <div class="input-group-custom">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <i class="bi bi-lock input-icon"></i>
                </div>

                <button type="submit" class="btn btn-login w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Aplikasi
                </button>
            </form>
        </div>
        
        <div class="text-center mt-4 footer-text">
            <p class="mb-0">&copy; {{ date('Y') }} BPVP Surakarta. All Rights Reserved.</p>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>