<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="tag-group" :class="{ 'tag-group--dark': isDark, 'tag-group--bright': !isDark }">
		<div
			class="tag-group__bg"
			:style="{ 'background-color': tag.color }" />
		<span
			class="tag-group__label">
			{{ translateTagDisplayName(tag) }}
		</span>
	</div>
</template>

<script setup>
import { defineProps } from 'vue'
import { translateTagDisplayName } from '../util/tag.js'

const props = defineProps({
	tag: {
		type: Object,
		required: true,
	},
})

// Source - https://stackoverflow.com/a
// Posted by kirilloid, modified by community. See post 'Timeline' for change history
// Retrieved 2025-11-19, License - CC BY-SA 4.0

const RED = 0.2126;
const GREEN = 0.7152;
const BLUE = 0.0722;

const GAMMA = 2.4;

function luminance(r, g, b) {
	const a = [r, g, b].map(function (v) {
		v /= 255
		return v <= 0.03928
			? v / 12.92
			: Math.pow((v + 0.055) / 1.055, GAMMA)
	})
	return a[0] * RED + a[1] * GREEN + a[2] * BLUE
}

function contrast(rgb1, rgb2) {
	const lum1 = luminance(...rgb1)
	const lum2 = luminance(...rgb2)
	const brightest = Math.max(lum1, lum2)
	const darkest = Math.min(lum1, lum2)
	return (brightest + 0.05) / (darkest + 0.05)
}

const match = /^\#([\da-fA-F]{1,2})([\da-fA-F]{1,2})([\da-fA-F]{1,2})$/.exec(props.tag.color);
if (!match) {
	throw new Error('Could not parse tag colour: ' + props.tag.color)
}
const rgbColor = {
	r: Number(match[1]),
	g: Number(match[2]),
	b: Number(match[3]),
}

const contrastWithWhite = contrast([255, 255, 255], [rgbColor.r, rgbColor.g, rgbColor.b]);
const contrastWithBlack = contrast([0, 0, 0], [rgbColor.r, rgbColor.g, rgbColor.b]);

const isDark = contrastWithBlack < contrastWithWhite
</script>

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
		filter: brightness(90%);
	}
	&--dark &__label {
		color: white;
	}
	&--bright &__label {
		color: black;
	}

	&__bg {
		position: absolute;
		width: 100%;
		height: 100%;
		top: 0;
		inset-inline-start: 0;
	}
}
</style>
