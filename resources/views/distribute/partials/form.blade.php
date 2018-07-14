<form action="{{ route('distribute.post') }}" method="post" enctype="multipart/form-data">
	<input type="hidden" name="_token" value="{{ csrf_token() }}" />
	<div class="distro-form">
		<div class="distro-form__section">
			<div class="distro-form__section__content">
				<div class="form-group">
					<label for="asset">Name of Token You Want to Distribute</label>
					<input type="text" class="form-control" id="asset" name="asset" placeholder="(e.g LTBCOIN)" value="{{ old('asset') }}" required />
				</div>
				<div class="form-group" id="percent_asset_total">
					<label for="asset_total">Total Amount of Tokens You Want to Distribute</label>
					<input type="text" class="form-control numeric-only" id="asset_total" name="asset_total" value="{{ old('asset_total') }}" placeholder="" style="width: 150px;"/>
				</div>
				<div class="form-group">
					<label for="use_fuel">Use available fuel in your account for BTC fee?</label><br>
					<ul class="yes-no-toggle">
						<li
							@click="useAccountFuel = true"
						><a><span class="yes" v-bind:class="{active: useAccountFuel}">Yes</span></a></li><li 
						class="no"
							@click="useAccountFuel = false"
						><a><span class="no" v-bind:class="{active: !useAccountFuel}">No</span></a></li>
					</ul>
					<input
						v-show="false"
						v-model="useAccountFuel"
						type="checkbox"
						name="use_fuel"
						id="use_fuel"
						value="1"
					/>
				</div>
				<div class="form-group dropdown">
					<label for="calculation_type">How do you want to distribute your tokens?</label>
					<div class="fancy-form-select-container">
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="calculationType = 'even'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: calculationType == 'even'}"
							>
								<i class="fa fa-pie-chart" style="color: #E65100;"></i>
								<span class="title">Proportionally</span>
								<small>Recipients will receive token amounts in proportion to their contributions to Folding@Home</small>
							</div>
						</div>
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="calculationType = 'static'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: calculationType == 'static'}"
							>
								<i class="fa fa-th" style="color: #AB47BC;"></i>
								<span class="title">Uniformally</span>
								<small>All recipients of your token will receive the same amount of your token.</small>
							</div>
						</div>
					</div>
					<select
						v-show="false"
						v-model="calculationType"
						name="calculation_type"
						id="calculation_type"
						class="form-control"
					>
						<option value="even">Proportional</option>
						<option value="static">Uniform</option>
					</select>
				</div>
			</div>
		</div>
		<div class="distro-form__section">
			<div class="distro-form__section__content">
				<div class="form-group dropdown">
					<label for="distribution_class">Who should receive your token?</label>
					<div class="fancy-form-select-container">
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="distributionClass = 'All Folders'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: distributionClass == 'All Folders'}"
							>
								<i class="fa fa-group" style="color: #009688;"></i>
								<span class="title">All Folders</span>
								<small>All Folding@Home participants will receive your token</small>
							</div>
						</div>
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="distributionClass = 'Minimum FAH points'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: distributionClass == 'Minimum FAH points'}"
							>
								<i class="fa fa-list" style="color: #D4E157;"></i>
								<span class="title">Only Folders with Minumum FAH points</span>
								<small>Only Folding@Home participants with sufficient FAH points will receive your token</small>
							</div>
						</div>
					</div>

					<div class="fancy-form-select-container">
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="distributionClass = 'Top Folders'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: distributionClass == 'Top Folders'}"
							>
								<i class="fa fa-trophy" style="color: #795548;"></i>
								<span class="title">Only the Top Folders</span>
								<small>Only the top contributors to the Folding@Home network will receive your token</small>
							</div>
						</div>
						<div class="fancy-form-select-container__entry centered">
							<div
								@click="distributionClass = 'Random'"
								class="fancy-form-select-container__entry__content two"
								v-bind:class="{active: distributionClass == 'Random'}"
							>
								<i class="fa fa-random" style="color: #607D8B;"></i>
								<span class="title">Random Folders</span>
								<small>Recipients of your token will be randomly selected</small>
							</div>
						</div>
					</div>

					<select
						v-show="false"
						v-model="distributionClass"
						name="distribution_class"
						id="distribution_class"
						class="form-control"
					>
						<option value="All Folders">All Folders</option>
						<option value="Minimum FAH points">Minimum FAH points</option>
						<option value="Top Folders">Top Folders</option>
						<option value="Random">Random</option>
						<option value="unique">Unique Distribution</option>
					</select>
				</div>
				<div id="scan_distros_from" class="form-group dropdown" style="display: none;">
					<label for="scan_distros_from_select">Scan Previous Distributions From</label>
					<select id="scan_distros_from_select" name="scan_distros_from" class="form-control">
						<option value="My Account">My Account</option>
						<option value="Official FLDC">Official FLDC</option>
						<option value="All Accounts">All Acounts</option>
					</select>
				</div>
				<div id="minimum_fah_points_wrapper" class="form-group" style="display: none;">
					<label for="minimum_fah_points">Minimum Required FAH Points (New credit)</label>
					<input type="text" min="0" name="minimum_fah_points" id="minimum_fah_points" class="form-control">
				</div>
				<div id="amount_top_folders_wrapper" class="form-group" style="display: none;">
					<label for="amount_top_folders">Amount of Top Folders to Select</label>
					<input type="number" min="3" value="100" name="amount_top_folders" id="amount_top_folders" class="form-control">
				</div>
				<div id="amount_random_folders_wrapper" style="display: none;">
					<div class="form-group">
						<label for="amount_random_folders">Amount of Random Folders to Select</label>
						<input type="number" min="3" name="amount_random_folders" id="amount_random_folders" value="3" class="form-control" style="width: 150px;">
					</div>
					<div class="form-group checkbox">
						<input type="checkbox" style="margin-left: 10px; margin-top: 2px;" name="weight_cache_by_fah" id="weight_cache_by_fah" value="1" />
						<label for="weight_cache_by_fah" style="padding-left: 35px; font-size: 13px;" >Weight chance by FAH points?</label>
					</div>
				</div>
				<div style="display: flex;">
					<div class="form-group" style="flex: 1; padding-right: 10px;">
						<label for="folding_start_date">Folding Start Date</label>
						<input type="text" id="folding_start_date" name="folding_start_date" value="{{ old('folding_start_date') }}" class="form-control datetimepicker_folding" />
	                </div>
	                <div class="form-group" style="flex: 1; padding-left: 10px;">
						<label for="folding_end_date">Folding End Date</label>
						<input type="text" id="folding_end_date" name="folding_end_date" value="{{ old('folding_end_date') }}" class="form-control datetimepicker_folding" />
					</div>  
				</div>
				<div class="form-group">
					<label for="btc_fee_rate">
						<span>Set a Custom Bitcoin Network Fee?</span>
						<small>(Optional)</small>
					</label>
					<br>
					<ul class="yes-no-toggle">
						<li
							@click="customBitcoinNetworkFee = true"
						><a><span class="yes" v-bind:class="{active: customBitcoinNetworkFee}">Yes</span></a></li><li 
						class="no"
							@click="customBitcoinNetworkFee = false"
						><a><span class="no" v-bind:class="{active: !customBitcoinNetworkFee}">No</span></a></li>
					</ul>
					<div v-show="customBitcoinNetworkFee">
						<br>
						<label for="btc_fee_rate">
							<span>Bitcoin Network Fee:</span>
						</label>
						<input
							type="text"
							class="form-control"
							id="btc_fee_rate"
							name="btc_fee_rate"
							placeholder="{{ Config::get('settings.miner_satoshi_per_byte') }}"
							minimum="5"
							maximum="600"
						/>
						<small>
							* This is an advanced feature. Rates are defined in <em>satoshis per byte</em>, enter a number between {{ Config::get('settings.min_fee_per_byte') }} and {{ Config::get('settings.max_fee_per_byte') }}.<br>
	                        See <a href="https://bitcoinfees.earn.com" target="_blank">https://bitcoinfees.earn.com</a> for help determining a rate.
						</small>
					</div>
				</div>
				<div class="form-submit">
					<button type="submit" class="btn btn-lg btn-success button wide"><i class="fa fa-check"></i> Initiate Distribution</button>
				</div>	
			</div>
		</div>
	</div>														
</form>