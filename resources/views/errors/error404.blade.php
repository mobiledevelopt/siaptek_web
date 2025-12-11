@extends('layouts.error')
@section('content')
<div class="card-body">
    <div class="container">
         <img src="{{asset('images/error/404.png')}}" class="img-fluid mb-4 w-50" alt=""> 
        <h2 class="mb-0 text-black">Oops! This Page is Not Found.</h2>
        <p class="mt-2 text-black">The requested page dose not exist.</p>
    </div>
    <div class="box">
        <div class="c xl-circle">
            <div class="c lg-circle">
                <div class="c md-circle">
                    <div class="c sm-circle">
                        <div class="c xs-circle">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop