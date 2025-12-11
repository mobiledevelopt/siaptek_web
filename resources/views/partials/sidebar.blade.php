<aside class="sidebar sidebar-default navs-rounded-all" id="first-tour" data-toggle="main-sidebar" data-sidebar="responsive">
    <div class="sidebar-header d-flex align-items-center justify-content-start ">
        <a href="{{ Route('home') }}" class="navbar-brand">
             <img src="{{ asset('images/logo.png') }}" alt="logo" loading="lazy" height="26px" /> 
            <!-- <svg width="30" height="30" fill="none">
                <use href="#logo"></use>
            </svg> -->
            <h4 class="logo-title d-none d-sm-block">{{ env('APP_NAME') }}</h4>
        </a>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon">
                <svg class="icon-20" width="20" fill="none">
                    <use href="#arrow"></use>
                </svg>
            </i>
        </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="sidebar-list" id="sidebar">
            <ul class="navbar-nav iq-main-menu" id="sidebar">

                <!-- <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('home')) }}" aria-current="page" href="{{ route('home') }}">
                        <i class="icon">
                            <svg class="icon-20">
                                <use href="#dashboard"></use>
                            </svg>
                        </i>
                        <span class="item-name">Dashboard</span>
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('pegawai.index')) }}" aria-current="page" href="{{ route('pegawai.index') }}">
                        <i class="icon">
                            <svg class="icon-20">
                                <use href="#dashboard"></use>
                            </svg>
                        </i>
                        <span class="item-name">Data Pegawai</span>
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('presensi-pegawai.index')) }}" aria-current="page" href="{{ Route('presensi-pegawai.index') }}">
                        <i class="icon">
                            <svg class="icon-20">
                                <use href="#dashboard"></use>
                            </svg>
                        </i>
                        <span class="item-name">Data Presensi</span>
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('pengumuman.index')) }}" aria-current="page" href="{{ Route('pengumuman.index') }}">
                        <i class="icon">
                            <svg class="icon-20">
                                <use href="#dashboard"></use>
                            </svg>
                        </i>
                        <span class="item-name">Data Pengumuman</span>
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link {{ activeRoute(route('izin.index')) }}" aria-current="page" href="{{ Route('izin.index') }}">
                        <i class="icon">
                            <svg class="icon-20">
                                <use href="#dashboard"></use>
                            </svg>
                        </i>
                        <span class="item-name">Data Izin</span>
                    </a>
                </li> -->
                {!! Menu::sidebar() !!}
            </ul>
        </div>
    </div>
    <div class="sidebar-footer"></div>
</aside>