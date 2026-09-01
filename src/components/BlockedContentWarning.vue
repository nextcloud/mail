<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="blocked-content-warning">
		<div class="blocked-content-warning__text">
			<IconImageOff :size="20" />
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
		</div>
		<NcActions variant="secondary" :menu-name="t('mail', 'Show images')">
			<NcActionButton @click="$emit('show')">
				<template #icon>
					<IconImage :size="20" />
				</template>
				{{ t('mail', 'Show images temporarily') }}
			</NcActionButton>
			<NcActionButton
				v-if="sender"
				@click="$emit('trust-sender')">
				<template #icon>
					<IconMail :size="20" />
				</template>
				{{ t('mail', 'Always show images from {sender}', { sender }) }}
			</NcActionButton>
			<NcActionButton
				v-if="domain"
				@click="$emit('trust-domain')">
				<template #icon>
					<IconDomain :size="20" />
				</template>
				{{ t('mail', 'Always show images from {domain}', { domain }) }}
			</NcActionButton>
		</NcActions>
	</div>
</template>

<script>
import { NcActionButton, NcActions } from '@nextcloud/vue'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import IconMail from 'vue-material-design-icons/EmailOutline.vue'
import IconImageOff from 'vue-material-design-icons/ImageOffOutline.vue'
import IconImage from 'vue-material-design-icons/ImageSizeSelectActual.vue'

export default {
	name: 'BlockedContentWarning',
	components: {
		IconDomain,
		IconImage,
		IconImageOff,
		IconMail,
		NcActionButton,
		NcActions,
	},

	props: {
		sender: {
			type: String,
			default: null,
		},

		domain: {
			type: String,
			default: null,
		},
	},
}
</script>

<style lang="scss" scoped>
.blocked-content-warning {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: var(--default-grid-baseline);
	padding: calc(var(--default-grid-baseline) * 2);
	border-radius: var(--border-radius-element);
	background-color: var(--color-background-dark);
	color: var(--color-main-text);
	text-align: start;

	&__text {
		display: flex;
		align-items: center;
		gap: var(--default-grid-baseline);
	}
}
</style>
