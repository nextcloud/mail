<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAssistantContent class="wrapper">
		<div class="summary">
			<div class="summary__header">
				<div class="summary__header__actions">
					<div class="summary__header__info">
						<NcAssistantIcon class="summary__header__icon" />
						<div class="summary__header__text">
							<div class="summary__header__title">
								{{ t('mail', 'Thread summary') }}
							</div>
							<div class="summary__header__brand">
								{{ brand }}
							</div>
						</div>
					</div>

					<NcButton :aria-label=" t('mail', 'Go to latest message')"
						type="tertiary-no-background"
						@click="onScroll">
						{{ t('mail', 'Newest message') }}
						<template #icon>
							<ArrowDownIcon :size="20" />
						</template>
					</NcButton>
				</div>
			</div>
			<div class="summary__body">
				<LoadingSkeleton v-if="loading" :number-of-lines="1" :with-avatar="false" />
				<p v-else>
					{{ summary }}
				</p>
			</div>
			<div class="summary__notice">
				{{ t('mail', 'This summary is AI generated and may contain mistakes.') }}
			</div>
		</div>
	</NcAssistantContent>
</template>
<script>
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcAssistantContent from '@nextcloud/vue/components/NcAssistantContent'
import NcAssistantIcon from '@nextcloud/vue/components/NcAssistantIcon'
import LoadingSkeleton from './LoadingSkeleton.vue'

export default {
	name: 'ThreadSummary',
	components: {
		LoadingSkeleton,
		NcButton,
		ArrowDownIcon,
		NcAssistantContent,
		NcAssistantIcon,
	},
	props: {
		summary: {
			type: String,
			required: true,
		},
		loading: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		brand() {
			if (OCA.Theming) {
				return t('mail', '{name} Assistant', { name: OCA.Theming.name })
			}
			return t('mail', '{name} Assistant', { name: 'Nextcloud' })
		},
	},
	methods: {
		onScroll() {
			let container = document.querySelector('.splitpanes__pane-details')
			if (!container) {
				container = document.querySelector('.app-content-wrapper--mobile')
			}
			container.scrollTo({ top: container.scrollHeight, left: 0, behavior: 'smooth' })
		},
	},

}
</script>
<style lang="scss" scoped>
.wrapper {
	max-width: calc(100% - 20px);
	margin: 0 auto;
	width: 100%;
}

.summary {
	position: relative;
	border-radius: var(--border-radius-large);
	padding: 10px;
	display: flex;
	flex-direction: column;
	&__header {
		display: flex;
		flex-direction: column;

		&__actions {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding-bottom: 10px;
		}

		&__info {
			display: flex;
			align-items: center;
			gap: var(--default-grid-baseline);
		}

		&__text {
			display: flex;
			flex-direction: column;
			line-height: 1.2;
		}

		&__title {
			font-weight: bold;
		}

		&__brand {
			color: var(--color-text-maxcontrast);
		}
		&__icon {
			padding-inline-end: 14px;
		}
	}
	&__body {
		margin-inline-start: 35px;
	}
	@media only screen and (max-width: 600px) {
		.summary {
			&__header {
				flex-direction: column;
				&__actions {
					flex-direction: column;
				}
			}
		}
	}
	.summary__notice {
		margin-top: 0.5rem;
		margin-inline-start: 35px;
		color: var(--color-text-maxcontrast);
	}
}
</style>
