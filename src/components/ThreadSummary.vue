<template>
	<div class="summary">
		<div class="summary__header">
			<div class="summary__header__actions">
				<span class="summary__header__actions__brand">
					<CreationIcon class="summary__header__actions__brand__icon" />
					<p>{{ brand }}</p>
				</span>
				<NcActions />
				<NcButton
					:aria-label=" t('mail', 'Go to latest message')"
					type="secondary"
					@click="onScroll">
					{{ t('mail', 'Go to newest message') }}
					<template #icon>
						<ArrowDownIcon
							:size="20" />
					</template>
				</NcButton>
			</div>
			<div class="summary__header__title">
				<h2>{{ t('mail', 'Thread Summary') }}</h2>
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
import NcButton from '@nextcloud/vue/dist/Components/NcButton'
import NcActions from '@nextcloud/vue/dist/Components/NcActions'
import CreationIcon from 'vue-material-design-icons/Creation'
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown'
import LoadingSkeleton from './LoadingSkeleton'

export default {
	name: 'ThreadSummary',
	components: {
		LoadingSkeleton,
		NcButton,
		NcActions,
		CreationIcon,
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
    padding: 28px;
    display: flex;
    flex-direction: column;

    &__header{
        display: flex;
        flex-direction: column;
        &__actions{
			display: flex;
			justify-content: space-between;
			margin-bottom: 10px;
            &__brand{
                display: flex;
                align-items: center;
                background-color: var(--color-primary-light);
                border-radius: var(--border-radius-pill);
                width: fit-content;
                padding-right: 5px;
                padding-left: 5px;

                &__icon{
                    color:var(--color-primary-element);
                    margin-right: 5px;
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
					&__brand{
						flex-wrap: nowrap;
						margin-bottom: 10px;
					}
				}
			}
		}
	}
}

</style>
