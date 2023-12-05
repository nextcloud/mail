<!--
  - @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
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
				<NcActionButton
					:aria-label="t('mail', 'Expand composer')"
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
import { mapGetters } from 'vuex'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import ArrowExpandIcon from 'vue-material-design-icons/ArrowExpand.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'

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
		...mapGetters(['composerMessage']),
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

			await this.$store.dispatch('showMessageComposer')
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
	right: calc(var(--body-container-margin) + var(--default-grid-baseline));
	z-index: 1000;

	display: flex;
	align-items: center;
	gap: 5px;

	width: 360px;
	padding: 0 8px;

	// Retain border radius from outer body container for visual consistency
	height: calc(var(--body-container-radius) * 2);
	border-radius: var(--body-container-radius);
	//height: 44px;
	//border-radius: var(--border-radius-pill);

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
		flex-shrink: 0;
	}
}
</style>
