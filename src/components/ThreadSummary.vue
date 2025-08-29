<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="summary">
		<div class="summary__header">
			<div class="summary__header__actions">
				<div class="summary__header__info">
					<NcIconSvgWrapper :size="20" :svg="creation" name="creation" />
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
					type="secondary"
					@click="onScroll">
					{{ t('mail', 'Go to newest message') }}
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
</template>
<script>
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import creation from '../../img/creation-gradient.svg?raw'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import LoadingSkeleton from './LoadingSkeleton.vue'

export default {
	name: 'ThreadSummary',
	components: {
		LoadingSkeleton,
		NcButton,
		NcIconSvgWrapper,
		ArrowDownIcon,
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
	data() {
		return {
			creation,
		}
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
.summary {
	position: relative;
	border-radius: var(--border-radius-large);
	margin: 0 10px 20px 10px;
	padding: 10px;
	display: flex;
	flex-direction: column;
	background: #f6f5ff;

	&::before {
		content: "";
		position: absolute;
		inset: 0;
		padding: 2px;
		border-radius: inherit;
		background: linear-gradient(120deg, #7398FE 50%, #6104A4 125%);

		-webkit-mask:
			linear-gradient(#fff 0 0) content-box,
			linear-gradient(#fff 0 0);
		-webkit-mask-composite: xor;
		mask-composite: exclude;
		pointer-events: none;
	}
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
			gap: 8px;
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
			color:var(--color-text-maxcontrast)
		}
	}
	&__body {
		margin-left: 40px;
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
		margin-left: 40px;
		color: var(--color-text-maxcontrast);
	}
}
@media (prefers-color-scheme: dark) {
	body[data-theme-default] .summary {
		background: linear-gradient(#221D2B) padding-box,
		linear-gradient(125deg, #0C3A65 50%, #6204A5 125%) border-box;
	}
}
body[data-theme-dark] .summary {
	background: linear-gradient(#221D2B) padding-box,
	linear-gradient(125deg, #0C3A65 50%, #6204A5 125%) border-box;
}

</style>
