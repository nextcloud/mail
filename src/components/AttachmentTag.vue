<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="attachment-tag">
		<FileIcon :file-name="fileName" :mime-type="mimeType" />
		<p class="attachment-tag__filename">
			{{ fileName }}
		</p>
	</div>
</template>

<script>
import FileIcon from './icons/FileIcon.vue'
export default {
	name: 'AttachmentTag',
	components: { FileIcon },
	props: {
		fileName: {
			type: String,
			required: true,
		},
		mimeType: {
			type: String,
			required: true,
		},
	},
}
</script>
<style scoped>
.attachment-tag {
    height: 24px;
    border: 1px solid var(--color-border-dark);
    border-radius: var(--border-radius-element);
	gap: 4px;
	display: flex;
	align-items: center;
    width: max-content;
	margin-inline-end: 4px;
	padding: 0 4px;
}
</style>
