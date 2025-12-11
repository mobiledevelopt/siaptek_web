@extends('layouts.auth')

@section('content')
    <form method="post">
        @csrf
        <div class="card-body ">
            <h4 class="mb-3">Log in to your account</h4>
            <div class="form-group">
                <label class="form-label" for="email-id">ID</label>
                <input type="email" name="email" class="form-control mb-0" id="email-id" placeholder="Enter email"
                    @error('email') is-invalid @enderror value="{{ old('email') }}" autocomplete="off">
                @if ($errors->has('email'))
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" class="form-control mb-0" id="password" placeholder="Enter password"
                    autocomplete="off">
                @if ($errors->has('password'))
                    <span class="text-danger">{{ $errors->first('password') }}</span>
                @endif
            </div>
            <div class="text-center pb-3">
                <button type="submit" class="btn btn-primary w-100">Log in</button>
            </div>
        </div>
    </form>
@stop
