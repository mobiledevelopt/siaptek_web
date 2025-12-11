@extends('layouts.admin')

@section('content')
<form id="formStore" action="{{ $action }}" method="POST">
    @method($method)
    @csrf
    <div class="row justify-content-center">
        <div class="col-sm-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-body">
                    <div id="errorCreate" class="mb-3" style="display:none;">
                        <div class="alert alert-danger" role="alert">
                            <div class="alert-icon"><i class="flaticon-danger text-danger"></i></div>
                            <div class="alert-text">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Nomor HP</label>
                        <input type="text" name="no_hp" value="{{ @$edit['no_hp'] }}" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Email</label>
                        <input type="email" name="email" value="{{ @$edit['email'] }}" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Password</label>
                        @if (@$edit['id'])
                        <input type="password" name="password" class="form-control" autocomplete="off" />
                        @else
                        <input type="password" name="password" class="form-control" autocomplete="off" />
                        @endif
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">Sekolah / Perguruan Tinggi</h4>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Jenjang</label>
                        <select name="jenjang_pendidikan_id" data-minimum-results-for-search="-1" class="select2-basic-single js-states form-select">
                            @foreach ($jenjang_pendidikan as $item)
                            @if ($item->id == @$edit['jenjang_pendidikan_id'])
                            <option value="{{ $item->id }}" selected>{{ strtoupper($item->name) }}</option>
                            @else
                            <option value="{{ $item->id }}">{{ strtoupper($item->name) }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Jurusan</label>
                        <input oninput="this.value = this.value.toUpperCase()" style="text-transform:uppercase" type="text" name="nama_pendidikan" value="{{ @$edit['nama_pendidikan'] }}" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Tahun Lulus</label>
                        <select name="thn_lulus_pendidikan" id="yearpicker" class=" select2-basic-single js-states form-select">
                            <option value="0">Pilih Tahun Lulus</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">Diklat Struktural</h4>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Nama Diklat</label>
                        <input oninput="this.value = this.value.toUpperCase()" style="text-transform:uppercase" type="text" name="nama_diklat" value="{{ @$edit['nama_diklat'] }}" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Tanggal</label>
                        <input type="text" name="tgl_diklat" value="{{ @$edit['tgl_diklat'] }}" class="form-control datePicker" autocomplete="off" readonly />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Jam</label>
                        <input type="text" name="jam_diklat" value="{{ @$edit['jam_diklat'] }}" class="form-control" autocomplete="off" />
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Profile</h4>
                    </div>
                    <div class="card-action">
                        <a href="{{ Route('pegawai.index') }}" class="btn btn-sm btn-primary" role="button">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">NIK</label>
                            <input type="number" name="id" value="{{ @$edit['id'] }}" class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Nama</label>
                            <input oninput="this.value = this.value.toUpperCase()" type="text" name="name" class="form-control" style="text-transform:uppercase" autocomplete="off" value="{{ @$edit['name'] }}" />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Gelar Depan</label>
                            <input type="text" name="gelar_depan" class="form-control" autocomplete="off" value="{{ @$edit['gelar_depan'] }}" />
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Gelar Belakang</label>
                            <input type="text" name="gelar_belakang" class="form-control" autocomplete="off" value="{{ @$edit['gelar_belakang'] }}" />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Tempat Lahir</label>
                            <input oninput="this.value = this.value.toUpperCase()" style="text-transform:uppercase" type="text" value="{{ @$edit['place_of_birth'] }}" name="place_of_birth" class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Tanggal Lahir</label>
                            <input type="text" name="date_of_birth" class="form-control datePicker" autocomplete="off" value="{{ @$edit['date_of_birth'] }}" readonly />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Jenis Kelamin</label>
                            <select name="gender" data-minimum-results-for-search="-1" class="select2-basic-single js-states form-select">
                                @foreach ($gender as $item)
                                @if ($item == @$edit['gender'])
                                <option value="{{ $item }}" selected>{{ strtoupper($item) }}</option>
                                @else
                                <option value="{{ $item }}">{{ strtoupper($item) }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Agama</label>
                            <select name="religion_id" data-minimum-results-for-search="-1" class="select2-basic-single js-states form-select">
                                @foreach ($agama as $item)
                                @if ($item->id == @$edit['religion_id'])
                                <option value="{{ $item->id }}" selected>{{ strtoupper($item->name) }}</option>
                                @else
                                <option value="{{ $item->id }}">{{ strtoupper($item->name) }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Status Perkawinan</label>
                            <select name="marriage_id" data-minimum-results-for-search="-1" class="select2-basic-single js-states form-select">
                                @foreach ($status as $item)
                                @if ($item->id == @$edit['marriage_id'])
                                <option value="{{ $item->id }}" selected>{{ strtoupper($item->name) }}</option>
                                @else
                                <option value="{{ $item->id }}">{{ strtoupper($item->name) }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Dinas</label>
                            <select name="dinas_id" id="dinas_id" class="select2-basic-single js-states form-select">
                                @if(isset($edit->dinas_id))
                                <option value="{{ $edit->dinas_id }}">{{ strtoupper($edit->dinas->name) }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">

                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">NIP</label>
                            <input type="number" name="nip" value="{{ @$edit['nip'] }}" class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">TMT Jabatan</label>
                            <input type="text" name="tmt_pengangkatan" class="form-control datePicker" autocomplete="off" value="{{ @$edit['tmt_pengangkatan'] }}" readonly />
                        </div>

                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md">
                            <label class="form-label mb-0">Jabatan</label>
                            <input oninput="this.value = this.value.toUpperCase()" style="text-transform:uppercase" type="text" name="position_pegawai" class="form-control" value="{{ @$edit['position_pegawai'] }}" />
                        </div>
                    </div>
                    <!-- <div class="row mb-3">
                        <div class="form-group col col-md">
                            <label class="form-label mb-0">Tingkat Pendidikan</label>
                            <input type="text" name="tingkat_pendidikan" class="form-control" autocomplete="off" value="{{ @$edit['tingkat_pendidikan'] }}" />
                        </div>
                    </div> -->
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Pangkat/Golongan</label>
                            <select name="pangkat_gol_id" class="select2-basic-single js-states form-select">
                                @foreach ($pangkat as $item)
                                @if ($item->id == @$edit['pangkat_gol_id'])
                                <option value="{{ $item->id }}" selected>{{ strtoupper($item->pangkat . " " . $item->gol) }}</option>
                                @else
                                <option value="{{ $item->id }}">{{ strtoupper($item->pangkat . " " . $item->gol) }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">TMT Pangkat</label>
                            <input type="text" name="tmt_pangkat" class="form-control datePicker" autocomplete="off" value="{{ @$edit['tmt_pangkat'] }}" readonly />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Masa Kerja Tahun</label>
                            <input type="number" value="{{ @$edit['masa_kerja_tahun'] }}" name="masa_kerja_tahun" class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group col col-md-6">
                            <label class="form-label mb-0">Masa Kerja Bulan</label>
                            <input type="number" name="masa_kerja_bulan" class="form-control" autocomplete="off" value="{{ @$edit['masa_kerja_bulan'] }}" />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="form-group col">
                            <label class="form-label mb-0">TPP</label>
                            <input type="number" value="{{ @$edit['tpp'] ?? 0}}" name="tpp" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" value="1" @if (@$edit->active == 1) checked @endif checked id="flexSwitchCheckDefault">
                            <label class="form-check-label" for="flexSwitchCheckDefault">Active</label>
                        </div>

                        @if(@$role ==1)
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="fake_gps" value="1" @if (@$edit->fake_gps == 1) checked @endif id="flexSwitchCheckDefault">
                            <label class="form-check-label" for="flexSwitchCheckDefault">Blokir FAKE GPS</label>
                        </div>
                        @endif

                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</form>
@stop

@push('scripts')
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr_picker.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr_month.js')}}"></script>
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.css') }}">
<script src="{{ asset('vendor/sweetalert2/dist/sweetalert2.min.js') }}" async></script>
<script type="text/javascript" src="{{ asset('js/users.js') }}"></script>
<script src="{{ asset('js/plugins/select2.js') }}" defer></script>
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">


<script>
    $(document).ready(function() {

        let startYear = 1800;
        let endYear = new Date().getFullYear();
        for (i = endYear; i > startYear; i--) {
            if (i == <?php echo $edit['thn_lulus_pendidikan'] ?? 0 ?>) {
                $('#yearpicker').append($('<option selected/>').val(i).html(i));
            } else {
                $('#yearpicker').append($('<option />').val(i).html(i));
            }
        }

        $(".datePicker").flatpickr({
            disableMobile: true,
            altInput: true,
            dateFormat: 'Y-m-d',
            altFormat: "d-m-Y",
            allowInput: true,
            onReady: function(dateObj, dateStr, instance) {
                const $clear = $('<button class="btn btn-danger btn-sm flatpickr-clear mb-2">Clear</button>')
                    .on('click', () => {
                        instance.clear();
                        instance.close();
                    })
                    .appendTo($(instance.calendarContainer));
            }
        });

        $(".yearsPicker").flatpickr({
            disableMobile: true,
            //  dateFormat: 'Y-m-d',
            altInput: true,
            dateFormat: 'Y-m',
            altFormat: "m-Y",
            allowInput: true,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true, //defaults to false
                    dateFormat: "m-Y", //defaults to "F Y"
                    altFormat: "F Y", //defaults to "F Y"
                    theme: "dark" // defaults to "light"
                })
            ],
            onReady: function(dateObj, dateStr, instance) {
                const $clear = $('<button class="btn btn-danger btn-sm flatpickr-clear mb-2">Clear</button>')
                    .on('click', () => {
                        instance.clear();
                        instance.close();
                    })
                    .appendTo($(instance.calendarContainer));
            }
        });

        $('#dinas_id').select2({
            dropdownParent: $('#dinas_id').parent(),
            placeholder: "Pilih Dinas",
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('dinas.select2') }}",
                dataType: "json",
                cache: true,
                data: function(e) {
                    return {
                        q: e.term || '',
                        page: e.page || 1
                    }
                },
            },
        });

        $("#formStore").submit(function(e) {
            e.preventDefault();
            let form = $(this);
            let btnSubmit = form.find("[type='submit']");
            let btnSubmitHtml = btnSubmit.html();
            let url = form.attr("action");
            let data = new FormData(this);
            $.ajax({
                cache: false,
                processData: false,
                contentType: false,
                type: "POST",
                url: url,
                data: data,
                beforeSend: function() {
                    btnSubmit.addClass("disabled").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...').prop("disabled", "disabled");
                },
                success: function(response) {
                    let errorCreate = $('#errorCreate');
                    errorCreate.css('display', 'none');
                    errorCreate.find('.alert-text').html('');
                    btnSubmit.removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
                    if (response.status === "success") {
                        toastr.success(response.message, 'Success !');
                        setTimeout(function() {
                            if (response.redirect === "" || response.redirect === "reload") {
                                location.reload();
                            } else {
                                location.href = response.redirect;
                            }
                        }, 1000);
                    } else {
                        toastr.error((response.message ? response.message : "Please complete your form"), 'Failed !');
                        if (response.error !== undefined) {
                            errorCreate.removeAttr('style');
                            $.each(response.error, function(key, value) {
                                errorCreate.find('.alert-text').append('<span style="display: block">' + value + '</span>');
                            });
                        }
                    }
                },
                error: function(response) {
                    btnSubmit.removeClass("disabled").html(btnSubmitHtml).removeAttr("disabled");
                    toastr.error(response.responseJSON.message, 'Failed !');
                }
            });
        });

    });
</script>
@endpush