<!doctype html>
<html lang="en" dir="ltr">

<head>
    @include('partials.head')
</head>

<body class data-bs-spy="scroll" data-bs-target="#elements-section" data-bs-offset="0" tabindex="0">
    @include('partials.svg')
    <div id="loading">
        @include('partials.loader')
    </div>
    <div class="wrapper">
        <div class="iq-auth-page">
            <nav class="navbar iq-auth-logo bg-dark mt-2 ms-2 rounded-3">
                <div class="container-fluid">
                    <a href="{{ url('/') }}" class="iq-link d-flex align-items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="logo" loading="lazy" height="32px" />
                        {{-- <h4 data-setting="xapp_name" class="mb-0">{{ env('APP_NAME') }}</h4> --}}
                    </a>
                </div>
            </nav>
            <div class="iq-banner-logo d-none d-lg-block align-items-center">
                <svg class="auth-image" fill="none">
                    <use href="#logo"></use>
                </svg>
            </div>
            <div class="container-inside">
                <div class="main-circle circle-small"></div>
                <div class="main-circle circle-medium"></div>
                <div class="main-circle circle-large"></div>
                <div class="main-circle circle-xlarge"></div>
                <div class="main-circle circle-xxlarge"></div>
            </div>
            <div class="row d-flex align-items-center iq-auth-container w-100">
                <div class="col-10 col-xl-4 offset-xl-7 offset-1">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @include('partials.foot')
</body>

</html>
