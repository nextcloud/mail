<template>
	<AppContent app-name="mail">
		<template slot="navigation">
			<AppNavigationNew :text="t('mail', 'New message')"
							  buttonId="mail_new_message"
							  buttonClass="icon-add"
							  @click="onNewMessage"/>
			<ul id="accounts-list">
				<AppNavigationItem v-for="item in menu"
								   :key="item.key"
								   :item="item"/>
			</ul>
			<AppNavigationSettings :title="t('mail', 'Settings')">
				<AppSettingsMenu />
			</AppNavigationSettings>
		</template>
		<template slot="content">
			TODO: implement account settings
		</template>
	</AppContent>
</template>

<script>
	import {
		AppContent,
		AppNavigationItem,
		AppNavigationNew,
		AppNavigationSettings
	} from 'nextcloud-vue'
	import AppSettingsMenu from '../components/AppSettingsMenu'

	import SidebarItems from '../mixins/SidebarItems'

	export default {
		name: 'AccountSettings',
		extends: SidebarItems,
		components: {
			AppContent,
			AppNavigationItem,
			AppNavigationNew,
			AppNavigationSettings,
			AppSettingsMenu,
		},
		computed: {
			menu () {
				return this.buildMenu()
			}
		}
	}
</script>
