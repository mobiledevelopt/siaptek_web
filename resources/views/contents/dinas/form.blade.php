@extends('layouts.admin')

@section('content')
<form id="formStore" action="{{ $action }}" method="POST">
    @method($method)
    @csrf

    <input type="hidden" name="id" readonly="true" value="{{ @$edit['id'] }}" required="true" class="form-control bg-light" autocomplete="off" />

    <div class="row justify-content-center">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @elseif (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Nama Dinas</label>
                        <input type="text" name="name" value="{{ @$edit['name'] }}" required="true" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Latitude</label>
                        <input type="text" name="latitude" class="form-control" value="{{ @$edit['latitude'] }}" />
                    </div>
                    <div class="form-group mb-4">
                        <label class="form-label mb-0">Longitude</label>
                        <input type="text" value="{{ @$edit['longitude'] }}" name="longitude" class="form-control" autocomplete="off" />
                    </div>
                    <div class="form-group text-end">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                    <!-- <button type="submit" class="btn btn-primary">Submit</button> -->
                </div>
            </div>
        </div>
    </div>
</form>
@stop


@push('script')
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