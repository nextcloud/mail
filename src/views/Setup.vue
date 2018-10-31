<template>
	<div id="content" class="mail">
		<div id="app-content">
			<div id="emptycontent">
				<div class="icon-mail"></div>
				<h2>{{ t('mail', 'Connect your mail account') }}</h2>
				<AccountForm v-bind:settings-page="false"/>
			</div>
		</div>
	</div>
</template>

<script>
	import AccountForm from '../components/AccountForm'

	export default {
		name: 'Setup',
		components: {
			AccountForm,
		}
	}
</script>

<style>
	#emptycontent {
		margin-top: 10vh;
	}
</style>