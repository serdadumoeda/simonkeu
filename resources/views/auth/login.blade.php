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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* Decorative background elements */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            top: -100px;
            left: -100px;
            z-index: 1;
        }

        body::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: rgba(255, 193, 7, 0.02);
            bottom: -200px;
            right: -200px;
            z-index: 1;
        }

        .login-container {
            z-index: 10;
            width: 100%;
            max-width: 440px;
            padding: 15px;
        }

        .card-login {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border-top: 5px solid #ffc107;
            overflow: hidden;
        }

        .brand-logo {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }

        .input-group-custom .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 15px 12px 45px;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
            background-color: #f8fafc;
        }

        .input-group-custom .form-control:focus {
            background-color: #ffffff;
            border-color: #1e3c72;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.15);
        }

        .input-group-custom .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
            z-index: 10;
            transition: color 0.2s;
        }

        .input-group-custom .form-control:focus + .input-icon {
            color: #1e3c72;
        }

        .btn-login {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(30, 60, 114, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(30, 60, 114, 0.4);
            background: linear-gradient(135deg, #152b52 0%, #1e3c72 100%);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card card-login p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="brand-logo d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-wallet2 text-primary fs-3"></i>
                    <span>simonKeu</span>
                </div>
                <p class="text-secondary small px-2">Sistem Informasi Smart Administrasi Layanan Manajemen Keuangan BPVP Surakarta</p>
                <hr class="mx-auto my-3 text-muted opacity-25" style="width: 80px;">
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