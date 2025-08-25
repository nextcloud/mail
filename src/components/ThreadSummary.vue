<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="summary">
		<div class="summary__header">
			<div class="summary__header__actions">
				<div class="summary__header__info">
					<NcIconSvgWrapper :size="20" :svg="creation" />
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
	</div>
</template>
<script>
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import creation from '@mdi/svg/svg/creation-outline.svg?raw'
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
    border: 2px solid var(--color-primary-element);
    border-radius:var( --border-radius-large) ;
    margin: 0 10px 20px 10px;
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
}

</style>
