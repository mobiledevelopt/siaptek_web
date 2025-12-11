@extends('layouts.admin')

@section('content')
    <form method="post" action="{{ Route('passwd.action') }}">
        @method('patch')
        @csrf
        <div class="row justify-content-center">
            <div class="col-sm-4">
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
                            <label class="form-label mb-0">Password baru</label>
                            <input type="password" name="password" required="true"
                                class="form-control @error('password') is-invalid @enderror" autocomplete="off" />
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label mb-0">Ulangi Password</label>
                            <input type="password" name="password_confirmation" required="true" class="form-control"
                                autocomplete="off" />
                        </div>
                        <button type="submit" class="btn w-100 btn-primary">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop


@push('script')
@endpush
