<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup>
import { defineProps } from 'vue'
import { translateTagDisplayName } from '../util/tag.js'

const props = defineProps({
	tag: {
		type: Object,
		required: true,
	},
})
</script>

<template>
	<div class="tag-group">
		<div
			class="tag-group__bg"
			:style="{ 'background-color': tag.color }" />
		<span
			class="tag-group__label"
			:style="{ color: tag.color }">
			{{ translateTagDisplayName(tag) }}
		</span>
	</div>
</template>

<style scoped lang="scss">
.tag-group {
	display: inline-block;
	border-radius: var(--border-radius-pill);
	position: relative;
	margin-inline-end: 1px;
	overflow: hidden;
	text-overflow: ellipsis;

	&__label {
		margin: 0 7px;
		z-index: 2;
		font-size: calc(var(--default-font-size) * 0.8);
		font-weight: bold;
		padding-inline: 2px;
		white-space: nowrap;
	}

	&__bg {
		position: absolute;
		width: 100%;
		height: 100%;
		top: 0;
		inset-inline-start: 0;
		opacity: 15%;
	}
}
</style>
