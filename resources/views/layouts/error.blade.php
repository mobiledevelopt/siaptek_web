<!doctype html>
<html lang="en" dir="ltr">

<head>
    @include('partials.headerror')
</head>

<body class data-bs-spy="scroll" data-bs-target="#elements-section" data-bs-offset="0" tabindex="0">

    <div class="wrapper d-flex align-items-center" style="height:100vh;background:#cfecf6 url({{ asset('images/bg.png') }}) no-repeat top left;background-size:contain">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-6">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</body>

</html>