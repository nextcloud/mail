<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<p class="help-text">
		{{ t('mail', 'Stop ends all processing') }}
	</p>
</template>
<script>

export default {
	name: 'ActionStop',
	props: {
		action: {
			type: Object,
			required: true,
		},
		account: {
			type: Object,
			required: true,
		},
	},
}
</script>
<style lang="scss" scoped>
.help-text {
	align-content: center;
}
</style>
