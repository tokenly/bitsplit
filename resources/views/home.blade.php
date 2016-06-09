@extends('app')

@section('content')
<div class="row">
	<div class="col-lg-6">
		<h1>Bitsplit - Distribute Tokens</h1>
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
					<strong class="text-info">OR</strong>
				</p>
				<div class="form-group">
					<label for="address_list">Manually Enter Address List:</label>
					<textarea id="address_list" style="height: 150px;" name="address_list" class="form-control" placeholder="&lt;address&gt;,&lt;amount&gt;"></textarea>
				</div>	
				<div class="form-group checkbox">
					<input type="checkbox" style="margin-left: 10px; margin-top: 2px;" name="use_fuel" id="use_fuel" value="1" checked="checked" />
					<label for="use_fuel" style="padding-left: 35px; font-size: 13px;" >Use available fuel for BTC fee?</label>
				</div>				
				<div class="form-submit">
					<button type="submit" class="btn btn-lg btn-success"><i class="fa fa-check"></i> Initiate Distribution</button>
				</div>															
			</form>
		</div>
	</div>
	@include('inc.dash-sidebar')
</div>
@endsection

@section('title')
	Bitsplit Dashboard
@stop
