<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- This wrapper can be either a router link or a `<li>` -->
	<component :is="to ? 'router-link' : 'NcVNodes'"
		v-slot="{ href: routerLinkHref, navigate, isActive }"
		:custom="to ? true : null"
		:to="to"
		:exact="to ? exact : null">
		<li class="list-item__wrapper"
			:class="{ 'list-item__wrapper--active' : isActive || active }">
			<div ref="list-item"
				class="list-item"
				:class="{
					'list-item--compact': compact,
					'list-item--one-line': oneLine,
					'list-item--multiline': !oneLine,
				}"
				@mouseover="handleMouseover"
				@mouseleave="handleMouseleave">
				<a :id="anchorId || undefined"
					:aria-label="linkAriaLabel"
					class="list-item__anchor"
					:href="routerLinkHref || href"
					:target="target || (href === '#' ? undefined : '_blank')"
					:rel="href === '#' ? undefined : 'noopener noreferrer'"
					@focus="showActions"
					@focusout="handleBlur"
					@click="onClick($event, navigate, routerLinkHref)"
					@contextmenu.prevent
					@keydown.esc="hideActions">
					<!-- @slot This slot is used for the NcAvatar or icon, the content of this slot must not be interactive -->
					<slot name="icon" />

					<div class="list-item-content">
						<div class="list-item-content__name">
							<!-- @slot Slot for the first line of the component. prop 'name' is used as a fallback is no slots are provided -->
							<span>
								<slot name="name">{{ name }}</slot>
							</span>
						</div>
						<div class="list-item-content__inner">
							<div class="list-item-content__inner__main">
								<div v-if="hasSubname"
									class="list-item-content__inner__subname"
									:class="{'list-item-content__inner__subname--bold': bold}">
									<!-- @slot Slot for the second line of the component -->
									<slot name="subname" />
								</div>
								<div v-if="$slots.tags" class="list-item-content__inner__tags">
									<!-- @slot This slot is used for the third line of the component -->
									<slot name="tags" />
								</div>
							</div>

							<div class="list-item-content__inner__details">
								<div v-if="showDetails" class="list-item-content__inner__details__details">
									<!-- @slot This slot is used for some details in form of icon (prop `details` as a fallback) -->
									<slot name="details">{{ details }}</slot>
								</div>

								<!-- Counter and indicator -->
								<div v-if="counterNumber || hasIndicator"
									v-show="showAdditionalElements"
									class="list-item-content__inner__details__extra">
									<NcCounterBubble v-if="counterNumber"
										:active="isActive || active"
										class="list-item-content__inner__details__extra__counter"
										:type="counterType">
										{{ counterNumber }}
									</NcCounterBubble>

									<span v-if="hasIndicator" class="list-item-content__inner__details__extra__indicator">
										<!-- @slot This slot is used for some indicator in form of icon -->
										<slot name="indicator" />
									</span>
								</div>
							</div>
						</div>
					</div>
				</a>

				<div class="list-item__hoverable">
					<EnvelopeSingleClickActions :is-read="isRead"
						:is-important="isImportant"
						@delete="$emit('delete')"
						@toggle-important="$emit('toggle-important')"
						@toggle-seen="$emit('toggle-seen')" />

					<!-- Actions -->
					<div v-show="forceDisplayActions || displayActionsOnHoverFocus"
						class="list-item__actions"
						@focusout="handleBlur">
						<NcActions ref="actions"
							:primary="isActive || active"
							:aria-label="computedActionsAriaLabel"
							variant="tertiary"
							@update:open="handleActionsUpdateOpen">
							<template #icon>
								<DotsHorizontal :size="20" />
							</template>
							<!-- @slot Provide the actions for the right side quick menu -->
							<slot name="actions" />
						</NcActions>
					</div>
				</div>
			</div>
		</li>
	</component>
</template>

<script>
import { NcActions, NcCounterBubble, NcVNodes } from '@nextcloud/vue'
import EnvelopeSingleClickActions from './EnvelopeSingleClickActions.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'

