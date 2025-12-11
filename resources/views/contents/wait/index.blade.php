@extends('layouts.admin')

@section('content')
    <div class="row justify-content-center pt-5">
        <div class="col-sm-4 pt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    Page will available soon!
                </div>
            </div>
        </div>
    </div>
@stop

@push('scripts')
    <script type="text/javascript" src="{{ asset('js/users.js') }}"></script>
@endpush
