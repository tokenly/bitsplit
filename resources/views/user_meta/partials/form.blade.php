<div>
	<div style="display: flex;">
		<div class="form-group" style="flex: 1; padding-right: 10px;">
			<label>First Name</label><br />
			<input 
				type="text"
				name="first_name"
				class="form-control"
				v-model="firstName"
			/>
		</div>
		<div class="form-group" style="flex: 1; padding-left: 10px;"> 
			<label>Last Name</label><br />
			<input 
				type="text"
				name="last_name"
				class="form-control"
				v-model="lastName"
			/>
		</div>
	</div>
	<div style="display: flex;">
		<div class="form-group" style="flex: 1; padding-right: 10px;">
			<label>Email</label><br />
			<input 
				type="text"
				name="email"
				class="form-control"
				v-model="email"
			/>
		</div>
		<div class="form-group" style="flex: 1; padding-left: 10px;">
			<label>
				<span>Phone Number</span>
				<small class="optional">(Optional)</small>
			</label><br />
			<input 
				type="text"
				name="phone_number"
				class="form-control"
				v-model="phoneNumber"
			/>
		</div>
	</div>
	<div class="form-group">
		<label>
			<span>Company Name</span>
			<small class="optional">(Optional)</small>
		</label><br />
		<input 
			type="text"
			name="company_name"
			class="form-control"
			v-model="companyName"
		/>
	</div>

	<div class="form-group">
		<label>
			<span>Website URL</span>
			<small class="optional">(Optional)</small>
		</label><br />
		<input 
			type="text"
			name="website"
			class="form-control"
			v-model="website"
		/>
	</div>

	<div class="form-group">
		<label>
			<span>Company Address</span>
			<small class="optional">(Optional)</small>
		</label><br />
		<input 
			type="text"
			name="company_address"
			class="form-control"
			v-model="companyAddress"
		/>
	</div>

	<div class="form-group">
		<label>Name of Token You Want to Distribute</label><br />
		<input 
			type="text"
			name="token_name"
			class="form-control"
			v-model="tokenName"
		/>
	</div>

	<div class="form-group">
		<label>A description of the token you want to distribute</label><br />
		<textarea 
			type="text"
			name="token_description"
			class="form-control"
			v-model="tokenDescription"
		/>
	</div>

	<div class="form-group">
		<label>Is the token you want to distribute listed on any exchanges?</label><br />
		<ul class="yes-no-toggle">
			<li
				@click="isListed = true"
			><a><span class="yes" v-bind:class="{active: isListed}">Yes</span></a></li><li 
				class="no"
				@click="isListed = false"
			><a><span class="no" v-bind:class="{active: !isListed}">No</span></a></li>
		</ul>
	</div>

	<div 
		v-if="isListed"
		class="form-group" 
	>
		<label>Which exchanges list the token you want to distribute?</label><br />
		
		<div>
			<span
				v-for="exchange in exchanges" 
				class="select-button"
				v-bind:class="{'active': tokenExchangesListed.indexOf(exchange) > -1}"
				@click="toggleListing(exchange)"
			>
				<i class="fa fa-check"></i>
				<span>@{{ exchange}}</span>
			</span>
		</div>
		<input 
			type="text"
			name="token_description"
			v-model="tokenExchangesListed"
			v-show="false"
		/>
	</div>

	<div>
		<button 
			type="submit" 
			class="btn btn-lg btn-success button wide"
			v-bind:class="{'disabled': !validConfiguration}"
		>
			<span>Complete My Account</span>
			<i class="fa fa-arrow-right"></i>
		</button>
	</div>
</div>