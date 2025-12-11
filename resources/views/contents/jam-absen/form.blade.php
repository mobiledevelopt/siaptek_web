@extends('layouts.admin')

@section('content')
<form id="formStore" action="{{ $action }}" method="POST">
    @method($method)
    @if (@$edit['id'])
    <input type="hidden" name="id" readonly="true" value="{{ @$edit['id'] }}" required="true" class="form-control bg-light" autocomplete="off" />
    @endif

    @csrf
    <div class="row justify-content-center">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">{{ $title }}</h4>
                    <a onclick="history.back()" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate-left"></i> Kembali</a>
                </div>

                <div class="card-body">
                    <div class="form-group">
                        <div id="errorCreate" class="mb-3" style="display:none;">
                            <div class="alert alert-danger" role="alert">
                                <div class="alert-icon"><i class="flaticon-danger text-danger"></i></div>
                                <div class="alert-text">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Hari</label>
                            <input class="form-control" name="nama" type="text" value="{{ @$data['title']}}" readonly>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Minimal Jam Masuk</label>
                            <input type="text" value="{{ @$data['min_masuk']}}" name="min_masuk" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Jam Masuk</label>
                            <input type="text" value="{{ @$data['jam_masuk']}}" name="jam_masuk" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Maksimal Jam Masuk</label>
                            <input type="text" value="{{ @$data['max_masuk']}}" name="max_masuk" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Minimal Jam Pulang</label>
                            <input type="text" value="{{ @$data['min_pulang']}}" name="min_pulang" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Jam Pulang</label>
                            <input type="text" value="{{ @$data['jam_pulang']}}" name="jam_pulang" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Maksimal Jam Pulang</label>
                            <input type="text" value="{{ @$data['max_pulang']}}" name="max_pulang" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group text-end">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@stop


@push('script')
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
<link rel="stylesheet" href="{{asset('vendor/flatpickr/flatpickr.min.css')}}">
<script src="{{asset('vendor/flatpickr/flatpickr.js')}}"></script>
<script>
    $(document).ready(function() {

        $(".datePicker").flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true
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