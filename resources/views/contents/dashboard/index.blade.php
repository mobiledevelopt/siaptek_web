@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4 gap-3">
    <div class="d-flex flex-column">
        <h3>Dashboard</h3>
        <!-- <p class="text-primary mb-0">Dashboard</p> -->
    </div>
    <div class="d-flex justify-content-between align-items-center rounded flex-wrap gap-3">
        <div class="form-group mb-0">
            <input type="text" name="start" class="form-control range_flatpicker flatpickr-input active" placeholder="24 Jan 2022 to 23 Feb 2022" readonly="readonly">
        </div>
        <button type="button" class="btn btn-primary">View</button>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="row">
            <!-- total tpp -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-44" width="44" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.9964 8.37513H17.7618C15.7911 8.37859 14.1947 9.93514 14.1911 11.8566C14.1884 13.7823 15.7867 15.3458 17.7618 15.3484H22V15.6543C22 19.0136 19.9636 21 16.5173 21H7.48356C4.03644 21 2 19.0136 2 15.6543V8.33786C2 4.97862 4.03644 3 7.48356 3H16.5138C19.96 3 21.9964 4.97862 21.9964 8.33786V8.37513ZM6.73956 8.36733H12.3796H12.3831H12.3902C12.8124 8.36559 13.1538 8.03019 13.152 7.61765C13.1502 7.20598 12.8053 6.87318 12.3831 6.87491H6.73956C6.32 6.87664 5.97956 7.20858 5.97778 7.61852C5.976 8.03019 6.31733 8.36559 6.73956 8.36733Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M16.0374 12.2966C16.2465 13.2478 17.0805 13.917 18.0326 13.8996H21.2825C21.6787 13.8996 22 13.5715 22 13.166V10.6344C21.9991 10.2297 21.6787 9.90077 21.2825 9.8999H17.9561C16.8731 9.90338 15.9983 10.8024 16 11.9102C16 12.0398 16.0128 12.1695 16.0374 12.2966Z" fill="currentColor"></path>
                                    <circle cx="18" cy="11.8999" r="1" fill="currentColor"></circle>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ "Rp ". number_format($dash['total_tpp'],0,',','.') }}</h2>
                                <a href="#"> <small>Total TPP</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <!-- pegawai -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3" onclick="pegawai()">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#users"></use>
                                </svg>
                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="font-size:1.7visibility: visible;">
                                    {{ $dash['pegawai']->num }}
                                </h2>
                                <a href="pegawai"> <small>Pegawai</small></a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- pengumuman -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#star"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['pengumuman']->num }}</h2>
                                <a href="web-pengumuman"> <small>Pengumuman</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- total presensi -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['total_absen'] }}</h2>
                                <a href="presensi-pegawai"> <small>Total Presensi</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- masuk -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['a_masuk'] }}</h2>
                                <a href="presensi-pegawai?status=Masuk"> <small>Masuk</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- tidak masuk -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['a_tidak_masuk'] }}</h2>
                                <a href="presensi-pegawai?status=Tidak Masuk"> <small>Tidak Masuk</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">
            <!-- izin -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['total_izin'] }}</h2>
                                <a href="web-izin"> <small>Total Izin</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- izin diterima -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['izin_aproved'] }}</h2>
                                <a href="web-izin?status=Di Terima"> <small>Izin Di Terima</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- izin ditolak -->
            <div class="col">
                <div class="card card-block card-stretch card-height">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-soft-primary avatar-60 rounded">
                                <svg class="icon-32" width="32" fill="none">
                                    <use href="#doc"></use>
                                </svg>

                            </div>
                            <div style="width: 100%;">
                                <h2 class="counter" style="visibility: visible;">{{ $dash['izin_not_aproved'] }}</h2>
                                <a href="web-izin?status=Di Tolak"> <small>Izin Di Tolak</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div>
            <div class="card card-block card-stretch card-height">
                <div class="card-header">
                    <div class=" d-flex justify-content-between  flex-wrap">
                        <h4 class="card-title">Persentase Kehadiran</h4>
                        <div class="dropdown">
                            <a href="#" class="text-secondary dropdown-toggle" id="dropdownMenuButton22" data-bs-toggle="dropdown" aria-expanded="false">
                                Sort by
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton22">
                                <li><a class="dropdown-item" href="#">Month</a></li>
                                <li><a class="dropdown-item" href="#">Week</a></li>
                                <li><a class="dropdown-item" href="#">Year</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="dashboard-line-chart" class="dashboard-line-chart"></div>
                </div>
            </div>
        </div> -->

    </div>
    <div class="col-md-4">
        <div class="card card-block card-stretch card-height">
            <div class="card-header border-bottom pb-3">
                <div class=" d-flex justify-content-between  flex-wrap">
                    <h4 class="card-title">Pengumuman Terbaru</h4>
                </div>
            </div>
            <div class="card-body">
                @foreach ($pengumuman as $item)
                <div class="mb-3 border-bottom">
                    <small class="text-primary">{{ date('d F Y', strtotime($item->created_at)) }}</small>
                    <span class="iq-title">
                        <h4 class="my-3">{{ $item->title }}.</h4>
                    </span>
                    <p class="pt-2">{{ $item->desc}} </p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function pegawai() {
        // document.getElementById(i).style.visibility='visible';
        console.log("a");
    }
</script>
@endpush
@endsection