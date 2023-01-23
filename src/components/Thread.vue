<template>
	<AppContentDetails id="mail-message">
		<!-- Show outer loading screen only if we have no data about the thread -->
		<Loading v-if="loading && thread.length === 0" :hint="t('mail', 'Loading thread')" />
		<Error
			v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage" />
		<template v-else>
			<div id="mail-thread-header">
				<div id="mail-thread-header-fields">
					<h2 :title="threadSubject">
						{{ threadSubject }}
					</h2>
					<div v-if="thread.length" ref="avatarHeader" class="avatar-header">
						<!-- Participants that can fit in the parent div -->
						<RecipientBubble v-for="participant in threadParticipants.slice(0, participantsToDisplay)"
							:key="participant.email"
							:email="participant.email"
							:label="participant.label" />
						<!-- Indicator to show that there are more participants than displayed -->
						<Popover v-if="threadParticipants.length > participantsToDisplay"
							class="avatar-more">
							<span slot="trigger" class="avatar-more">
								{{ moreParticipantsString }}
							</span>
							<RecipientBubble v-for="participant in threadParticipants.slice(participantsToDisplay)"
								:key="participant.email"
								:email="participant.email"
								:label="participant.label" />
						</Popover>
						<!-- Remaining participants, if any (Needed to have avatarHeader reactive) -->
						<RecipientBubble v-for="participant in threadParticipants.slice(participantsToDisplay)"
							:key="participant.email"
							class="avatar-hidden"
							:email="participant.email"
							:label="participant.label" />
					</div>
				</div>
			</div>
			<ThreadEnvelope v-for="env in thread"
				:key="env.databaseId"
				:envelope="env"
				:mailbox-id="$route.params.mailboxId"
				:expanded="expandedThreads.includes(env.databaseId)"
				:full-height="thread.length === 1"
				@delete="$emit('delete', env.databaseId)"
				@move="onMove(env.databaseId)"
				@toggle-expand="toggleExpand(env.databaseId)" />
		</template>
	</AppContentDetails>
</template>

<script>
import { NcAppContentDetails as AppContentDetails, NcPopover as Popover } from '@nextcloud/vue'

import { prop, uniqBy } from 'ramda'
import debounce from 'lodash/fp/debounce'

import { getRandomMessageErrorMessage } from '../util/ErrorMessageFactory'
import Loading from './Loading'
import logger from '../logger'
import Error from './Error'
import RecipientBubble from './RecipientBubble'
import ThreadEnvelope from './ThreadEnvelope'

