@extends('app')

@section('content')
	
	<div class="content padded">
		<a href="{{ route('account.admin.users') }}">
			<i class="fa fa-chevron-left"></i>
			<span>User Dashboard (Admin)</span>
		</a>
		<div class="page-information">
			<h1>User #{{$this_user->id}} (Admin view)</h1>
		</div>

		<div class="distribution-index">
			@include('admin.partials.user', array('row' => $this_user))
		</div>
	</div>

@stop

@section('title')
	User #{{$this_user->id}} (Admin)
@stop
