<template>
	<nav-element
		v-bind="navElement"
		:class="{
			'app-content-list-entry--no-icon': !isIconShown,
			'app-content-list-entry--opened': opened,
			'app-content-list-entry--pinned': pinned,
			'app-content-list-entry--editing': editing,
			'app-content-list-entry--deleted': undo,
			'app-content-list-entry--collapsible': collapsible,
			active: isActive,
		}"
		class="app-content-list-entry"
	>
		<!-- Icon and title -->
		<a v-if="!undo && !editing" class="app-content-list-entry-link" href="#" @click="onClick">
			<!-- icon if not collapsible -->
			<!-- never show the icon over the collapsible if mobile -->
			<div
				:class="{'icon-loading-small': loading, [icon]: icon && isIconShown}"
				class="app-content-list-entry-icon"
			>
				<slot v-if="!loading" v-show="isIconShown" name="icon" />
			</div>
			<span class="app-content-list-entry__title" :title="title">
				{{ title }}
			</span>
			<span class="app-content-list-entry__info"><slot name="info" /></span>
		</a>

		<!-- Counter and Actions -->
		<div v-if="hasUtils" class="app-content-list-entry__utils">
			<slot name="counter" />
			<Actions
				menu-align="right"
				:open="menuOpen"
				:force-menu="forceMenu"
				:default-icon="menuIcon"
				@update:open="onMenuToggle"
			>
				<ActionButton v-if="editable && !editing" icon="icon-rename" @click="handleEdit">
					{{ editLabel }}
				</ActionButton>
				<ActionButton
					v-if="undo"
					icon="app-content-list-entry__deleted-button icon-history"
					@click="handleUndo"
				/>
				<slot name="actions" />
			</Actions>
		</div>

		<!-- edit entry -->
		<div v-if="editing" class="app-content-list-entry__edit">
			<form @submit.prevent="handleRename" @keydown.esc.exact.prevent="cancelEdit">
				<input
					ref="inputTitle"
					v-model="newTitle"
					type="text"
					class="app-content-list-entry__edit-input"
					:placeholder="editPlaceholder !== '' ? editPlaceholder : title"
				/>
				<button type="submit" class="icon-confirm" @click.stop.prevent="handleRename" />
				<button type="reset" class="icon-close" @click.stop.prevent="cancelEdit" />
			</form>
		</div>

		<!-- Children elements -->
		<ul v-if="canHaveChildren && hasChildren" class="app-content-list-entry__children">
			<slot />
		</ul>
	</nav-element>
</template>

<script>
import {directive as ClickOutside} from 'v-click-outside'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
// import AppNavigationIconCollapsible from '@nextcloud/vue/src/components/AppNavigationItem/AppNavigationIconCollapsible'
import isMobile from '@nextcloud/vue/dist/Mixins/isMobile'