export default {
	name: 'Thread',
	components: {
		RecipientBubble,
		AppContentDetails,
		Error,
		Loading,
		ThreadEnvelope,
		Popover,
	},

	data() {
		return {
			loading: true,
			message: undefined,
			errorMessage: '',
			error: undefined,
			expandedThreads: [],
			participantsToDisplay: 999,
			resizeDebounced: debounce(500, this.updateParticipantsToDisplay),
		}
	},

	computed: {
		moreParticipantsString() {
			// Returns a number showing the number of thread participants that are not shown in the avatar-header
			return `+${this.threadParticipants.length - this.participantsToDisplay}`
		},
		threadId() {
			return parseInt(this.$route.params.threadId, 10)
		},
		thread() {
			const envelope = this.$store.getters.getEnvelope(this.threadId)
			if (envelope === undefined) {
				return []
			}

			const envelopes = this.$store.getters.getEnvelopesByThreadRootId(envelope.accountId, envelope.threadRootId)
			if (envelopes.length === 0) {
				return []
			}

			const currentMailbox = this.$store.getters.getMailbox(envelope.mailboxId)
			const trashMailbox = this.$store.getters.getMailboxes(currentMailbox.accountId).find(mailbox => mailbox.specialRole === 'trash')

			if (trashMailbox === undefined) {
				return envelopes
			}

			if (currentMailbox.databaseId === trashMailbox.databaseId) {
				return envelopes.filter(envelope => envelope.mailboxId === trashMailbox.databaseId)
			} else {
				return envelopes.filter(envelope => envelope.mailboxId !== trashMailbox.databaseId)
			}
		},
		threadParticipants() {
			const recipients = this.thread.flatMap(envelope => {
				return envelope.from.concat(envelope.to).concat(envelope.cc)
			})
			return uniqBy(prop('email'), recipients)
		},
		threadSubject() {
			const thread = this.thread
			if (thread.length === 0) {
				console.warn('thread is empty')
				return ''
			}
			return thread[0].subject || this.t('mail', 'No subject')
		},
	},
	watch: {
		$route(to, from) {
			if (
				from.name === to.name
				&& from.params.mailboxId === to.params.mailboxId
				&& from.params.threadId === to.params.threadId
				&& from.params.filter === to.params.filter
			) {
				logger.debug('navigated but the thread is still the same')
				return
			}
			logger.debug('navigated to another thread', { to, from })
			this.resetThread()
		},
	},
	created() {
		this.resetThread()
		window.addEventListener('resize', this.resizeDebounced)
	},
	beforeDestroy() {
		window.removeEventListener('resize', this.resizeDebounced)
	},
	methods: {
		updateParticipantsToDisplay() {
			// Wait until everything is in place
			if (!this.$refs.avatarHeader || !this.threadParticipants) {
				return
			}

			// Compute the number of participants to display depending on the width available
			const avatarHeader = this.$refs.avatarHeader
			const maxWidth = (avatarHeader.clientWidth - 100) // Reserve 100px for the avatar-more span
			let childrenWidth = 0
			let fits = 0
			let idx = 0
			while (childrenWidth < maxWidth && fits < this.threadParticipants.length) {
				// Skipping the 'avatar-more' span
				if (avatarHeader.childNodes[idx].clientWidth === undefined) {
					idx += 3
					continue
				}
				childrenWidth += avatarHeader.childNodes[idx].clientWidth
				fits++
				idx++
			}

			if (childrenWidth > maxWidth) {
				// There's not enough space to show all thread participants
				if (fits > 1) {
					this.participantsToDisplay = fits - 1
				} else if (fits === 0) {
					this.participantsToDisplay = 1
				} else {
					this.participantsToDisplay = fits
				}
			} else {
				// There's enough space to show all thread participants
				this.participantsToDisplay = this.threadParticipants.length
			}
		},
		toggleExpand(threadId) {
			if (this.thread.length === 1) {
				return
			}
			if (!this.expandedThreads.includes(threadId)) {
				console.debug(`expand thread ${threadId}`)
				this.expandedThreads.push(threadId)
			} else {
				console.debug(`collapse thread ${threadId}`)
				this.expandedThreads = this.expandedThreads.filter(t => t !== threadId)
			}
		},
		onMove(threadId) {
			if (threadId === this.threadId) {
				this.$router.replace({
					name: 'mailbox',
					params: {
						mailboxId: this.$route.params.mailboxId,
					},
				})
			} else {
				this.expandedThreads = this.expandedThreads.filter((id) => id !== threadId)
				this.fetchThread()
			}
		},
		async resetThread() {
			this.expandedThreads = [this.threadId]
			this.errorMessage = ''
			this.error = undefined
			await this.fetchThread()
			this.updateParticipantsToDisplay()

		},
		async fetchThread() {
			this.loading = true
			this.errorMessage = ''
			this.error = undefined
			const threadId = this.threadId

			try {
				const thread = await this.$store.dispatch('fetchThread', threadId)
				logger.debug(`thread for envelope ${threadId} fetched`, { thread })
				// TODO: add timeout so that envelope isn't flagged when only viewed
				//       for a few seconds
				if (threadId !== parseInt(this.$route.params.threadId, 10)) {
					logger.debug("User navigated away, loaded envelope won't be shown nor flagged as seen", {
						oldId: threadId,
						newId: this.$route.params.threadId,
					})
					return
				}

				if (thread.length === 0) {
					logger.info('thread could not be found and is empty', { threadId })
					this.errorMessage = getRandomMessageErrorMessage()
					this.loading = false
					return
				}

				this.loading = false
			} catch (error) {
				logger.error('could not load envelope thread', { threadId, error })
				if (error?.response?.status === 403) {
					this.error = t('mail', 'Could not load your message thread')
					this.errorMessage = t('mail', 'The thread doesn\'t exist or has been deleted')
					this.loading = false
				} else {
					this.errorMessage = t('mail', 'Could not load your message thread')
				}
			}
		},
	},
}
</script>

