<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="composer-session"
		:class="{'composer-session--disabled': disabled}"
		@click="onShowComposer">
		<div class="composer-session__icon">
			<PencilIcon :size="20" />
		</div>

		<div class="composer-session__text">
			{{ title }}
		</div>

		<div class="composer-session__actions">
			<NcActions>
				<NcActionButton :aria-label="t('mail', 'Expand composer')"
					:disabled="disabled"
					@click.stop="onShowComposer">
					<template #icon>
						<ArrowExpandIcon :size="20" />
					</template>
				</NcActionButton>
			</NcActions>
			<NcActions>
				<NcActionButton :aria-label="t('mail', 'Close composer')"
					:disabled="disabled"
					@click.stop="onClose">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
				</NcActionButton>
			</NcActions>
		</div>
	</div>
</template>

<script>
import { NcActions, NcActionButton } from '@nextcloud/vue'
import PencilIcon from 'vue-material-design-icons/PencilOutline.vue'
import ArrowExpandIcon from 'vue-material-design-icons/ArrowExpand.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import useMainStore from '../store/mainStore.js'
import { mapStores, mapState } from 'pinia'

export default {
	name: 'ComposerSessionIndicator',
	components: {
		NcActions,
		NcActionButton,
		PencilIcon,
		ArrowExpandIcon,
		CloseIcon,
	},
	data() {
		return {

		}
	},
	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['composerMessage']),
		title() {
			return this.composerMessage?.data.subject || t('mail', 'Untitled message')
		},
		disabled() {
			return this.composerMessage?.indicatorDisabled ?? false
		},
	},
	methods: {
		async onShowComposer() {
			if (this.disabled) {
				return
			}

			await this.mainStore.showMessageComposerMutation()
		},
		onClose() {
			if (this.disabled) {
				return
			}

			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
.composer-session {
	position: fixed;
	bottom: calc(var(--body-container-margin) + var(--default-grid-baseline));
	inset-inline-end: calc(var(--body-container-margin) + var(--default-grid-baseline));
	z-index: 1000;

	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);

	width: 360px;
	padding: 0 calc(var(--default-grid-baseline) * 2);

	// Retain border radius from outer body container for visual consistency
	border-radius: var(--body-container-radius);

	// Mobile
	@media (max-width: 1024px) {
		width: calc(100% - 2 * var(--default-grid-baseline));
		height: 44px;
		border-radius: var(--border-radius-pill);
	}

	// Conditional hover and pointer styles
	background-color: var(--color-primary-element-light);
	&:not(&--disabled) {
		&:hover {
			background-color: var(--color-primary-element-light-hover);
		}

		&, * {
			cursor: pointer;
		}
	}

	&__icon {
		width: 44px;
	}

	&__text {
		flex: 1 auto;
		font-weight: bold;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__actions {
		display: flex;
		flex-shrink: 0;
	}
}
</style>
