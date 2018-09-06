<template>
	<div id="content" class="mail">
		<app-navigation :menu="menu">
			<AppSettingsMenu slot="settings-content"/>
		</app-navigation>
		<FolderContent>
			<NewMessageDetail/>
		</FolderContent>
	</div>
</template>

<script>
	import AppNavigation from "../components/core/appNavigation";
	import AppSettingsMenu from "../components/AppSettingsMenu";
	import FolderContent from "../components/FolderContent";

	import SidebarItems from "../mixins/SidebarItems";
	import NewMessageDetail from "../components/NewMessageDetail";

	export default {
		name: 'home',
		extends: SidebarItems,
		components: {
			NewMessageDetail,
			AppNavigation,
			AppSettingsMenu,
			FolderContent,
		},
		computed: {
			menu () {
				return this.buildMenu(this.$store.state.accounts);
			}
		}
	};
</script>
