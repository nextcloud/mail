<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="recipient-info">
		<!-- For a single recipient -->
		<div v-if="recipients && recipients.length === 1" class="recipient-info__single">
			<div class="recipient-info__header">
				<div class="recipient-info__avatar">
					<Avatar :display-name="recipients[0].label"
						:email="recipients[0].email"
						:size="55"
						:disable-tooltip="true"
						:disable-menu="true"
						:avatar="getAvatarForRecipient(recipients[0])" />
				</div>
				<div class="recipient-info__details">
					<DisplayContactDetails :email="recipients[0].email" />
				</div>
			</div>
		</div>

		<!-- For multiple recipients -->
		<div v-else-if="recipients && recipients.length > 1" class="recipient-info__multiple">
			<div v-for="(recipient, index) in recipients" :key="recipient.email" class="recipient-info__item">
				<div class="recipient-info__header">
					<div class="recipient-info__avatar">
						<Avatar :display-name="recipient.label"
							:email="recipient.email"
							:size="55"
							:disable-tooltip="true"
							:disable-menu="true"
							:avatar="getAvatarForRecipient(recipient)" />
					</div>
					<div v-if="!expandedRecipients[index]" class="recipient-info__name">
						<h6>{{ recipient.email }}</h6>
					</div>
					<div class="recipient-info__expand-toggle" @click="toggleExpand(index)">
						<template v-if="isExpanded(index)">
							<div class="recipient-info__show-less">
								<IconArrowUp :size="20" />
								<span>{{ t('mail', 'Show less') }}</span>
							</div>
						</template>
						<template v-else>
							<IconArrowDown :size="20" />
							<span>{{ t('mail', 'Show more') }}</span>
						</template>
					</div>
				</div>
				<div v-show="expandedRecipients[index]" class="recipient-info__details">
					<DisplayContactDetails :email="recipient.email" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { mapGetters } from 'pinia'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import Avatar from './Avatar.vue'
import DisplayContactDetails from './DisplayContactDetails.vue'
import useMainStore from '../store/mainStore.js'

export default {
	components: {
		Avatar,
		IconArrowDown,
		IconArrowUp,
		DisplayContactDetails,
	},
	data() {
		return {
			expandedRecipients: [],
		}
	},
	computed: {
		...mapGetters(useMainStore, ['composerMessage']),
		recipients() {
			return Array.isArray(this.composerMessage.data.to) ? this.composerMessage.data.to : []
		},
	},
	watch: {
		recipients: {
			immediate: true,
			handler() {
				this.expandedRecipients = this.recipients.map(() => false)
			},
		},
	},
	methods: {
		toggleExpand(index) {
			this.$set(this.expandedRecipients, index, !this.expandedRecipients[index])
		},
		isExpanded(index) {
			return this.expandedRecipients[index]
		},
		getAvatarForRecipient(recipient) {
			if ((recipient.source && recipient.source === 'contacts') && recipient.photo) {
				return {
					isExternal: false,
					url: recipient.photo,
				}
			}
			return null
		},
	},
}
</script>
<style scoped lang="scss">
.recipient-info {
	display: inline;
	width: 100%;

	&__single {
		width: 370px;
		display: inline-block;
	}

	&__avatar {
		margin-top: 20px;
		display: inline;
		float: inline-start;
		padding: 20px;
	}

	&__details {
		max-width: 100%;
	}

	&__multiple {
		margin-top: 10px;
		display: flex;
		flex-direction: column;
	}

	&__item {
		margin-bottom: 10px;
	}

	&__expand-toggle {
		cursor: pointer;
		display: flex;
		gap: 5px;
	}

	&__header {
		display: contents;
	}

	&__name {
		margin-top: 50px;
	}

	&__show-less {
		margin-top: 40px;
	}
}
</style>
