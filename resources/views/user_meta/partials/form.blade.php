<form 
	method="post"
	v-bind:action="actionPath"
	v-on:submit="checkIfValid"
>
	<div 
		class="form-group"
		v-bind:class="{'action-required': inputWarning && (!validFirstName || !validLastName)}"
		style="display: flex;"
	>
		<div class="form-group" style="flex: 1; padding-right: 10px;">
			<label>First Name</label><br />
			<input 
				type="text"
				name="first_name"
				class="form-control"
				v-model="firstName"
			/>
			<div 
				v-if="inputWarning && !validFirstName"
				class="action-required-container"
			>
				<span class="action-required-notice">
					<i class="fa fa-exclamation-circle"></i>
					<span>Please enter your first name</span>
				</span>
			</div>
		</div>
		<div class="form-group" style="flex: 1; padding-left: 10px;"> 
			<label>Last Name</label><br />
			<input 
				type="text"
				name="last_name"
				class="form-control"
				v-model="lastName"
			/>
			<div 
				v-if="inputWarning && !validLastName"
				class="action-required-container"
			>
				<span class="action-required-notice">
					<i class="fa fa-exclamation-circle"></i>
					<span>Please enter your last name</span>
				</span>
			</div>
		</div>
	</div>
	<div
		class="form-group"
		v-bind:class="{'action-required': inputWarning && !validEmail}"
		style="display: flex;"
	>
		<div class="form-group" style="flex: 1; padding-right: 10px;">
			<label>Email</label><br />
			<input 
				type="text"
				name="email"
				class="form-control"
				v-model="email"
			/>
			<div 
				v-if="inputWarning && !validEmail"
				class="action-required-container"
			>
				<span class="action-required-notice">
					<i class="fa fa-exclamation-circle"></i>
					<span>Please enter your email address</span>
				</span>
			</div>
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

	<div 
		class="form-group"
		v-bind:class="{'action-required': inputWarning && !validTokenName}"
	>
		<label>Name of Token You Want to Distribute</label><br />
		<input 
			type="text"
			name="token_name"
			class="form-control"
			v-model="tokenName"
		/>
		<div 
			v-if="inputWarning && !validTokenName"
			class="action-required-container"
		>
			<span class="action-required-notice">
				<i class="fa fa-exclamation-circle"></i>
				<span>Please provide the name of the token you want to distribute</span>
			</span>
		</div>
	</div>

	<div 
		class="form-group"
		v-bind:class="{'action-required': inputWarning && !validTokenDescription}"
	>
		<label>A description of the token you want to distribute</label><br />
		<textarea 
			type="text"
			name="token_description"
			class="form-control"
			v-model="tokenDescription"
		/>
		<div 
			v-if="inputWarning && !validTokenDescription"
			class="action-required-container"
		>
			<span class="action-required-notice">
				<i class="fa fa-exclamation-circle"></i>
				<span>Please add a description of the token you want to distribute</span>
			</span>
		</div>
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
			name="token_exchanges_listed"
			v-model="tokenExchangesListed"
			v-show="false"
		/>
	</div>

	<div>
		<button 
			type="submit" 
			class="btn btn-lg btn-success button wide"
			v-bind:class="{'disabled': !formIsValid}"
		>
			<span>Complete My Account</span>
			<i class="fa fa-arrow-right"></i>
		</button>
	</div>
	<input type="hidden" name="_token" value="{{ csrf_token() }}" />
</form>