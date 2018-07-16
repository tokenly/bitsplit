<form
	action="{{ route('distribute.post') }}"
	method="post"
	enctype="multipart/form-data"
	v-on:submit="checkValid"
>
	<input type="hidden" name="_token" value="{{ csrf_token() }}" />
	<div class="distro-form">
		<div class="distro-form__section">
			<div class="distro-form__section__content">
				<div 
					class="form-group"
					v-bind:class="{'action-required': invalidInputWarning && !validToken}"
				>
					<label for="asset">Name of Token You Want to Distribute</label>
					<input
						v-model="tokenName"
						type="text"
						class="form-control"
						id="asset"
						name="asset"
						placeholder="(e.g LTBCOIN)"  
					/>
					<div 
						v-if="invalidInputWarning && !validToken"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please enter a valid token name</span>
						</span>
					</div>
				</div>
				<div 
					class="form-group"
					id="percent_asset_total"
					v-bind:class="{'action-required': invalidInputWarning && !validTokenAmount}"
				>
					<label for="asset_total">
						<span>Total Amount of</span>
						<span v-if="!validToken">Tokens</span>
						<span v-if="validToken" style="color: #1E88E5;">@{{ tokenName }}</span>
						<span>You Want to Distribute</span>
					</label>
					<input
						v-model="tokenAmount"
						type="text"
						class="form-control numeric-only"
						id="asset_total"
						name="asset_total" 
						placeholder="" 
						style="width: 150px;"
					/>
					<div 
						v-if="invalidInputWarning && !validTokenAmount"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please enter a valid amount of</span>
							<span v-if="validToken">@{{ tokenName }}</span>
							<span v-if="!validToken">tokens</span>
						</span>
					</div>
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
				<div 
					class="form-group dropdown"
					v-bind:class="{'action-required': invalidInputWarning && !calculationType}"
				>
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

					<div 
						v-if="invalidInputWarning && !calculationType"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please choose how to distribute your</span>
							<span v-if="validToken">@{{ tokenName }}</span>
							<span v-if="!validToken">tokens</span>
						</span>
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
				<div 
					class="form-group dropdown"
					v-bind:class="{'action-required': invalidInputWarning && !distributionClass}"
				>
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
								<small>Only Folding@Home participants with enough FAH points will receive your token</small>
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

					<div 
						v-if="invalidInputWarning && !distributionClass"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please choose the recipients of your</span>
							<span v-if="validToken">@{{ tokenName }}</span>
							<span v-if="!validToken">tokens</span>
						</span>
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
				<div 
					v-if="distributionClass == 'Minimum FAH points'"
					id="minimum_fah_points_wrapper"
					class="form-group"
					v-bind:class="{'action-required': invalidInputWarning && !minFAHPoints}"
				>
					<label for="minimum_fah_points">Minimum Required FAH Points (New credit)</label>
					<input 
						v-model="minFAHPoints"
						type="text"
						min="0"
						name="minimum_fah_points"
						id="minimum_fah_points"
						class="form-control"
						style="width: 150px;"
					>
					<div 
						v-if="invalidInputWarning && !minFAHPoints"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please choose minimum FAH points required to receive your</span>
							<span v-if="validToken">@{{ tokenName }}</span>
							<span v-if="!validToken">tokens</span>
						</span>
					</div>
				</div>
				<div 
					v-if="distributionClass == 'Top Folders'"
					id="amount_top_folders_wrapper" 
					class="form-group"
					v-bind:class="{'action-required': invalidInputWarning && !amountTopFolders}"
				>
					<label for="amount_top_folders">Number of Top Folders to Select</label>
					<div>
							<span
								@click="selectTopFolders(3)"
								class="select-button"
								v-bind:class="{'active': amountTopFolders == 3 && !showTopFoldersInput}"
							>
								<i class="fa fa-check"></i>
								<span>3</span>
							</span>
							<span
								@click="selectTopFolders(10)"
								class="select-button"
								v-bind:class="{'active': amountTopFolders == 10 && !showTopFoldersInput}"
							>
								<i class="fa fa-check"></i>
								<span>10</span>
							</span>
							<span
								@click="selectTopFolders(50)"
								class="select-button"
								v-bind:class="{'active': amountTopFolders == 50 && !showTopFoldersInput}"
							>
								<i class="fa fa-check"></i>
								<span>50</span>
							</span>
							<span
								@click="selectTopFolders(100)"
								class="select-button"
								v-bind:class="{'active': amountTopFolders == 100 && !showTopFoldersInput}"
							>
								<i class="fa fa-check"></i>
								<span>100</span>
							</span>
							<span
								@click="selectTopFolders(500)" 
								class="select-button"
								v-bind:class="{'active': amountTopFolders == 500 && !showTopFoldersInput}"
							>
								<i class="fa fa-check"></i>
								<span>500</span>
							</span>
							<span
								@click="showTopFoldersInput = true"
								class="select-button"
								v-bind:class="{'active': showTopFoldersInput == true}"
							>
								<i class="fa fa-check"></i>
								<span>Other Number</span>
							</span>
						</div>
					<input 
						v-show="showTopFoldersInput"
						v-model="amountTopFolders" 
						type="number" 
						min="3" 
						value="100" 
						name="amount_top_folders" 
						id="amount_top_folders" 
						class="form-control" 
						style="width: 150px;"
					/>

					<div 
						v-if="invalidInputWarning && !amountTopFolders"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please choose the number of top folders who should receive your</span>
							<span v-if="validToken">@{{ tokenName }}</span>
							<span v-if="!validToken">tokens</span>
						</span>
					</div>
				</div>
				<div 
					v-if="distributionClass == 'Random'"
					id="amount_random_folders_wrapper"
					class="form-group"
				>
					<div
						class="form-group"
						v-bind:class="{'action-required': invalidInputWarning && !amountRandomFolders}"
					>
						<label for="amount_random_folders">Number of Random Folders to Select</label>
						<div>
							<span
								@click="selectRandomNumber(3)"
								class="select-button"
								v-bind:class="{'active': amountRandomFolders == 3 && !showRandomInput}"
							>
								<i class="fa fa-check"></i>
								<span>3</span>
							</span>
							<span
								@click="selectRandomNumber(10)"
								class="select-button"
								v-bind:class="{'active': amountRandomFolders == 10 && !showRandomInput}"
							>
								<i class="fa fa-check"></i>
								<span>10</span>
							</span>
							<span
								@click="selectRandomNumber(50)"
								class="select-button"
								v-bind:class="{'active': amountRandomFolders == 50 && !showRandomInput}"
							>
								<i class="fa fa-check"></i>
								<span>50</span>
							</span>
							<span
								@click="selectRandomNumber(100)"
								class="select-button"
								v-bind:class="{'active': amountRandomFolders == 100 && !showRandomInput}"
							>
								<i class="fa fa-check"></i>
								<span>100</span>
							</span>
							<span
								@click="selectRandomNumber(500)" 
								class="select-button"
								v-bind:class="{'active': amountRandomFolders == 500 && !showRandomInput}"
							>
								<i class="fa fa-check"></i>
								<span>500</span>
							</span>
							<span
								@click="showRandomInput = true"
								class="select-button"
								v-bind:class="{'active': showRandomInput == true}"
							>
								<i class="fa fa-check"></i>
								<span>Other Number</span>
							</span>
						</div>
						<input
							v-show="showRandomInput" 
							v-model="amountRandomFolders"
							type="number"
							min="3"
							name="amount_random_folders"
							id="amount_random_folders"
							class="form-control"
							style="width: 150px;"/>
						<div 
							v-if="invalidInputWarning && !amountRandomFolders"
							class="action-required-container"
						>
							<span class="action-required-notice">
								<i class="fa fa-exclamation-circle"></i>
								<span>Please choose the number of random folders who should receive your</span>
								<span v-if="validToken">@{{ tokenName }}</span>
								<span v-if="!validToken">tokens</span>
							</span>
						</div>	
					</div>
					<div class="form-group">
						<label for="weight_cache_by_fah">Weight chance by FAH points?</label><br>
						<ul class="yes-no-toggle">
							<li
								@click="weightChanceByFAHPoints = true"
							><a><span class="yes" v-bind:class="{active: weightChanceByFAHPoints}">Yes</span></a></li><li 
							class="no"
								@click="weightChanceByFAHPoints = false"
							><a><span class="no" v-bind:class="{active: !weightChanceByFAHPoints}">No</span></a></li>
						</ul>
						<input 
							v-show="false"
							v-model="weightChanceByFAHPoints"
							type="checkbox"
							style="margin-left: 10px; margin-top: 2px;"
							name="weight_cache_by_fah"
							id="weight_cache_by_fah"
							value="1" />
					</div>
				</div>
				<div 
					class="form-group"
					v-bind:class="{'action-required': invalidInputWarning && (!startDate || !endDate)}"
				>
					<div style="display: flex;">
						<div 
							class="form-group" 
							style="flex: 1; padding-right: 10px; margin-bottom: 0px;"
						>
							<label for="folding_start_date">Folding Start Date</label>
							<input
								v-model="startDate"
								type="date"
								name="folding_start_date"
							/>
		                </div>
		                <div class="form-group" style="flex: 1; padding-left: 10px; margin-bottom: 0px;">
							<label for="folding_end_date">Folding End Date</label>
							<input 
								v-model="endDate"
								type="date" 
								name="folding_end_date" 
							/>
						</div>  
					</div>
					<div 
						v-if="invalidInputWarning &&  (!startDate || !endDate)"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please enter valid start/end dates</span>
						</span>
					</div>
				</div>
				<div 
					class="form-group"
					v-bind:class="{'action-required': invalidInputWarning &&  !validBTCNetworkFee}"
				>
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
						<div>
							<div class="inline-input-container">
								<div style="display: flex;">
									<input
										v-model="btcNetworkFee"
										type="text"
										id="btc_fee_rate"
										name="btc_fee_rate"
										minimum="{{ Config::get('settings.min_fee_per_byte') }}"
										maximum="{{ Config::get('settings.max_fee_per_byte') }}"
										style="width: 75px;"
									/>
									<span class="inline-input-label">Satoshis per byte</span>
								</div>
							</div>
						</div>
						<small>
							<span>Min: {{ Config::get('settings.min_fee_per_byte') }}</span>
							<span>Max: {{ Config::get('settings.max_fee_per_byte') }}</span>
							<br>
							<span>See <a href="https://bitcoinfees.earn.com" target="_blank">https://bitcoinfees.earn.com</a> for help determining a rate.</span>
						</small>
					</div>

					<div 
						v-if="invalidInputWarning &&  !validBTCNetworkFee"
						class="action-required-container"
					>
						<span class="action-required-notice">
							<i class="fa fa-exclamation-circle"></i>
							<span>Please enter valid btc network fee of between {{ Config::get('settings.min_fee_per_byte') }} and {{ Config::get('settings.max_fee_per_byte') }} satoshis per byte</span>
						</span>
					</div>
				</div>
				<div class="form-submit">
					<button
						type="submit"
						class="btn btn-lg btn-success button wide"
						v-bind:class="{'disabled': !validConfiguration}"
					>
						<span>Initiate Distribution</span>
						<i class="fa fa-arrow-right"></i>
					</button>
				</div>	
			</div>
		</div>
	</div>														
</form>