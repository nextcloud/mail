<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AppContentDetails class="app-content no-message-selected"
		:class="{ 'no-message-selected--themed': isThemed, }"
		:style="{ 'backgroundImage': isThemed ? undefined: backgroundImgSrc, }">
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
import { useIsDarkTheme } from '@nextcloud/vue/composables/useIsDarkTheme'
import { NcAppContentDetails as AppContentDetails } from '@nextcloud/vue'

import NewMessageButtonHeader from './NewMessageButtonHeader.vue'

export default {
	name: 'NoMessageSelected',
	components: {
		NewMessageButtonHeader,
		AppContentDetails,
	},

	setup() {
		return {
			isDarkTheme: useIsDarkTheme(),
		}
	},

	data() {
		return {
			backgroundImgSrc: this.isDarkTheme
				? 'url("' + generateFilePath('mail', 'img', 'welcome-connection-dark.png') + '")'
				: 'url("' + generateFilePath('mail', 'img', 'welcome-connection-light.png') + '")',
			isThemed: this.isDarkTheme
				? window.getComputedStyle(document.body).getPropertyValue('--color-primary-element') !== '#0091f2'
				: window.getComputedStyle(document.body).getPropertyValue('--color-primary-element') !== '#00679e',
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
@use '../../css/fluid';

.no-message-selected {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	justify-content: center;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
	padding-inline-start: 50px;
	height: 100%;
	max-width: 100% !important; /* restricted otherwise by stronger selector */

	@include fluid.background;

	/** fallback gradient when the theme color isn't standard blue */
	&--themed {
		@include fluid.gradient-background;
	}

	&__heading {
		font-weight: bold;
		font-size: 20px;
		line-height: 30px;
	}

	&__text {
		text-wrap-style: balance;
		max-width: 50%;
	}

	&__action {
		:deep(button) {
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2)
		}
	}
}
</style>
