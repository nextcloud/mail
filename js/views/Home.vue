<template>
	<div id="content" class="mail">
		<Loading v-if="loading" :hint="t('mail', 'Loading your accounts')"/>
		<template v-else>
			<app-navigation :menu="menu">
				<AppSettingsMenu slot="settings-content"/>
			</app-navigation>
			<FolderContent />
		</template>
	</div>
</template>

<script>
	import AppNavigation from "../components/core/appNavigation";
	import AppSettingsMenu from "../components/AppSettingsMenu";
	import FolderContent from "../components/FolderContent";
	import Loading from "../components/Loading";

	import SidebarItems from "../mixins/SidebarItems";

	export default {
		name: 'home',
		extends: SidebarItems,
		components: {
			Loading,
			AppNavigation,
			AppSettingsMenu,
			FolderContent,
		},
		data () {
			return {
				loading: true
			}
		},
		computed: {
			menu () {
				return this.buildMenu(this.$store.state.accounts);
			}
		},
		created () {
			this.$store.dispatch('fetchAccounts').then(() => {
				this.loading = false;
			});
		}
	};
</script>
