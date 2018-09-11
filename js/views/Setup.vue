<template>
	<div id="content" class="mail">
		<div id="app-content">
			<div class="container">
				<div id="setup-content container">
					<account-form v-bind:settings-page="false" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import AccountForm from '../components/AccountForm';
export default {
  name: 'Setup',
  components: {
    AccountForm
  }
};
</script>
