<template>
	<div>
		<transition name="multiselect-header">
			<div v-if="selectMode" key="multiselect-header" class="multiselect-header">
				<div class="button primary" @click.prevent="markSelectedSeenOrUnseen">
					<span id="action-label">{{
						areAllSelectedRead
							? t('mail', 'Mark ' + selection.length + ' unread')
							: t('mail', 'Mark ' + selection.length + ' read')
					}}</span>
				</div>
				<Actions class="app-content-list-item-menu" menu-align="right">
					<ActionButton icon="icon-starred" @click.prevent="favoriteOrUnfavoriteAll">
						{{
							areAllSelectedFavorite
								? t('mail', 'Unfavorite ' + selection.length)
								: t('mail', 'Favorite ' + selection.length)
						}}
					</ActionButton>
					<ActionButton icon="icon-close" @click.prevent="unselectAll">
						{{ t('mail', 'Unselect ' + selection.length) }}
					</ActionButton>
					<ActionButton icon="icon-delete" @click.prevent="deleteAllSelected">
						{{ t('mail', 'Delete ' + selection.length) }}
					</ActionButton>
				</Actions>
			</div>
		</transition>
		<transition-group name="list">
			<div id="list-refreshing"
				key="loading"
				class="icon-loading-small"
				:class="{refreshing: refreshing}" />
			<Envelope
				v-for="env in envelopes"
				:key="env.databaseId"
				:data="env"
				:mailbox="mailbox"
				:selected="isEnvelopeSelected(envelopes.indexOf(env))"
				:select-mode="selectMode"
				@delete="$emit('delete', env.databaseId)"
				@update:selected="onEnvelopeSelectToggle(env, ...$event)" />
			<div
				v-if="loadMoreButton && !loadingMore"
				:key="'list-collapse-' + searchQuery"
				class="load-more"
				@click="$emit('loadMore')">
				{{ t('mail', 'Load more') }}
			</div>
			<div id="load-more-mail-messages" key="loadingMore" :class="{'icon-loading-small': loadingMore}" />
		</transition-group>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

import Envelope from './Envelope'
import logger from '../logger'

export default {
	name: 'EnvelopeList',
	components: {
		Actions,
		ActionButton,
		Envelope,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		mailbox: {
			type: Object,
			required: true,
		},
		envelopes: {
			type: Array,
			required: true,
		},
		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},
		refreshing: {
			type: Boolean,
			required: true,
			default: true,
		},
		loadingMore: {
			type: Boolean,
			required: true,
		},
		loadMoreButton: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			selection: [],
		}
	},
	computed: {
		selectMode() {
			// returns true when in selection mode (where the user selects several emails at once)
			return this.selection.length > 0
		},
		areAllSelectedRead() {
			// returns false if at least one selected message has not been read yet
			return this.selection.every((idx) => this.envelopes[idx].flags.unseen === false)
		},
		areAllSelectedFavorite() {
			// returns false if at least one selected message has not been favorited yet
			return this.selection.every((idx) => this.envelopes[idx].flags.flagged === true)
		},
	},
	methods: {
		isEnvelopeSelected(idx) {
			if (this.selection.length === 0) {
				return false
			}

			return this.selection.includes(idx)
		},
		markSelectedSeenOrUnseen() {
			const seenFlag = this.areAllSelectedRead
			this.selection.forEach((envelopeId) => {
				this.$store.dispatch('markEnvelopeSeenOrUnseen', {
					envelope: this.envelopes[envelopeId],
					seenFlag,
				})
			})
			this.unselectAll()
		},
		favoriteOrUnfavoriteAll() {
			const favFlag = !this.areAllSelectedFavorite
			this.selection.forEach((envelopeId) => {
				this.$store.dispatch('markEnvelopeFavoriteOrUnfavorite', {
					envelope: this.envelopes[envelopeId],
					favFlag,
				})
			})
			this.unselectAll()
		},
		deleteAllSelected() {
			this.selection.forEach((envelopeId) => {
				// Navigate if the message being deleted is the one currently viewed
				if (this.envelopes[envelopeId].databaseId === this.$route.params.threadId) {
					let next
					if (envelopeId === 0) {
						next = this.envelopes[envelopeId + 1]
					} else {
						next = this.envelopes[envelopeId - 1]
					}

					if (next) {
						this.$router.push({
							name: 'message',
							params: {
								mailboxId: this.$route.params.mailboxId,
								threadId: next.databaseId,
							},
						})
					}
				}
				logger.info(`deleting message ${this.envelopes[envelopeId].databaseId}`)
				this.$store.dispatch('deleteMessage', {
					id: this.envelopes[envelopeId].databaseId,
				})
			})
			this.unselectAll()
		},
		onEnvelopeSelectToggle(envelope, selected) {
			const idx = this.envelopes.indexOf(envelope)

			if (selected) {
				envelope.flags.selected = true
				this.selection.push(idx)
			} else {
				envelope.flags.selected = false
				this.selection.splice(this.selection.indexOf(idx), 1)
			}

		},
		unselectAll() {
			this.envelopes.forEach((env) => {
				env.flags.selected = false
			})
			this.selection = []
		},
	},
}
</script>

<style lang="scss" scoped>
div {
	// So we can align the loading spinner in the Priority inbox
	position: relative;
}

.load-more {
	text-align: center;
	margin-top: 10px;
	cursor: pointer;
	color: var(--color-text-maxcontrast);
}

.multiselect-header {
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
	background-color: var(--color-main-background-translucent);
	position: sticky;
	top: 0px;
	height: 48px;
	z-index: 100;
}

#load-more-mail-messages {
	margin: 10px auto;
	padding: 10px;
	margin-top: 50px;
	margin-bottom: 50px;
}

/* TODO: put this in core icons.css as general rule for buttons with icons */
#load-more-mail-messages.icon-loading-small {
	padding-left: 32px;
	background-position: 9px center;
}

#list-refreshing {
	position: absolute;
	left: calc(50% - 8px);
	overflow: hidden;
	padding: 12px;
	background-color: var(--color-main-background);
	z-index: 1;
	border-radius: var(--border-radius-pill);
	border: 1px solid var(--color-border);
	top: -24px;
	opacity: 0;
	transition-property: top, opacity;
	transition-duration: 0.5s;
	transition-timing-function: ease-in-out;

	&.refreshing {
		top: 4px;
		opacity: 1;
	}
}

.multiselect-header-enter-active,
.multiselect-header-leave-active,
.list-enter-active,
.list-leave-active {
	transition: all var(--animation-slow);
}

.multiselect-header-enter,
.multiselect-header-leave-to,
.list-enter,
.list-leave-to {
	opacity: 0;
	height: 0px;
	transform: scaleY(0);
}

#action-label {
	vertical-align: middle;
}
@media only screen and (min-width: 600px) {
	#action-label {
		display: block;
	}
}
</style>
