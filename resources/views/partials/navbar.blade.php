<div class="container-fluid navbar-inner">
    <a href="{{ Route('home') }}" class="navbar-brand">
         <!--<img src="{{ asset('images/logo.png') }}" alt="logo" loading="lazy" height="26px" /> -->
        {{-- <svg width="30" height="30" class="text-primary" fill="none">
            <use href="#logo"></use>
        </svg>
        <h4 class="logo-title d-none d-sm-block">{{ env('APP_NAME') }}</h4> --}}
    </a>
    <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
        <i class="icon d-flex">
            <svg width="20px" viewBox="0 0 24 24">
                <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
            </svg>
        </i>
    </div>
    <div class="d-flex align-items-center justify-content-between product-offcanvas">
        <div class="breadcrumb-title me-3 pe-3 d-none d-xl-block">
            <small class="mb-0 text-capitalize">{{ $title ?? $config['title'] }}</small>
        </div>
    </div>
    <div class="d-flex align-items-center">
        <button id="navbar-toggle" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon">
                <span class="navbar-toggler-bar bar1 mt-1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
            </span>
        </button>
    </div>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
            <li class="nav-item dropdown" id="itemdropdown1">
                <a class="py-0 nav-link d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="btn btn-primary btn-icon btn-sm rounded-pill">
                        <span class="btn-inner">
                            <svg class="icon-32" width="32" fill="none">
                                <use href="#user"></use>
                            </svg>
                        </span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('passwd') }}">Password</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ url('login/logout') }}">
                            @csrf
                            <a href="javascript:void(0)" class="dropdown-item" onclick="event.preventDefault(); this.closest('form').submit();">
                                Log out
                            </a>
                        </form>
                    </li>
                </ul>
            </li>
            <li class="nav-item iq-full-screen d-none d-xl-block" id="fullscreen-item">
                <a href="#" class="nav-link" id="btnFullscreen" data-bs-toggle="dropdown">
                    <div class="btn btn-primary btn-icon btn-sm rounded-pill">
                        <span class="btn-inner">
                            <svg class="normal-screen" width="24" height="24" fill="none"">
                                <use href=" #expand"></use>
                            </svg>
                            <svg class="full-normal-screen d-none" width="24" height="24" fill="none">
                                <use href="#resize"></use>
                            </svg>
                        </span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>