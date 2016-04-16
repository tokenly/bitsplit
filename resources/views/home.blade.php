@extends('app')

@section('content')
<div class="row">
	<div class="col-lg-6">
		<h1>BitSplit - Distribute Tokens</h1>
		<p>
			Use this tool to mass distribute Counterparty tokens to many addresses. <br>
			Enter in your token name to pay with and either upload a .csv file, or manually
			enter in addresses in the following format:
		</p>
		<blockquote style="font-size: 14px;">
			&lt;Bitcoin Address&gt;, &lt;Amount&gt;<br>
			<small>(one per line) </small>
		</blockquote>
		<p>
			A deposit address will be generated for you along with a total amount of tokens + 
			total amount of <em>fuel</em> (bitcoin) it will cost. Fuel can be paid directly, 
			or sourced from your account <em>fuel address</em>. Once confirmed, your tokens will enter
			the distribution process.
		</p>
		<hr>
		<div id="new-distro-form">
			<form action="{{ route('distribute.post') }}" method="post" enctype="multipart/form-data">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				<div class="form-group">
					<label for="asset">Token Name</label>
					<input type="text" class="form-control" id="asset" name="asset" placeholder="(e.g LTBCOIN)" required />
				</div>
				<div class="form-group">
					<label for="label">Distribution Label</label>
					<input type="text" class="form-control" id="label" name="label" placeholder="(optional)" />
				</div>
				<div class="form-group">
					<label for="value_type">Input Value Type</label>
					<select name="value_type" id="value_type" class="form-control">
						<option value="fixed">Fixed</option>
						<option value="percent">Percentage</option>
					</select>
					<small>
						*fixed = must define exact amount per address<br>
						*percentage = set a total to send and define percents per address
					</small>
				</div>
				<div class="form-group" id="percent_asset_total" style="display: none;">
					<label for="asset_total">Total Tokens to Send</label>
					<input type="text" class="form-control numeric-only" id="asset_total" name="asset_total" placeholder="" />
				</div>
				<div class="form-group">
					<label for="csv_list">Upload .CSV file</label>
					<input type="file" name="csv_list" id="csv_list" />
				</div>
				<div class="form-group checkbox">
					<input type="checkbox" style="margin-left: 10px; margin-top: 2px;" name="cut_head" id="cut_head" value="1" checked="checked" />
					<label for="cut_head" style="padding-left: 35px; font-size: 13px;" >Remove heading line from csv?</label>
				</div>
				<p>
					<strong>OR</strong>
				</p>
				<div class="form-group">
					<label for="address_list">Manually Enter Address List:</label>
					<textarea id="address_list" style="height: 150px;" name="address_list" class="form-control" placeholder="&lt;address&gt;,&lt;amount&gt;"></textarea>
				</div>	
				<div class="form-submit">
					<button type="submit" class="btn btn-lg btn-success"><i class="fa fa-check"></i> Initiate Distribution</button>
				</div>															
			</form>
		</div>
	</div>
	<div class="col-lg-5 col-lg-offset-1">
		<h3>My Account</h3>
		<ul>
			<li><strong>User:</strong> {{ $user->username }}</li>
			<li><strong>BTC Fuel Balance:</strong> 0</li>
			<li><strong>BTC Fuel Address:</strong> <a href="" target="_blank"></a></li>
			<li><strong>Fuel Spent:</strong> </li>
			<li><strong>Distributions Completed:</strong> 0</li>
			<li><strong># Distribution Txs:</strong> 0</li>
		</ul>
		<hr>
		<h3>Distribution History</h3>
		<p>
			You have made no distributions yet.
		</p>
	</div>
</div>
@endsection

@section('title')
	BitSplit Dashboard
@stop
