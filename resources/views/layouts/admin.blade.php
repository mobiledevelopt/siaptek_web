<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    @include('partials.head')

</head>

<body class="">
    @include('partials.svg')
    <div id="loading">
        @include('partials.loader')
    </div>
    @include('partials.sidebar')
    <main class="main-content">
        <div class="position-relative default">
            <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar">
                @include('partials.navbar')
            </nav>
        </div>

        <div class="conatiner-fluid content-inner">
            @yield('content')
        </div>

        @include('partials.footer')

    </main>
    @include('partials.foot')
    @stack('script')
</body>

</html>