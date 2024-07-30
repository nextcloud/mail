<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="summary">
		<div class="summary__header">
			<div class="summary__header__actions">
				<NcChip :icon-svg="creation" no-close>
					{{ brand }}
				</NcChip>
				<NcButton :aria-label=" t('mail', 'Go to latest message')"
					type="secondary"
					@click="onScroll">
					{{ t('mail', 'Go to newest message') }}
					<template #icon>
						<ArrowDownIcon :size="16" />
					</template>
				</NcButton>
			</div>
			<div class="summary__header__title">
				<h2>{{ t('mail', 'Thread summary') }}</h2>
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
import creation from '@mdi/svg/svg/creation.svg'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'
import LoadingSkeleton from './LoadingSkeleton.vue'

export default {
	name: 'ThreadSummary',
	components: {
		LoadingSkeleton,
		NcButton,
		NcChip,
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
.summary{
    border: 2px solid var(--color-primary-element);
    border-radius:var( --border-radius-large) ;
    margin: 0 10px 20px 10px;
    padding: 10px;
    display: flex;
    flex-direction: column;

    &__header{
        display: flex;
        flex-direction: column;
        &__actions{
			display: flex;
			justify-content: space-between;
            &__brand{
                display: flex;
                align-items: center;
                background-color: var(--color-primary-light);
                border-radius: var(--border-radius-pill);
                width: fit-content;
                padding-right: 10px;
				padding-left: 4px;
				margin: 8px 0 8px 0;

                &__icon{
                    color:var(--color-primary-element);
                    margin-right: 5px;
					margin-left:5px
                }
            }
        }

    }
	@media only screen and (max-width: 600px) {
		.summary{
			&__header{
				flex-direction: column;
				&__actions{
					flex-direction: column;
				}
			}
		}
	}
}

</style>
