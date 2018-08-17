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

				@if(Auth::user()->isAdmin)
				<div>&nbsp;</div>
				<h2>FLDC Escrow</h2>
				<div class="welcome-section-divider"></div>
				<div>
					@include('inc.fldc-escrow-details')
				</div>	
				@endif
			</div>


			@if(Auth::user()->isModerator)
				<div class="dashboard__secondary__content">
					<h2>Admin</h2>
					<div class="welcome-section-divider"></div>
					
					<a
						href="{{ route('account.admin.users') }}"
						class="select-button" 
						style="cursor: pointer; position: absolute; right: 0px; top: 20px;"
					>
						<span>Go to Admin Dashboard</span>
					</a>

					@if(User::needsApprovalCount())
						<div>
						
							<a
								href="{{ route('account.admin.users') }}"
								class="action-required-notice centered" 
								style="cursor: pointer;"
							>
								<i class="fa fa-exclamation-circle"></i>
								<span>{{ User::needsApprovalCount() }}</span>
								<span>new users need admin approval</span>
							</a>
						</div>
					@else
						<div class="blank-state-container centered">
							<p class="blank-state-text">
								<span>No action required right now.</span>
							</p>
						</div>
					@endif	
				</div>
			@endif
		</div>
	</div>
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop
