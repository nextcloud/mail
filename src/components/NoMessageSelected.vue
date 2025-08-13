<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppContentDetails class="app-content no-message-selected"
		:style="{ 'backgroundImage': backgroundImgSrc }">
		<div class="no-message-selected__heading">
			{{ t('mail', 'Welcome to {productName} Mail', { productName }, null, {escape: false}) }}
		</div>
		<div class="no-message-selected__text">
			{{ t('mail', 'Start writing a message by clicking below or select an existing message to display its contents') }}
		</div>
		<div class="no-message-selected__action">
			<NewMessageButtonHeader :show-refresh="false" />
		</div>
	</AppContentDetails>
</template>

<script>
import { generateFilePath } from '@nextcloud/router'
import { isDarkTheme } from '@nextcloud/vue/functions/isDarkTheme'
import { NcAppContentDetails as AppContentDetails } from '@nextcloud/vue'

import NewMessageButtonHeader from './NewMessageButtonHeader.vue'

export default {
	name: 'NoMessageSelected',
	components: {
		NewMessageButtonHeader,
		AppContentDetails,
	},

	data() {
		return {
			backgroundImgSrc: isDarkTheme
				? 'url("' + generateFilePath('mail', 'img', 'welcome-connection-dark.png') + '")'
				: 'url("' + generateFilePath('mail', 'img', 'welcome-connection-light.png') + '")',
		}
	},

	computed: {
		productName() {
			return window?.OC?.theme?.name ?? 'Nextcloud'
		},
	},
}
</script>
<style lang="scss" scoped>
.no-message-selected {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	justify-content: center;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
	padding-inline-start: 50px;
	height: 100%;
	max-width: 100% !important; /* restricted otherwise by stronger selector */

	background-size: cover;
	background-repeat: no-repeat;
	background-position: right 100% bottom 40%;

	&__heading {
		font-weight: bold;
		font-size: 20px;
		line-height: 30px;
	}

	&__text {
		text-wrap-style: balance;
		max-width: 50%;
	}
}
</style>
