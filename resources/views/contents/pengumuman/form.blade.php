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
                <div class="card-header d-flex justify-content-between mb-4">
                    <div class="header-title">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-action">
                        <a href="{{ Route('web-pengumuman.index') }}" class="btn btn-sm btn-primary" role="button">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Tanggal </label>
                        <input class="form-control datePicker" name="tgl" type="text" value="{{ @$edit['tgl'] ?? date('Y-m-d') }}" readonly>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Judul</label>
                        <input type="text" name="title" value="{{ @$edit['title'] }}" required="true" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="desc" class="form-control" rows="3">{{ @$edit['desc'] }}</textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold">Lampiran</label>
                        <input class="form-control image" type="file" id="attachment" name="attachment">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</form>
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
        $(".datePicker").flatpickr({
            disableMobile: true,
            //  dateFormat: 'Y-m-d',
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