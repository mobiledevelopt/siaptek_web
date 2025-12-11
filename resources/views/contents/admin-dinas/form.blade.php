@extends('layouts.admin')

@section('content')
<form id="formStore" action="{{ $action }}" method="POST">
    @method($method)
    @if (@$edit['id'])
    <input type="hidden" name="id" readonly="true" value="{{ @$edit['id'] }}" required="true" class="form-control bg-light" autocomplete="off" />
    @endif

    @csrf
    <input type="hidden" name="id" value="{{ @$edit->id }}" />
    <div class="row justify-content-center">
        <div class="col">
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
                        <label class="form-label mb-0">Dinas</label>
                        <select name="dinas_id" id="dinas_id" class="select2-basic-single js-states form-select">
                            @if(isset($edit->dinas_id))
                            <option value="{{ $edit->dinas_id }}">{{ $edit->dinas->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Role</label>
                        <select name="role_id" id="role_id" class=" select2-basic-single js-states form-select" required="true">
                            @if(isset($edit->role_id))
                            <option value="{{ $edit->role_id }}">{{ $edit->roles->name }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Nama</label>
                        <input type="text" name="name" required="true" class="form-control @error('name') is-invalid @enderror" autocomplete="off" value="{{ @$edit->name }}" />
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Email</label>
                        <input type="email" name="email" required="true" class="form-control @error('email') is-invalid @enderror" autocomplete="off" value="{{ @$edit->email }}" />
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label mb-0">Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" @if (@$edit->id == "") required="true" @endif autocomplete="off" />
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" value="1" @if (@$edit->active == 1) checked @endif id="flexSwitchCheckDefault">
                            <label class="form-check-label" for="flexSwitchCheckDefault">Active</label>
                        </div>
                    </div>
                    <button type="submit" class="btn w-100 btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</form>
@stop


@push('script')
<script src="{{asset('vendor/toastr/toastr.min.js')}}"></script>
<link rel="stylesheet" href="{{asset('vendor/toastr/toastr.min.css')}}">
<script src="{{ asset('js/plugins/select2.js') }}" defer></script>
<style>
    .select2-selection.select2-selection--single {
        padding-right: 30px !important;
    }
</style>
<script>
    $(document).ready(function() {
        $('#dinas_id').select2({
            dropdownParent: $('#dinas_id').parent(),
            placeholder: "Pilih Dinas",
            allowClear: true,
            width: '100%',
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

        $('#role_id').select2({
            dropdownParent: $('#role_id').parent(),
            placeholder: "Pilih Roles",
            allowClear: true,
            width: '100%',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('roles.select2') }}",
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