export default {
	name: 'AppContentListEntry',

	components: {
		Actions,
		ActionButton,
		// AppNavigationIconCollapsible,
	},
	directives: {
		ClickOutside,
	},
	mixins: [isMobile],
	props: {
		/**
		 * The title of the element.
		 */
		title: {
			type: String,
			required: true,
		},
		/**
		 * Refers to the icon on the left, this prop accepts a class
		 * like 'icon-category-enabled'.
		 */
		icon: {
			type: String,
			default: '',
		},

		/**
		 * Displays a loading animated icon on the left of the element
		 * instead of the icon.
		 */
		loading: {
			type: Boolean,
			default: false,
		},
		/**
		 * Passing in a route will make the root element of this
		 * component a <router-link /> that points to that route.
		 * By leaving this blank, the root element will be a <li>.
		 */
		to: {
			type: [String, Object],
			default: '',
		},
		/**
		 * Pass in `true` if you want the matching behaviour to
		 * be non-inclusive: https://router.vuejs.org/api/#exact
		 */
		exact: {
			type: Boolean,
			default: false,
		},
		/**
		 * Gives the possibility to collapse the children elements into the
		 * parent element (true) or expands the children elements (false).
		 */
		allowCollapse: {
			type: Boolean,
			default: false,
		},
		/**
		 * Makes the title of the item editable by providing an `ActionButton`
		 * component that toggles a form
		 */
		editable: {
			type: Boolean,
			default: false,
		},
		/**
		 * Only for 'editable' items, sets label for the edit action button.
		 */
		editLabel: {
			type: String,
			default: '',
		},
		/**
		 * Only for 'editable' items, sets placeholder text for the editing form.
		 */
		editPlaceholder: {
			type: String,
			default: '',
		},
		/**
		 * Pins the item to the bottom left area, above the settings. Do not
		 * place 'non-pinned' `AppnavigationItem` components below `pinned`
		 * ones.
		 */
		pinned: {
			type: Boolean,
			default: false,
		},
		/**
		 * Puts the item in the 'undo' state.
		 */
		undo: {
			type: Boolean,
			default: false,
		},
		/**
		 * The navigation collapsible state (synced)
		 */
		open: {
			type: Boolean,
			default: false,
		},
		/**
		 * The actions menu open state (synced)
		 */
		menuOpen: {
			type: Boolean,
			default: false,
		},
		/**
		 * Force the actions to display in a three dot menu
		 */
		forceMenu: {
			type: Boolean,
			default: false,
		},
		/**
		 * The action's menu default icon
		 */
		menuIcon: {
			type: String,
			default: undefined,
		},
	},

	data() {
		return {
			newTitle: '',
			opened: this.open,
			editing: false,
		}
	},
	computed: {
		collapsible() {
			return this.allowCollapse && !!this.$slots.default
		},

		// is the icon shown?
		// we don't show it on mobile if the entry is collapsible
		// we show the collapse toggle directly!
		isIconShown() {
			return !this.collapsible || (this.collapsible && !this.isMobile)
		},

		// Checks if the component is already a children of another
		// instance of AppNavigationItem
		canHaveChildren() {
			if (this.$parent.$options._componentTag === 'AppNavigationItem') {
				return false
			} else {
				return true
			}
		},
		hasChildren() {
			if (this.$slots.default) {
				return true
			} else {
				return false
			}
		},
		hasUtils() {
			if (this.editing) {
				return false
			} else if (this.$slots.actions || this.$slots.counter || this.editable || this.undo) {
				return true
			} else {
				return false
			}
		},
		// This is used to decide which outer element type to use
		// li or router-link
		navElement() {
			if (this.to) {
				return {
					is: 'router-link',
					tag: 'li',
					to: this.to,
					exact: this.exact,
				}
			}
			return {
				is: 'li',
			}
		},
		isActive() {
			return this.to && this.$route === this.to
		},
	},
	watch: {
		open(newVal) {
			this.opened = newVal
		},
	},
	methods: {
		// sync opened menu state with prop
		onMenuToggle(state) {
			this.$emit('update:menuOpen', state)
		},
		// toggle the collapsible state
		toggleCollapse() {
			this.opened = !this.opened
			this.$emit('update:open', this.opened)
		},

		// forward click event
		onClick(event) {
			this.$emit('click', event)
		},

		// Edition methods
		handleEdit() {
			this.newTitle = this.title
			this.editing = true
			this.onMenuToggle(false)
			this.$nextTick(() => {
				this.$refs.inputTitle.focus()
			})
		},
		cancelEdit() {
			this.editing = false
		},
		handleRename() {
			this.$emit('update:title', this.newTitle)
			this.newTitle = ''
			this.editing = false
		},

		// Undo methods
		handleUndo() {
			this.$emit('undo')
		},
	},
}
</script>

<style lang="scss" scoped>
@import '@nextcloud/vue/src/assets/variables';