export default {
	name: 'EnvelopeSkeleton',

	components: {
		NcActions,
		NcCounterBubble,
		NcVNodes,
		EnvelopeSingleClickActions,
		DotsHorizontal,
	},

	props: {
		/**
		 * The details text displayed in the upper right part of the component
		 */
		details: {
			type: String,
			default: '',
		},

		/**
		 * Name (first line of text)
		 */
		name: {
			type: String,
			required: true,
		},

		/**
		 * Pass in `true` if you want the matching behavior to
		 * be non-inclusive: https://router.vuejs.org/api/#exact
		 */
		exact: {
			type: Boolean,
			default: false,
		},

		/**
		 * The route for the router link.
		 */
		to: {
			type: [String, Object],
			default: null,
		},

		/**
		 * The value for the external link
		 */
		href: {
			type: String,
			default: '#',
		},

		target: {
			type: String,
			default: '',
		},

		/**
		 * Id for the `<a>` element
		 */
		anchorId: {
			type: String,
			default: '',
		},

		/**
		 * Make subname bold
		 */
		bold: {
			type: Boolean,
			default: false,
		},

		/**
		 * Show the NcListItem in compact design
		 */
		compact: {
			type: Boolean,
			default: false,
		},

		/**
		 * Toggle the active state of the component
		 */
		active: {
			type: Boolean,
			default: false,
		},

		/**
		 * Aria label for the wrapper element
		 */
		linkAriaLabel: {
			type: String,
			default: '',
		},

		/**
		 * Aria label for the actions toggle
		 */
		actionsAriaLabel: {
			type: String,
			default: '',
		},

		/**
		 * If different from 0 this component will display the
		 * NcCounterBubble component
		 */
		counterNumber: {
			type: [Number, String],
			default: 0,
		},

		/**
		 * Outlined or highlighted state of the counter
		 */
		counterType: {
			type: String,
			default: '',
			validator(value) {
				return ['highlighted', 'outlined', ''].indexOf(value) !== -1
			},
		},

		/**
		 * To be used only when the elements in the actions menu are very important
		 */
		forceDisplayActions: {
			type: Boolean,
			default: false,
		},
		/**
		 * Show the list component layout
		 */
		oneLine: {
			type: Boolean,
			default: false,
		},
		isRead: {
			type: Boolean,
			default: false,
		},
		isImportant: {
			type: Boolean,
			default: false,
		},
	},

	emits: [
		'click',
		'update:menuOpen',
	],

	data() {
		return {
			hovered: false,
			hasActions: false,
			hasSubname: false,
			displayActionsOnHoverFocus: false,
			menuOpen: false,
			hasIndicator: false,
			hasDetails: false,
		}
	},

	computed: {
		showAdditionalElements() {
			return !this.displayActionsOnHoverFocus || this.forceDisplayActions
		},

		showDetails() {
			return (this.details !== '' || this.hasDetails)
				&& (!this.displayActionsOnHoverFocus || this.forceDisplayActions)
		},

		computedActionsAriaLabel() {
			return this.actionsAriaLabel || t('Actions for item with name "{name}"', { name: this.name })
		},
	},

	watch: {

		menuOpen(newValue) {
			// A click outside both the menu and the root element hides the actions again
			if (!newValue && !this.hovered) {
				this.displayActionsOnHoverFocus = false
			}
		},
	},

	mounted() {
		this.checkSlots()
	},

	updated() {
		this.checkSlots()
	},

	methods: {
		/**
		 * Handle link click
		 *
		 * @param {MouseEvent|KeyboardEvent} event - Native click or keydown event
		 * @param {Function} [navigate] - VueRouter link's navigate if any
		 * @param {string} [routerLinkHref] - VueRouter link's href
		 */
		onClick(event, navigate, routerLinkHref) {
			// Always forward native event
			this.$emit('click', event)
			// Do not navigate with control keys - it is opening in a new tab
			if (event.metaKey || event.altKey || event.ctrlKey || event.shiftKey) {
				return
			}
			// Prevent default link behaviour if it's a router-link and navigate manually
			if (routerLinkHref) {
				navigate?.(event)
				event.preventDefault()
			}
		},

		showActions() {
			if (this.hasActions) {
				this.displayActionsOnHoverFocus = true
			}
			this.hovered = false
		},

		hideActions() {
			this.displayActionsOnHoverFocus = false
		},

		/**
		 * @param {FocusEvent} event UI event
		 */
		handleBlur(event) {
			// do not hide if open
			if (this.menuOpen) {
				return
			}
			// do not hide if focus is kept within
			if (this.$refs['list-item'].contains(event.relatedTarget)) {
				return
			}
			this.hideActions()
		},

		/**
		 * Hide the actions on mouseleave unless the menu is open
		 */
		handleMouseleave() {
			if (!this.menuOpen) {
				this.displayActionsOnHoverFocus = false
			}
			this.hovered = false
		},

		handleMouseover() {
			this.showActions()
			this.hovered = true
		},

		handleActionsUpdateOpen(e) {
			this.menuOpen = e
			this.$emit('update:menuOpen', e)
		},

		// Check if subname and actions slots are populated
		checkSlots() {
			if (this.hasActions !== !!this.$slots.actions) {
				this.hasActions = !!this.$slots.actions
			}
			if (this.hasSubname !== !!this.$slots.subname) {
				this.hasSubname = !!this.$slots.subname
			}
			if (this.hasIndicator !== !!this.$slots.indicator) {
				this.hasIndicator = !!this.$slots.indicator
			}
			if (this.hasDetails !== !!this.$slots.details) {
				this.hasDetails = !!this.$slots.details
			}
		},
	},
}
</script>

