<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="warning">
		<div class="warning__title">
			<IconAlertOutline :size="16" :title="t('mail', 'Phishing email')" />
			This email might be a phishing attempt
		</div>
		<ul v-for="(warning,index) in warnings" :key="index" class="warning__list">
			<li>{{ warning.message }}</li>
		</ul>
		<div v-if="linkWarning !== undefined" class="warning__links">
			<NcButton class="warning__links__button" type="Tertiary" @click="showMore = !showMore">
				{{ showMore? t('mail','Hide suspicious links') :t('mail','Show suspicious links') }}
			</NcButton>
			<div v-if="showMore">
				<ul v-for="(link,index) in linkWarning.additionalData" :key="index" class="warning__list">
					<li><b>href: </b>{{ link.href }} : <b>{{ t('mail','link text') }}</b> {{ link.linkText }} </li>
				</ul>
			</div>
		</div>
	</div>
</template>
<script>
import IconAlertOutline from 'vue-material-design-icons/AlertOutline.vue'
import { NcButton } from '@nextcloud/vue'

export default {

	name: 'PhishingWarning',
	components: {
		IconAlertOutline,
		NcButton,
	},
	props: {
		phishingData: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			showMore: false,
		}
	},
	computed: {
		warnings() {
			return this.phishingData.filter(check => check.isPhishing)
		},
		linkWarning() {
			return this.phishingData.find(check => check.type === 'Link')
		},
	},

}

</script>
<style lang="scss" scoped>
.warning {
	background-color:var(--ck-color-base-error);
    border-radius: var(--border-radius-rounded);
    width: 100%;
    text-align: left;
    padding: 15px;
    margin-bottom: 10px;
	&__title {
		display: flex;
	}
	&__list {
		list-style-position: inside;
		list-style-type: disc;
	}
    &__links {
      margin-top: 10px;
		&__button{
			margin-bottom: 10px;
		}
    }
}
</style>