<style lang="scss">
#mail-message {
	margin-bottom: 30vh;

	.icon-loading {
		&:only-child:after {
			margin-top: 20px;
		}
	}
}

.mail-message-body {
	flex: 1;
	margin-bottom: 30px;
	position: relative;
}

#mail-thread-header {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	padding: 30px 0;
	// somehow ios doesn't care about this !important rule
	// so we have to manually set left/right padding to chidren
	// for 100% to be used
	box-sizing: content-box !important;
	height: 44px;
	width: 100%;

	z-index: 100;
	position: fixed; // ie fallback
	position: -webkit-sticky; // ios/safari fallback
	position: sticky;
	top: 0;
	background: -webkit-linear-gradient(var(--color-main-background), var(--color-main-background) 80%, rgba(255,255,255,0));
	background: -o-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
	background: -moz-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
	background: linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
}

#mail-thread-header-fields {
	// initial width
	width: 0;
	padding-left: 70px;
	// grow and try to fill 100%
	flex: 1 1 auto;
	h2,
	p {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		padding-bottom: 7px;
		margin-bottom: 0;
	}

	.transparency {
		opacity: 0.6;
		a {
			font-weight: bold;
		}
	}
}

.attachment-popover {
	position: sticky;
	bottom: 12px;
	text-align: center;
}

.tooltip-inner {
	text-align: left;
}

#mail-content {
	margin: 10px 38px 0 59px;
}

#mail-content iframe {
	width: 100%;
}

#show-images-text {
	display: none;
}

#mail-content a,
.mail-signature a {
	color: #07d;
	border-bottom: 1px dotted #07d;
	text-decoration: none;
	word-wrap: break-word;
}

/* Show action button label and move icon to the left
   on screens larger than 600px */
@media only screen and (max-width: 600px) {
	.action-label {
		display: none;
	}
}
@media only screen and (min-width: 600px) {
	.icon-reply-white,
	.icon-reply-all-white {
		background-position: 12px center;
	}
}

@media print {
	#mail-thread-header-fields {
		position: relative;
	}
	.app-content-details,
	.splitpanes__pane-details {
		max-width: unset !important;
		width: 100% !important;
	}
	#header,
	.app-navigation,
	#reply-composer,
	#forward-button,
	#mail-message-has-blocked-content,
	.app-content-list,
	.message-composer,
	.splitpanes__pane-list,
	.mail-message-attachments {
		display: none !important;
	}
	.app-content {
		margin-left: 0 !important;
	}
	.mail-message-body {
		margin-bottom: 0 !important;
	}
}

.message-source {
	font-family: monospace;
	white-space: pre-wrap;
	user-select: text;
}

.avatar-header {
	max-height: 24px;
	overflow: hidden;
}
.avatar-more {
	display: inline;
	background-color: var(--color-background-dark);
	padding: 0px 0px 1px 1px;
	border-radius: 10px;
	cursor: pointer;
}
.avatar-hidden {
	visibility: hidden;
}
.popover__wrapper {
	max-width: 500px;
}

.app-content-list-item-star.icon-starred {
	display: none;
}
.user-bubble__wrapper {
	margin-right: 4px;
}
.user-bubble__title {
	cursor: pointer;
}
</style>
