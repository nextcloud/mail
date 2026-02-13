<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="attachment-tag" :class="{ 'attachment-tag--remaing': remaining > 0 }" @click="$emit('open')">
		<img v-if="remaining === 0" class="attachment-tag__icon" :src="mimeUrl">
		<p class="attachment-tag__filename">
			{{ remaining > 0 ? `+${remaining}` : fileName }}
		</p>
	</div>
</template>

<script>

export default {
	name: 'AttachmentTag',
	props: {
		fileName: {
			type: String,
			default: '',
		},

		mimeType: {
			type: String,
			default: '',
		},

		remaining: {
			type: Number,
			default: 0,
		},

		mimeUrl: {
			type: String,
			default: '',
		},
	},
}
</script>

<style lang="scss" scoped>
.attachment-tag {
    height: 24px;
    border: 1px solid var(--color-border-dark);
    border-radius: var(--border-radius-element);
	gap: 4px;
	display: flex;
	cursor: unset;
	align-items: center;
    max-width: 140px;
	min-width: 60px;
	margin-inline-end: 4px;
	padding: 0 6px;

	&__filename{
		white-space: nowrap;
		text-overflow: ellipsis;
		overflow: hidden;
	}

	&__icon{
		width: 16px;
	}
}

.attachment-tag--remaing{
	min-width: unset;
}
</style>