<style lang="scss" scoped>

.list-item__wrapper {
	display: flex;
	position: relative;
	width: 100%;
	// padding for the focus-visible styles. Width is reduced to compensate it
	padding: 2px 4px;
	// The first and lastelement needs also padding for the box shadow of the focus-visible effect
	&:first-of-type {
		padding-block-start: 4px;
	}
	&:last-of-type {
		padding-block-end: 4px
	}

	&--active,
	&.active {
		.list-item {
			background-color: var(--color-primary-element);
			&:hover,
			&:focus-within,
			&:has(:focus-visible),
			&:has(:active) {
				background-color: var(--color-primary-element-hover);
			}
		}

		.list-item-content__name,
		.list-item-content__subname,
		.list-item-content__details,
		.list-item-details__details {
			color: var(--color-primary-element-text);
		}

		.list-item-content__quick-actions :deep(svg) {
			fill: var(--color-primary-element-text) !important;
		}
	}
	.list-item-content__name,
	.list-item-content__subname,
	.list-item-content__details,
	.list-item-details__details {
		white-space: nowrap;
		margin-block: 0;
		margin-inline-start: 0;
		margin-inline-end: auto;
		overflow: hidden;
		text-overflow: ellipsis;
	}
}

// NcListItem
.list-item {
	--list-item-padding: calc(var(--default-grid-baseline) * 2);
	// The content are two lines of text and respect the 1.5 line height
	--list-item-border-radius: var(--border-radius-element, 32px);
	--list-item-height: calc(4 * var(--default-line-height));
	height: var(--list-item-height);

	// General styles
	box-sizing: border-box;
	display: flex;
	position: relative;
	flex: 0 0 auto;
	justify-content: flex-start;
	// we need to make sure the elements are not cut off by the border
	width: 100%;
	border-radius: var(--border-radius-element, 32px);
	cursor: pointer;
	transition: background-color var(--animation-quick) ease-in-out;
	list-style: none;
	flex-wrap: nowrap !important;
	padding: var(--default-grid-baseline);

	&:hover,
	&:has(:active),
	&:has(:focus-visible) {
		background-color: var(--color-background-hover);

		a {
			max-width: calc(100% - var(--default-clickable-area));
		}
	}

	&:has(&__anchor:focus-visible) {
		outline: 2px solid var(--color-main-text);
		box-shadow: 0 0 0 4px var(--color-main-background);
	}

	&__hoverable {
		visibility: hidden;
	}

	.list-item-content {
		display: flex;
		flex-direction: column;

		&__name {
			min-width: 100px;
			flex: 1 1 10%;
			font-weight: 500;
			// we changed the time/date and actions to be alighned with the name
			max-width: 78%;
			line-height: var(--default-line-height);

			span {
				min-width: 0;
				overflow: hidden;
				flex: 1 1 auto;
				text-overflow: ellipsis;
			}
		}

		&__inner {
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			max-width: 100%;

			&__main {
				flex: 0 1 auto;
				min-width: 0;
			}

			&__subname {
				flex: 1 0;
				min-width: 0;
				color: var(--color-text-maxcontrast);
				line-height: var(--default-line-height);
				&--bold {
					font-weight: 500;
				}
			}

			&__tags {
				overflow-y: auto;
				display: flex;
				flex-direction: row;
				justify-content: start;
				align-items: center;
				line-height: var(--default-line-height);
			}

			&__details {
				display: flex;
				flex-direction: column;
				justify-content: start;
				align-items: end;
				white-space: nowrap;
				gap: 4px;
				// to align details on top instead of in the center. The right way to do it would be to change the template, but that breaks one-line layout
				margin-top: -22px;

				&__details {
					margin: 0 4px !important;
					color: var(--color-text-maxcontrast);
					height: var(--default-line-height);
					font-weight: normal;
				}

				&__extra {
					margin: 0 4px;
					height: calc(var(--default-line-height) * var(--default-font-size));
					display: flex;
					align-items: center;

					&__indicator {
						margin: 0 4px;
					}
				}
			}
		}
	}

	a {
		max-width: 100%;
		margin: 0;
	}

	.one-line .envelope__subtitle__subject {
		max-width: 300px;
	}

	&--compact {
		--list-item-padding: 2px;
	}

	&--one-line {
		--list-item-height: calc(var(--default-line-height) * var(--default-font-size) * 2 + var(--list-item-padding) * 4);
		--list-item-border-radius: var(--border-radius-element, calc(var(--default-clickable-area) / 2));
		padding-block: calc(var(--list-item-padding) * 2);
		--list-item-padding: 2px;
		height: unset;

		.list-item-content {
			flex-direction: row;
			align-content: center;
			align-items: center;

			&__name {
				align-self: center;
				min-width: 300px;
				padding-inline-end: calc(var(--default-grid-baseline) * 2);
			}

			&__inner {
				overflow-y: hidden;
			}

			&__inner__main {
				display: flex;
				justify-content: start;
				min-width: 0;
			}

			&__inner__details {
				flex-direction: row;
				align-items: unset;
				justify-content: end;
				margin-top: 0;
				margin-inline-start: 0;
			}
		}

		a {
			margin: 0;
			align-items: center;
			height: unset;
		}

		.list-item__actions {
			align-self: center;
			margin-top: 0;
		}
	}

	&__anchor {
		display: flex;
		flex: 1 1 auto;
		align-items: start;
		height: var(--list-item-height);
		min-width: 0;

		// This is handled by the parent container
		&:focus-visible {
			outline: none;
		}
	}

	&-content {
		display: flex;
		flex: 1 0;
		justify-content: space-between;
		padding-inline-start: 8px;
		min-width: 0;
		&__main {
			flex: 1 0;
			width: 0;
			margin: auto 0;

			&--oneline {
				display: flex;
			}
		}
	}

}

.list-item:hover {
	.list-item__hoverable {
		visibility: visible;
		position: absolute;
		display: flex;
		background: var(--color-main-background);
		border-radius: var(--border-radius-element);
		box-shadow: 0 0 4px 0 var(--color-box-shadow);
		height: var(--default-clickable-area);
		inset-inline-end: var(--default-grid-baseline);

		:deep(svg) {
			fill: var(--color-main-text) !important; // needed to not inherit active styling
		}
	}
}

.list-item--multiline:hover .list-item-content__name {
	display: flex;
	justify-content: space-between;
	width: 100%;
	max-width: unset;
	max-height: calc(var(--default-font-size) * var(--default-line-height));
}

// Force icon to be in line with the first two lines
:deep(.app-content-list-item-icon), :deep(.avatardiv), :deep(.avatardiv__initials-wrapper) {
	height: calc(var(--header-menu-item-height) - 4px);
	width: calc(var(--header-menu-item-height) - 4px);
}
</style>
