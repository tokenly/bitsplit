<form>
	<div v-for="(field, key) in fields" v-if="checkCondition(field)" class="form-group" :class="{ 'text-danger': errors[field.name] }">
		<label>
			<span>@{{ field.name }}</span>
			<small v-if="!field.required" class="optional">(Optional)</small>
		</label><br />

		<!-- Text -->
		<input v-if="field.type === 'text'" :type="field.type" v-model="field.value" class="form-control"/>
		<!-- Textarea -->
		<textarea v-else-if="field.type === 'textarea'" v-model="field.value" class="form-control"></textarea>
		<!-- Toggle -->
		<ul v-else-if="field.type === 'toggle'" class="yes-no-toggle">
			<li @click="toggle(field, true)"><a><span class="yes" v-bind:class="{active: field.value}">Yes</span></a></li><li class="no" @click="toggle(field, false)"><a><span class="no" v-bind:class="{active: !field.value}">No</span></a></li>
		</ul>
		<!-- Checkbox -->
		<div v-else-if="field.type.toLowerCase() === 'checkbox'">
			<span v-for="option in field.options" class="select-button" v-bind:class="{'active': field.value && field.value.indexOf(option.value) > -1}" @click="toggleListing(field, option.value)">
				<i class="fa fa-check"></i>
				<span>@{{ option.value }}</span>
			</span>
			<input type="text" name="token_exchanges_listed" v-model="field.value" v-show="false"/>
		</div>
		<span v-if="errors[field.name]" class="text-danger">@{{ errors[field.name] }}</span>
	</div>
	<hr>

	<div>
		<button 
			type="button"
			class="btn btn-lg btn-success button wide"
			v-bind:class="{'disabled': !formIsValid}"
			v-on:click.prevent="submit"
		>
			<span>Complete My Account</span>
			<i class="fa fa-arrow-right"></i>
		</button>
	</div>
	<input type="hidden" name="_token" value="{{ csrf_token() }}" />
</form>