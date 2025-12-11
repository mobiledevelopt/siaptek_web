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
                            <label class="form-label mb-0">Dinas</label>
                            <input class="form-control" name="dinas_id" type="text" value="{{ @$data->dinas->name}}" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Hari</label>
                            @php
                            if (@$data['hari'] == '1') {
                            $hari = 'Senin';
                            } else if (@$data['hari'] == '2') {
                            $hari = 'Selasa';
                            } else if (@$data['hari'] == '3') {
                            $hari = 'Rabu';
                            } else if (@$data['hari'] == '4') {
                            $hari = 'Kamis';
                            } else if (@$data['hari'] == '5') {
                            $hari = 'Jumat';
                            }
                            @endphp
                            <input class="form-control" name="hari" type="text" value="@php echo $hari @endphp" readonly>
                        </div>
                        <div class="form-check form-switch mt-4">
                            <label class="form-label mb-0">Apel Pagi</label>
                            <input class="form-check-input" type="checkbox" name="apel_pagi" id="apel_pagi" value="1" @if (@$data->apel_pagi == '1') checked @endif id="flexSwitchCheckDefault">
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Jam Apel Pagi</label>
                            <input type="text" value="{{ @$data['jam_apel_pagi']}}" name="jam_apel_pagi" id="jam_apel_pagi" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Maksimal Jam Apel Pagi</label>
                            <input type="text" value="{{ @$data['max_apel_pagi']}}" name="max_apel_pagi" id="max_apel_pagi" class="form-control datePicker" autocomplete="off" />
                        </div>
                        <div class="form-check form-switch mt-4">
                            <label class="form-label mb-0">Apel Sore</label>
                            <input class="form-check-input" type="checkbox" name="apel_sore" id="apel_sore" value="1" @if (@$data->apel_sore=='1' ) checked @endif id="flexSwitchCheckDefault">
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Jam Apel Sore</label>
                            <input type="text" value="{{ @$data['jam_apel_sore']}}" name="jam_apel_sore" id="jam_apel_sore" class="form-control datePicker_sore" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Maksimal Jam Apel Sore</label>
                            <input type="text" value="{{ @$data['max_apel_sore']}}" name="max_apel_sore" id="max_apel_sore" class="form-control datePicker_sore" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Latitude</label>
                            <input type="text" value="{{ @$data['latitude']}}" name="latitude" class="form-control" autocomplete="off" />
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label mb-0">Longitude</label>
                            <input type="text" value="{{ @$data['longitude']}}" name="longitude" class="form-control" autocomplete="off" />
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
            time_24hr: true,
            onReady: function(dateObj, dateStr, instance) {
                const $clear = $('<button class="btn btn-danger btn-sm flatpickr-clear mb-2 mt-2">Clear</button>')
                    .on('click', () => {
                        instance.clear();
                        instance.close();
                    })
                    .appendTo($(instance.calendarContainer));
            }
        });

        $(".datePicker_sore").flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minTime: "12:00",
            onReady: function(dateObj, dateStr, instance) {
                const $clear = $('<button class="btn btn-danger btn-sm flatpickr-clear mb-2 mt-2">Clear</button>')
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

        var apel_pagi = document.getElementById('apel_pagi').value,
            apel_sore = document.getElementById('apel_sore').value;

        console.log
        if (apel_pagi == 0) {
            document.getElementById('jam_apel_pagi').disabled = true;
            document.getElementById('jam_apel_pagi').value = "";
            document.getElementById('max_apel_pagi').disabled = true;
            document.getElementById('max_apel_pagi').value = "";
        }

        if (apel_sore == 0) {
            document.getElementById('jam_apel_sore').disabled = true;
            document.getElementById('jam_apel_sore').value = "";
            document.getElementById('max_apel_sore').disabled = true;
            document.getElementById('max_apel_sore').value = "";
        }

        $("#apel_pagi").click(function() {
            var formElementVisible = $(this).is(":checked");
            if (formElementVisible) {
                document.getElementById('jam_apel_pagi').disabled = false;
                document.getElementById('max_apel_pagi').disabled = false;
                return true;
            }
            document.getElementById('jam_apel_pagi').disabled = true;
            document.getElementById('jam_apel_pagi').value = "";
            document.getElementById('max_apel_pagi').disabled = true;
            document.getElementById('max_apel_pagi').value = "";

        });

        $("#apel_sore").click(function() {
            var formElementVisible = $(this).is(":checked");
            if (formElementVisible) {
                document.getElementById('jam_apel_sore').disabled = false;
                document.getElementById('max_apel_sore').disabled = false;
                return true;
            }
            document.getElementById('jam_apel_sore').disabled = true;
            document.getElementById('jam_apel_sore').value = "";
            document.getElementById('max_apel_sore').disabled = true;
            document.getElementById('max_apel_sore').value = "";

        });

    });
</script>
@endpush