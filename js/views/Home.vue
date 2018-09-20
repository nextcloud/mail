<template>
	<div id="content" class="mail">
		<app-navigation :menu="menu">
			<AppSettingsMenu slot="settings-content"/>
		</app-navigation>
		<FolderContent>
			<Message v-if="messages.length > 0" />
		</FolderContent>
	</div>
</template>

<script>
	import AppNavigation from "../components/core/appNavigation";
	import AppSettingsMenu from "../components/AppSettingsMenu";
	import FolderContent from "../components/FolderContent";
	import Message from "../components/Message";

	import SidebarItems from "../mixins/SidebarItems";

	export default {
		name: 'home',
		extends: SidebarItems,
		components: {
			AppNavigation,
			AppSettingsMenu,
			FolderContent,
			Message,
		},
		computed: {
			menu () {
				return this.buildMenu(this.$store.state.accounts);
			},
			messages () {
				return this.$store.getters.currentFolder.messages
			}
		}
	};
</script>
