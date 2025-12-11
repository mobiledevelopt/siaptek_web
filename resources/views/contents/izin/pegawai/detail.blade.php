@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">Detail Izin</h4>
                </div>
                <div class="card-action">
                    <a href="{{ Route('web-izin.index') }}" class="btn btn-sm btn-primary" role="button">
                        <svg class="icon-20" width="20" fill="none">
                            <use href="#arrow"></use>
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="formStore" action="{{ $action }}" method="POST">
                    @method($method)
                    @csrf
                    <input type="hidden" name="id" value="{{ $detail->id }}">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td>Tanggal Izin</td>
                                <td> {{ date('d-m-Y', strtotime($detail->date)) }} s/d {{date('d-m-Y', strtotime($detail->sampai))}}</td>
                            </tr>
                            <tr>
                                <td>Dinas</td>
                                <td>{{ $detail->dinas }}</td>
                            </tr>
                            <tr>
                                <td>Nama Pegawai</td>
                                <td>{{ $detail->pegawai }}</td>
                            </tr>
                            <tr>
                                <td>Jenis Izin</td>
                                <td>{{ $detail->jenis }}</td>
                            </tr>
                            <tr>
                                <td>Deskripsi</td>
                                <td>{{ $detail->desc }}</td>
                            </tr>
                            <tr>
                                <td>Bukti Izin</td>
                                <td>
                                    <!-- <a class="btn btn-primary btn-icon btn-sm rounded-pill" href="{{ $detail->attachment }}" role="button" title="Download">
                                        <span class="btn-inner">
                                            <svg class="icon-32" width="32" fill="none">
                                                <use href="#download"></use>
                                            </svg>
                                            
                                        </span>
                                    </a> -->
                                    <img src="{{ $detail->attachment }}" width="100px" height="100px" onclick="window.open(this.src)">
                                </td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <select class="select2-basic-single js-states form-select" name="status">
                                        <option value="Pengajuan" {{ $detail->status === "Pengajuan" ?  'selected' : ''}}>Pengajuan</option>
                                        <option value="Di Terima" {{ $detail->status === "Di Terima" ?  'selected' : ''}}>Di Terima</option>
                                        <option value="Di Tolak" {{ $detail->status === "Di Tolak" ?  'selected' : ''}}>Di Tolak</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Catatan</td>
                                <td>
                                    <textarea class="form-control" name="status_note" rows="3">{{ $detail->alasan_ditolak }}</textarea>
                                </td>
                            </tr>
                            @if ($detail->status === "Pengajuan" && $role != 1)
                            <tr>
                                <td colspan="2" class="text-end"><button type="submit" class="btn btn-success">Simpan</button></td>
                            </tr>
                            @elseif($role == 1)
                            <tr>
                                <td colspan="2" class="text-end"><button type="submit" class="btn btn-success">Simpan</button></td>
                            </tr>
                            @endif

                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>
@stop

@push('script')
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr_picker.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr_month.js')}}"></script>
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
<script>
    $(document).ready(function() {
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