.app-content-list-entry {
	position: relative;
	display: flex;
	flex-shrink: 0;
	flex-wrap: wrap;
	order: 1;
	box-sizing: border-box;
	width: 100%;
	min-height: $clickable-area;
	// When .active class is applied, change color background of link and utils. The
	// !important prevents the focus state to override the active state.
	&.active > a,
	&.active > a ~ .app-content-list-entry__utils {
		background-color: var(--color-primary-light) !important;
	}

	/* hide deletion/collapse of subitems */
	&.app-content-list-entry--deleted,
	&.app-content-list-entry--collapsible:not(.app-content-list-entry--opened) {
		> ul {
			// NO ANIMATE because if not really hidden, we can still tab through it
			display: none;
		}
	}

	// Main entry link
	.app-content-list-entry-link {
		z-index: 100; /* above the bullet to allow click*/
		display: flex;
		overflow: hidden;
		flex: 1 1 0;
		box-sizing: border-box;
		min-height: $clickable-area;
		padding: 0;
		padding-right: $icon-margin;
		white-space: nowrap;
		color: var(--color-main-text);
		background-repeat: no-repeat;
		background-position: $icon-margin center;
		background-size: $icon-size $icon-size;
		line-height: $clickable-area;
		&:hover,
		&:hover ~ .app-content-list-entry__utils,
		&:focus,
		&:focus ~ .app-content-list-entry__utils {
			background-color: var(--color-background-hover);
		}
		&.active,
		&:active,
		&:active ~ .app-content-list-entry__utils {
			background-color: var(--color-primary-light);
		}

		.app-content-list-entry-icon {
			display: flex;
			align-items: center;
			flex: 0 0 $clickable-area;
			justify-content: center;
			width: $clickable-area;
			height: $clickable-area;
			background-size: $icon-size $icon-size;
		}

		.app-content-list-entry__title {
			overflow: hidden;
			width: 25%;
			white-space: nowrap;
			text-overflow: ellipsis;
		}

		.app-content-list-entry__info {
			overflow: hidden;
			white-space: nowrap;
			text-overflow: ellipsis;
			font-style: italic;
		}
	}

	/* Second level nesting for lists */
	.app-content-list-entry__children {
		position: relative;
		display: flex;
		flex: 0 1 auto;
		flex-direction: column;
		width: 100%;

		.app-content-list-entry {
			display: inline-flex;
			flex-wrap: wrap;
			padding-left: $clickable-area - $icon-margin;
		}
	}
}

/* Deleted entries */
.app-content-list-entry__deleted {
	display: inline-flex;
	flex: 1 1 0;
	padding-left: $clickable-area - $icon-margin !important;
	.app-content-list-entry__deleted-description {
		position: relative;
		overflow: hidden;
		flex: 1 1 0;
		white-space: nowrap;
		text-overflow: ellipsis;
		line-height: $clickable-area;
	}
}

.app-content-list-entry__edit {
	flex: 1 0 100%;
	/* Ugly hack for overriding the main entry link */
	/* align the input correctly with the link text
	44px-6px padding for the input */
	padding-left: $clickable-area - $icon-margin - 6px !important;
	form {
		display: flex;
		width: 100%;
		.app-content-list-entry__edit-input {
			flex: 1 1 100%;
		}

		// submit and cancel buttons
		button {
			display: flex;
			align-items: center;
			justify-content: center;
			width: $clickable-area !important;
			color: var(--color-main-text);
			background: none;
			font-size: 16px;

			// icon hover/focus feedback
			&::before {
				opacity: $opacity_normal;
			}
			&:hover,
			&:focus {
				&::before {
					opacity: $opacity_full;
				}
			}
		}
		.icon-close {
			margin: 0;
			border: none;
			background-color: transparent;
		}
	}
}

/* Makes the icon of the collapsible element disappear
*  When hovering on the root element */
.app-content-list-entry--collapsible {
	//shows the triangle button
	.icon-collapse {
		visibility: hidden;
	}
	&.app-content-list-entry--no-icon,
	&:hover,
	&:focus {
		a .app-content-list-entry-icon {
			// hides the icon
			visibility: hidden;
		}
		.icon-collapse {
			//shows the triangle button
			visibility: visible;
		}
		// prevent the icon of children elements from being hidden
		// by the previous rule
		.app-content-list-entry__children li:not(.app-content-list-entry--collapsible) a :first-child {
			visibility: visible;
		}
	}
}

/* counter and actions */
.app-content-list-entry__utils {
	display: flex;
	align-items: center;
	flex: 0 1 auto;
	// visually balance the menu so it's not
	// stuck to the scrollbar
	.action-item {
		margin-right: 2px;
	}
}

// STATES
/* editing state */
.app-content-list-entry--editing {
	.app-content-list-entry-edit {
		z-index: 250;
		opacity: 1;
	}
}

/* deleted state */
.app-content-list-entry--deleted {
	.app-content-list-entry-deleted {
		z-index: 250;
		transform: translateX(0);
	}
}

/* pinned state */
.app-content-list-entry--pinned {
	order: 2;
	margin-top: auto;
	// only put a marginTop auto to the first one!
	~ .app-content-list-entry--pinned {
		margin-top: 0;
	}
}
</style>
