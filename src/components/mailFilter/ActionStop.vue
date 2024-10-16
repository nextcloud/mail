<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<p>{{ t('mail', 'The stop action ends all processing') }}</p>
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
