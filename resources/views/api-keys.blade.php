@extends('app')

@section('content')
<h1>My API Keys</h1>
<p>
    Use the interface below to generate and manage key pairs for use in the Bitsplit API.
</p>
<p>
    <a href="#">Click here</a> to view API documentation.
</p>
<hr>
@if(!$keys OR count($keys) == 0)
	<p>
		No API key pairs found.
	</p>
@else
	<table class="table table-bordered data-table client-app-table">
		<thead>
			<tr>
				<th>Client Key</th>
				<th>Register Date</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			@foreach($keys as $key)
				<tr>
					<td>{{ $key->client_key }}</td>
					<td>{{ date('Y/m/d', strtotime($key->created_at)) }}</td>
					<td class="table-action">
						<a href="#" class="btn  btn-success" class="View API Secret" data-toggle="modal" data-target="#view-key-modal-{{ $key->client_key }}"><i class="fa fa-key"></i> Show Secret</a>
						<div class="modal fade" id="view-key-modal-{{ $key->client_key }}" tabindex="-1" role="dialog">
						  <div class="modal-dialog" role="document">
							<div class="modal-content">
							  <div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h3 class="modal-title" id="myModalLabel">Client App API Keys</h3>
							  </div>
							  <div class="modal-body">
								<div class="well">
									<h4 class="text-center">
										<strong>Client Key:</strong><br><br>
										{{ $key->client_key }}
									</h4>
								</div>
								<div class="well">
									<h4 class="text-center">
										<strong>API Secret:</strong><br><br>
										{{ $key->client_secret }}
									</h4>
								</div>								
							  </div>
							  <div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							  </div>
							</div>
						  </div>
						</div>						
						<a href="{{ route('account.api-keys.delete', $key->client_key) }}" class="btn  btn-danger delete" class="Delete"><i class="fa fa-close"></i> Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>

@endif

<p>
	<a href="{{ route('account.api-keys.create') }}" class="btn btn-lg btn-success"><i class="fa fa-plus"></i> New Key Pair</a>
</p>
@endsection

@section('title')
	API Key Management
@stop
