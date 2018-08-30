<?php
    // $num_tx = $row->addressCount();
    // $num_complete = $row->countComplete();
	$user_data = $row->getAccountData();
?>

<div class="distribution-index__row">
	<p>
		<span>Status:</span>
		<span><strong>{{ $row->status }}</strong></span>
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
	
	@if($user_data)
		<div style="padding: 15px; border: 1px solid #eee; border-radius: 5px;">
			<p><b>Account Details</b></p>
			<div style="padding: 15px;">
				@foreach($user_data as $key => $datum)
					<div>
						<span><strong>{{ $datum->field->name }}: </strong></span>
						@if($datum->field->type === 'toggle')
							<span>@if($datum->value) Yes @else No @endif</span>
						@else
							<span>{{ $datum->value }}</span>
						@endif
					</div>
				@endforeach
			</div>
		</div>
	@endif

	<div>
		@if(!$row->approval_admin_id)
			<div class="distribution-index__row__cta-container">
				<a class="distribution-index__row__cta" href="{{ route('account.admin.users.approve', $row->id) }}">
					<span>Approve Account</span>
				</a>
			</div>
			<div class="distribution-index__row__cta-container" style="border: red 1px solid">
				<a class="distribution-index__row__cta decline" href="{{ route('account.admin.users.decline', $row->id) }}">
					<span>Decline Account</span>
				</a>
			</div>
			<div class="distribution-index__row__cta-container">
				<a class="distribution-index__row__cta" href="#" data-toggle="modal" data-target="#email">
					<span>Send email</span>
				</a>
			</div>
			<!-- EMAIL MODAL -->
			<div id="email" class="modal fade" tabindex="-1" role="dialog">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h3 class="modal-title" id="myModalLabel">Send email to {{ $row->name }}</h3>
						</div>
						<form action="{{ route('account.admin.users.message', $row->id) }}" method="post">
							{{ csrf_field() }}
							<div class="modal-body">
								<div class="form-group">
									<label for="email-message">Message you want to send</label>
									<textarea name="message" id="email-message" cols="30" rows="10" class="form-control"></textarea>
								</div>
							</div>
							<div class="modal-footer">
								<button type="submit" class="btn btn-default btn-success">Send</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		@elseif($row->approval_admin_id && $user->admin)
			@if(!$row->admin)
				@if(!$row->hasRole('moderator'))
					<div class="distribution-index__row__cta-container">
						<a class="distribution-index__row__cta orange" href="{{ route('account.admin.users.make_moderator', $row->id) }}">
							<span>Make this user a moderator</span>
						</a>
					</div>
				@else
					<div class="distribution-index__row__cta-container">
						<a class="distribution-index__row__cta orange" href="{{ route('account.admin.users.remove_moderator', $row->id) }}">
							<span>Remove moderator status</span>
						</a>
					</div>
				@endif
					<div class="distribution-index__row__cta-container" style="border: #E65100 1px solid">
						<a class="distribution-index__row__cta moderator" href="{{ route('account.admin.users.make_admin', $row->id) }}">
							<span>Make this user an admin</span>
						</a>
					</div>
			@else
				<div class="distribution-index__row__cta-container" style="border: #E65100 1px solid">
					<a class="distribution-index__row__cta moderator" href="{{ route('account.admin.users.remove_admin', $row->id) }}">
						<span>Remove Admin status</span>
					</a>
				</div>
			@endif
		@endif
	</div>
</div>