<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span
		:aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon junk-icon"
		role="img">
		<svg
			:fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 16 16">
			<path d="M0 0h24v24H0V0z" fill="none" />
			<path d="m5.5156 2l-3.5156 3.5156v4.9684l3.5156 3.516h4.9684l3.516-3.516v-4.9684l-3.516-3.5156h-4.9684zm1.4844 2h2v5h-2v-5zm0 6h2v2h-2v-2z" />
		</svg>
	</span>
</template>

<script>
export default {
	name: 'JunkIcon',
	props: {
		title: {
			type: String,
			default: '',
		},

		size: {
			type: Number,
			default: 20,
		},

		fillColor: {
			type: String,
			default: 'currentColor',
		},
	},
}
</script>
