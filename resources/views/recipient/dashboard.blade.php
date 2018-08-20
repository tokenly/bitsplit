@extends('app')

@section('content')

<?php
    $dash_info = User::getDashInfo();
?>

<div class="content padded">
    <div class="page-information">
        <h1>Dashboard</h1>
    </div>
    
    <div class="dashboard">
        Welcome to your FLDC dashboard
    </div>
</div>
@endsection

@section('title')
    FLDC Dashboard
@stop
