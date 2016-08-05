@extends('app')

@section('content')

<p>
	Sign in using<br> <strong>Tokenpass</strong>:
</p>
<a href="{{ route('account.authorize') }}" class="btn btn-primary">Login or Register Now</a>


@stop

@section('title')
Login
@stop

