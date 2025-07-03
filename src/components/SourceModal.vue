<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal size="full" @close="onClose">
		<div class="message-source">
			<h2>{{ t('mail', 'Message source') }}</h2>
			<pre>{{ rawMessage }}</pre>
		</div>
	</NcModal>
</template>

<script>
import { NcModal } from '@nextcloud/vue'

export default {
	name: 'SourceModal',
	components: {
		NcModal,
	},
	props: {
		rawMessage: {
			type: String,
			required: true,
		},
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
.message-source {
	padding: 0 calc(var(--default-grid-baseline) * 4);

	h2 {
		margin-top: 0;
		margin-bottom: 0;
	}

	pre {
		font-family: monospace;
		white-space: pre-wrap;
		user-select: text;
	}

}
</style>
