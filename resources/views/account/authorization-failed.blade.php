@extends('app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9">
                <div class="alert alert-danger">
                    <p><strong>This login was not successful.</strong></p>
                    <p>{{$error_msg}}</p>
                </div>

                <div class="spacer1"></div>
                <p>Sorry about that.</p>

                <div class="spacer2"></div>
                <p><a class="btn btn-primary" href="{{ route('account.auth') }}">Try Again ?</a></p>
            </div>
        </div>
    </div>
@stop

@section('title')
Authorization Failed
@stop
