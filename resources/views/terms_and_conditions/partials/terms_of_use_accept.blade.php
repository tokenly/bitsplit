<form
	method="post"
	v-bind:action="actionPath"
	v-on:submit="checkAccept"
>
	<div class="tou-accept-container" id="tou-accept">
		<div class="tou-accept-container__checkbox">
			<span
				class="tou-accept-container__checkbox__input-padding"
				v-bind:class="{'warning': acceptPrompt && !userAccepted}">
				<input id="user_accepted_input" name="userAccepted" type="checkbox" v-model="userAccepted"/>
			</span>
			<label class="tou-accept-container__checkbox__label" for="user_accepted_input">
				<span>I have read and agree to the Terms and Conditions for Merged Folding</span>
			</label>
			<div
				v-if="acceptPrompt && !userAccepted"
				class="tou-accept-container__accept-prompt"
			>
				<span>Please indicate your acceptance of the Terms and Conditions by checking the box above.</span>
			</div>
		</div>
		<div class="tou-accept-container__button">
			<button class="tou-cta__button" v-bind:class="{'disabled': !userAccepted}">I Accept the Terms of Use</button>
		</div>
	</div>
	<input type="hidden" name="_token" value="{{ csrf_token() }}">
</form>
