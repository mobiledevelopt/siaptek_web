<!doctype html>
<html lang="en" dir="ltr">

<head>
    @include('partials.head')
    <style>
        /* Custom styles for the login form */
        .login-container {
            max-width: 400px;
            padding: 2rem;
            margin: auto;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .login-container h2 {
            font-size: 1.75rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 0.25rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
            }

            .login-container h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 1rem;
            }

            .login-container h2 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body class data-bs-spy="scroll" data-bs-target="#elements-section" data-bs-offset="0" tabindex="0">
    @include('partials.svg')
    <div class="wrapper d-flex align-items-center" style="height:100vh;background:#fff url({{ asset('images/bg.png') }}) no-repeat top left;background-size:contain">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="login-container">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
        <img src="{{ asset('images/logo.png') }}" style="height:3rem" class="position-absolute end-0 top-0 m-5" />
    </div>
</body>

</html>