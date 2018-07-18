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
		<div class="dashboard__main">
			<div class="dashboard__main__content">
				<h2>My Distributions</h2>
				<div class="welcome-section-divider"></div>
				<a 
					class="dashbaord__main__content__cta" 
					href="{{ route('distribute.new') }}">Create a New Distribution</a>
				<div>
					@include('inc.my-distributions')
				</div>
			</div>
		</div>

		<div class="dashboard__secondary">
			<div class="dashboard__secondary__content">
				<h2>My Account</h2>
				<div class="welcome-section-divider"></div>
				<div>
					@include('inc.my-account-details')
				</div>	
			</div>
			@if(Auth::user()->admin)
				<div class="dashboard__secondary__content">
					<h2>Admin</h2>
					<div class="welcome-section-divider"></div>
					<div>
						@if(User::needsApprovalCount())
							<a
								href="{{ route('account.admin.users') }}"
								class="action-required-notice centered" 
								style="cursor: pointer;"
							>
								<i class="fa fa-exclamation-circle"></i>
								<span>{{ User::needsApprovalCount() }}</span>
								<span>new users need admin approval</span>
							</a>
						@else
							<p>No action required right now.</p>
						@endif
					</div>	
				</div>
			@endif
		</div>
	</div>
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop
