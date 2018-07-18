<?php
    // $num_tx = $row->addressCount();
    // $num_complete = $row->countComplete();
	$user_account_data = User::getUserAccountData($row->id);
?>

<div class="distribution-index__row">
	<p>
		<span>UserId:</span>
		<span>{{ $row->id }}</span>
	</p>
	<p>
		<span>Name:</span>
		<span>{{ $row->name }}</span>
	</p>
	<p>
		<span>Email:</span>
		<span>{{ $row->email }}</span>
	</p>
	<p>
		<span>Username:</span>
		<span>{{ $row->username }}</span>
	</p>
	
	@if($user_account_data)
		<div style="padding: 15px; border: 1px solid #eee; border-radius: 5px;">
			<p><b>Account Details</b></p>
			<div style="padding: 15px;">
				<div>
					<span>First Name:</span>
					<span>{{ $user_account_data->first_name }}</span>
				</div>
				<div>
					<span>Last Name:</span>
					<span>{{ $user_account_data->last_name  }}</span>
				</div>
				<div>
					<span>Email:</span>
					<span>{{ $user_account_data->email }}</span>
				</div>
				<div>
					<span>Phone Number:</span>
					<span>{{ $user_account_data->phone_number }}</span>
				</div>
				<div>
					<span>Website:</span>
					<span>{{ $user_account_data->website }}</span>
				</div>
				<div>
					<span>Company Name:</span>
					<span>{{ $user_account_data->company_name }}</span>
				</div>
				<div>
					<span>Company Address:</span>
					<span>{{ $user_account_data->company_address }}</span>
				</div>
				<div>
					<span>Token Name:</span>
					<span>{{ $user_account_data->token_name }}</span>
				</div>
				<div>
					<span>Token Description:</span>
					<span>{{ $user_account_data->token_description }}</span>
				</div>
				<div>
					<span>Token Exchanges Listed:</span>
					<span>{{ $user_account_data->token_exchanges_listed }}</span>
				</div>
			</div>
		</div>
	@endif

	<div>
		@if($row->admin)
			<div>
				<span class="action-required">
					<i class="fa fa-star"></i>
					<span>Admin</span>
					{{ $row->admin }}
				</span>
			</div>
		@else
			@if(!$row->tac_accept)
				<div>
					<span class="action-required-notice">
						<i class="fa fa-exclamation-circle"></i>
						<span>Needs Terms and Conditions Approval</span>
					</span>
				</div>
			@endif
			@if(!$user_account_data)
				<div>
					<span class="action-required-notice">
						<i class="fa fa-exclamation-circle"></i>
						<span>Needs Account Details</span>
					</span>
				</div>
			@endif
			@if($row->tac_accept AND !$row->approval_admin_id)
				<div>
					<a class="distribution-index__row__cta" href="{{ route('account.admin.users.approve', $row->id) }}">
						<span>Approve Account</span>
					</a>
				</div>
			@endif
			@if($row->tac_accept AND $row->approval_admin_id)
				<div>
					<span class="action-complete-notice">
						<i class="fa fa-check"></i>
						<span>Account Approved</span>
					</span>
				</div>
			@endif
		@endif
	</div>
</div>