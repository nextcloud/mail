<template>
	<div id="content" class="app-mail">
		<div id="app-navigation" class="icon-loading">
			<div id="mail-new-message-fixed"
				 class="app-navigation-new">
			</div>
			<ul id="usergrouplist"></ul>
			<div id="app-settings">
				<div id="app-settings-header">
					<button class="settings-button"
							data-apps-slide-toggle="#app-settings-content"><?php p($l->
						t('Settings'));?>
					</button>
				</div>
				<div id="app-settings-content"></div>
			</div>
		</div>
		<div id="app-content">
			<div id="app-content-wrapper"></div>
		</div>
	</div>
</template>

<script>
	export default {}
</script>

<style>

</style>
