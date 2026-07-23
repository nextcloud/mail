<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="recipient-info">
		<!-- For a single recipient -->
		<div v-if="recipients && recipients.length === 1" class="recipient-info__single">
			<div class="recipient-info__avatar">
				<Avatar
					:display-name="recipients[0].label"
					:email="recipients[0].email"
					:size="55"
					:disable-tooltip="true"
					:disable-menu="true"
					:avatar="getAvatarForRecipient(recipients[0])" />
			</div>
			<div class="recipient-info__contact">
				<span class="recipient-info__contact-name">{{ recipients[0].label }}</span>
				<span v-if="recipients[0].email !== recipients[0].label" class="recipient-info__contact-email">
					{{ recipients[0].email }}
				</span>
			</div>
			<div class="recipient-info__details">
				<DisplayContactDetails :email="recipients[0].email" />
			</div>
		</div>

		<!-- For multiple recipients -->
		<div v-else-if="recipients && recipients.length > 1" class="recipient-info__multiple">
			<div v-for="(recipient, index) in recipients" :key="recipient.email" class="recipient-info__item">
				<div class="recipient-info__header">
					<div class="recipient-info__avatar recipient-info__avatar--small">
						<Avatar
							:display-name="recipient.label"
							:email="recipient.email"
							:size="36"
							:disable-tooltip="true"
							:disable-menu="true"
							:avatar="getAvatarForRecipient(recipient)" />
					</div>
					<div class="recipient-info__name">
						<strong>{{ recipient.label }}</strong>
						<span v-if="recipient.email !== recipient.label" class="recipient-info__contact-email">
							{{ recipient.email }}
						</span>
					</div>
					<button class="recipient-info__expand-toggle" @click="toggleExpand(index)">
						<IconArrowUp v-if="isExpanded(index)" :size="20" />
						<IconArrowDown v-else :size="20" />
					</button>
				</div>
				<div v-if="expandedRecipients[index]" class="recipient-info__details">
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
	width: 100%;

	&__single {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		align-items: flex-start;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__avatar {
		display: flex;
		justify-content: center;
		flex-shrink: 0;
	}

	&__contact {
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		text-align: start;
		gap: var(--default-grid-baseline);
		flex: 1;
		min-width: 0;
		overflow: hidden;
	}

	&__contact-name {
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		width: 100%;
		color: var(--color-text-maxcontrast);
	}

	&__contact-email {
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		width: 100%;
		color: var(--color-text-maxcontrast);
	}

	&__details {
		flex: 0 0 100%;
		width: 100%;
		overflow: hidden;
	}

	&__multiple {
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__item {
		border-bottom: 1px solid var(--color-border);
		padding-bottom: calc(var(--default-grid-baseline) * 2);

		&:last-child {
			border-bottom: none;
		}
	}

	&__header {
		display: flex;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__avatar--small {
		flex-shrink: 0;
	}

	&__name {
		flex: 1;
		overflow: hidden;

		strong {
			display: block;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}

	&__expand-toggle {
		flex-shrink: 0;
		background: none;
		border: none;
		cursor: pointer;
		padding: var(--default-grid-baseline);
		color: var(--color-main-text);
		display: flex;
		align-items: center;
		border-radius: var(--border-radius);

		&:hover {
			background-color: var(--color-background-hover);
		}
	}
}
</style>
