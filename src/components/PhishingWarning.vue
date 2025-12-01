<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="phishing-warning">
		<div class="phishing-warning__title">
			<IconAlertOutline :size="20" :title="t('mail', 'Phishing email')" />
			{{ t('mail', 'This email might be a phishing attempt') }}
		</div>
		<ul v-for="(warning, index) in warnings" :key="index" class="phishing-warning__list">
			<li class="phishing-warning__list__item">
				{{ warning.message }}
			</li>
		</ul>
		<div v-if="linkWarning !== undefined" class="phishing-warning__links">
			<NcButton class="warning__links__button" variant="tertiary" @click="showMore = !showMore">
				{{ showMore ? t('mail', 'Hide suspicious links') : t('mail', 'Show suspicious links') }}
			</NcButton>
			<div v-if="showMore">
				<ul v-for="(link, index) in linkWarning.additionalData" :key="index" class="phishing-warning__list">
					<li class="phishing-warning__list__item" dir="auto">
						<b>href: </b>{{ link.href }} <b>{{ t('mail', 'link text') }}:</b> {{ link.linkText }}
					</li>
				</ul>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import IconAlertOutline from 'vue-material-design-icons/AlertOutline.vue'

export default {

	name: 'PhishingWarning',
	components: {
		IconAlertOutline,
		NcButton,
	},

	props: {
		phishingData: {
			required: true,
			type: Array,
		},
	},

	data() {
		return {
			showMore: false,
		}
	},

	computed: {
		warnings() {
			return this.phishingData.filter((check) => check.isPhishing)
		},

		linkWarning() {
			return this.phishingData.find((check) => check.type === 'Link' && check.isPhishing)
		},
	},

}

</script>

<style lang="scss" scoped>
@use '../../css/variables';

.phishing-warning {
	background-color: rgba(var(--color-warning-rgb), 0.2);
	border-radius: var(--border-radius-element);
	text-align: start;
	padding: 8px;
	margin: calc(var(--default-grid-baseline) * 2);
	// To match the html message margin
	margin-inline-start: calc(var(--default-grid-baseline) * 14);
	&__title {
		display: flex;
		gap: 2px;
	}
	&__list {
		list-style-position: inside;
		list-style-type: disc;
		&__item {
			word-wrap: break-word;
		}
	}
	&__links {
		margin-top: 10px;
		&__button{
			margin-bottom: 10px;
		}
    }

	@media (max-width: #{variables.$breakpoint-mobile}) {
		margin-inline-start: calc(var(--default-grid-baseline) * 3);
	}
}
</style>
