<template>
	<div id="content" class="mail">
		<app-navigation :menu="menu">
			<AppSettingsMenu slot="settings-content"/>
		</app-navigation>
		<div id="app-content">
			TODO: implement account settings
		</div>
	</div>
</template>

<script>
	import {AppNavigation} from 'nextcloud-vue'
	import AppSettingsMenu from '../components/AppSettingsMenu'

	import SidebarItems from '../mixins/SidebarItems'

	export default {
		name: 'AccountSettings',
		extends: SidebarItems,
		components: {
			AppNavigation,
			AppSettingsMenu,
		},
		computed: {
			menu () {
				return this.buildMenu()
			}
		}
	}
</script>
