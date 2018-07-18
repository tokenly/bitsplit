@extends('app')

@section('content')
	
	<div class="content padded">
		<div class="page-information">
			<h1>Users Dashboard (Admin)</h1>
		</div>

		<div class="distribution-index">
			@foreach($all_users as $row)
				<div class="distribution-index__container half">
					@include('admin.partials.user')
				</div>
			@endforeach
		</div>
	</div>

@stop

@section('title')
	Users Dashboard (Admin)
@stop
