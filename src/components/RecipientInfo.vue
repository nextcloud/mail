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
					<Avatar
						:display-name="recipients[0].label"
						:email="recipients[0].email"
						:size="55"
						:disable-tooltip="true"
						:disable-menu="true"
						:avatar="getAvatarForRecipient(recipients[0])" />
				</div>
				<div class="recipient-info__contact">
					<strong class="recipient-info__contact-name">{{ recipients[0].label }}</strong>
					<span v-if="recipients[0].email !== recipients[0].label" class="recipient-info__contact-email">
						{{ recipients[0].email }}
					</span>
				</div>
			</div>
			<div class="recipient-info__details">
				<DisplayContactDetails :email="recipients[0].email" />
			</div>
		</div>

		<!-- For multiple recipients -->
		<div v-else-if="recipients && recipients.length > 1" class="recipient-info__multiple">
			<div v-for="(recipient, index) in recipients" :key="recipient.email" class="recipient-info__item">
				<NcButton
					class="recipient-info__expand-toggle"
					variant="tertiary-no-background"
					:aria-label="isExpanded(index) ? t('mail', 'Collapse') : t('mail', 'Expand')"
					@click="toggleExpand(index)">
					<template #icon>
						<IconArrowUp v-if="isExpanded(index)" :size="20" />
						<IconArrowDown v-else :size="20" />
					</template>
				</NcButton>
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
					<div class="recipient-info__contact">
						<strong class="recipient-info__contact-name">{{ recipient.label }}</strong>
						<span v-if="recipient.email !== recipient.label" class="recipient-info__contact-email">
							{{ recipient.email }}
						</span>
					</div>
				</div>
				<div
					class="recipient-info__details recipient-info__details--multiple"
					:class="{ 'recipient-info__details--expanded': isExpanded(index) }">
					<DisplayContactDetails :email="recipient.email" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
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
		NcButton,
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
			handler(newRecipients) {
				const next = newRecipients.length
				const current = this.expandedRecipients.length
				if (next > current) {
					for (let i = current; i < next; i++) {
						this.$set(this.expandedRecipients, i, false)
					}
				} else if (next < current) {
					this.expandedRecipients = this.expandedRecipients.slice(0, next)
				}
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
		flex-direction: column;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__avatar {
		display: flex;
		flex-shrink: 0;
	}

	&__avatar--small {
		flex-shrink: 0;
	}

	&__header {
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__contact {
		display: flex;
		flex-direction: column;
		gap: var(--default-grid-baseline);
		min-width: 0;
		overflow: hidden;
	}

	&__contact-name {
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__contact-email {
		display: block;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		color: var(--color-text-maxcontrast);
	}

	&__details {
		width: 100%;
		overflow: hidden;

		:deep(.contact-title) {
			display: none;
		}

		&--multiple {
			:deep(.contact-details-wrapper) {
				display: none;
			}

			&.recipient-info__details--expanded {
				:deep(.contact-details-wrapper) {
					display: block;
				}
			}
		}
	}

	&__multiple {
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 2);
	}

	&__item {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		gap: var(--default-grid-baseline);
		border-bottom: 1px solid var(--color-border);
		padding-bottom: calc(var(--default-grid-baseline) * 2);
		padding-inline-end: calc(var(--default-clickable-area) + var(--default-grid-baseline));

		&:last-child {
			border-bottom: none;
		}
	}

	&__expand-toggle {
		position: absolute;
		top: 0;
		inset-inline-end: 0;
	}
}
</style>
