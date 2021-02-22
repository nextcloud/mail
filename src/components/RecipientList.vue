<template>
	<div ref="avatarHeader" class="avatar-header">
		<div v-if="label" ref="label" class="label">
			<span>{{ label }}</span>
		</div>
		<!-- Participants that can fit in the parent div -->
		<RecipientBubble v-for="participant in participants.slice(0,participantsToDisplay)"
			:key="participant.email"
			class="avatar"
			:email="participant.email"
			:label="participant.label" />
		<!-- Indicator to show that there are more participants than displayed -->
		<Popover v-if="participants.length > participantsToDisplay" class="avatar-more">
			<span slot="trigger">
				{{ moreParticipantsString }}
			</span>
			<RecipientBubble v-for="participant in participants.slice(participantsToDisplay)"
				:key="participant.email"
				:email="participant.email"
				:label="participant.label" />
		</Popover>
		<!-- Optional caret for showing more information -->
		<div v-if="showCaret" class="caret">
			<div :class="expandCaret ? 'icon-triangle-n' : 'icon-triangle-s'"
				@click="$emit('click-caret')" />
		</div>
		<!-- Remaining participants, if any (Needed to have avatarHeader reactive) -->
		<RecipientBubble v-for="participant in participants.slice(participantsToDisplay)"
			:key="participant.email"
			class="avatar avatar-hidden"
			:email="participant.email"
			:label="participant.label" />
	</div>
</template>

<script>
import Popover from '@nextcloud/vue/dist/Components/Popover'
import RecipientBubble from './RecipientBubble'
import debounce from 'lodash/fp/debounce'

export default {
	name: 'RecipientList',
	components: {
		RecipientBubble,
		Popover,
	},
	props: {
		label: {
			type: String,
			required: false,
			default: '',
		},
		participants: {
			type: Array,
			required: true,
		},
		showCaret: {
			type: Boolean,
			required: false,
			default: false,
		},
		expandCaret: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			participantsToDisplay: 999,
			resizeDebounced: debounce(500, this.updateParticipantsToDisplay),
		}
	},
	computed: {
		moreParticipantsString() {
			// Returns a number showing the number of thread participants that are not shown in the avatar-header
			return `+${this.participants.length - this.participantsToDisplay}`
		},
	},
	watch: {
		participants() {
			this.updateParticipantsToDisplay()
		},
	},
	created() {
		window.addEventListener('resize', this.resizeDebounced)
	},
	mounted() {
		this.updateParticipantsToDisplay()
	},
	beforeDestroy() {
		window.removeEventListener('resize', this.resizeDebounced)
	},
	methods: {
		updateParticipantsToDisplay() {
			// Wait until everything is in place
			if (!this.$refs.avatarHeader || !this.participants) {
				return
			}

			// Only include recipient bubbles
			const children = Array
				.from(this.$refs.avatarHeader.childNodes)
				.filter((node) => node.nodeType === Node.ELEMENT_NODE)
				.filter((node) => node.classList.contains('avatar'))

			// Reserve 100px for the avatar-more span
			const maxWidth = this.$refs.avatarHeader.clientWidth - 100

			let childrenWidth = 0
			let fits = 0

			// Calculate full width including margins
			const getFullWidth = (node) => {
				const styles = window.getComputedStyle(node)
				const margins = parseFloat(styles.marginLeft) + parseFloat(styles.marginRight)
				return node.clientWidth + margins
			}

			// Measure label width if present
			if (this.$refs.label) {
				childrenWidth += getFullWidth(this.$refs.label)
			}

			for (const child of children) {
				const newWidth = childrenWidth + getFullWidth(child)
				if (newWidth > maxWidth) {
					break
				}

				fits++
				childrenWidth = newWidth
			}

			this.participantsToDisplay = fits
		},
	},
}
</script>

<style lang="scss" scoped>
.avatar + .avatar {
	margin-left: 4px;
}

.avatar-header {
	max-height: 24px;
	overflow: hidden;
}

.avatar-more {
	display: inline-block;
	vertical-align: middle;

	span {
		display: inline-block;
		vertical-align: top;
		height: 20px;
		line-height: 20px;
		background-color: var(--color-background-dark);
		border-radius: 10px;
		cursor: pointer;
		padding: 0 4px;
	}
}

.avatar-hidden {
	visibility: hidden;
}

.popover__wrapper {
	max-width: 500px;
}

::v-deep .user-bubble__title {
	cursor: pointer;
}

.label {
	display: inline-block;
	margin-right: 2px;
	vertical-align: middle;

	span {
		height: 20px;
		line-height: 20px;
		vertical-align: top;
	}
}

.caret {
	display: inline-block;
	vertical-align: middle;

	div {
		display: inline-block;
		vertical-align: top;
		width: 20px;
		height: 20px;
		background-color: var(--color-background-dark);
		border-radius: 10px;
		cursor: pointer;
	}
}
</style>
