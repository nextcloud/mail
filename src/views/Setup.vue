<template>
	<div id="content" class="mail">
		<div id="app-content">
			<div id="emptycontent">
				<div class="icon-mail"></div>
				<h2>{{ t('mail', 'Connect your mail account') }}</h2>
				<AccountForm :displayName="displayName"
							 :email="email"
							 :save="onSave"/>
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
		},
		computed: {
			displayName () {
				return $('#user-displayname').text() || ''
			},
			email () {
				return $('#user-email').text() || ''
			}
		},
		methods: {
			onSave (data) {
				return this.$store.dispatch('createAccount', data)
					.then(account => {
						this.$router.push({
							name: 'home',
						})

						return account
					})
			}
		}
	}
</script>

<style>
	#emptycontent {
		margin-top: 10vh;
	}
</style>