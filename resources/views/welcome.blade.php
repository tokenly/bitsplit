@extends('app')

@section('content')
    
    @include('welcome.hero')
    
    @include('welcome.use-cases')

    @include('welcome.how-it-works')

    @include('welcome.customize-distributions')

    @include('welcome.secondary-cta')

    @include('welcome.about')
		
@stop


@section('title')
    Token Distributions
@stop
