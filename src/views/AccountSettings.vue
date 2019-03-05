<template>
	<AppContent app-name="mail">
		<Navigation slot="navigation" />
		<template slot="content">
			TODO: implement account settings
		</template>
	</AppContent>
</template>

<script>
	import {AppContent} from 'nextcloud-vue'

	import AppSettingsMenu from '../components/AppSettingsMenu'
	import Navigation from '../components/Navigation'

	export default {
		name: 'AccountSettings',
		components: {
			AppContent,
			Navigation,
			AppSettingsMenu,
		},
		computed: {
			menu () {
				return this.buildMenu()
			}
		}
	}
</script>
