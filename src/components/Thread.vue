<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<AppContentDetails id="mail-message">
		<!-- Show outer loading screen only if we have no data about the thread -->
		<Loading v-if="loading && thread.length === 0" :hint="t('mail', 'Loading thread')" />
		<Error v-else-if="error"
			:error="error && error.message ? error.message : t('mail', 'Not found')"
			:message="errorMessage" />
		<template v-else>
			<div id="mail-thread-header">
				<div id="mail-thread-header-fields">
					<h2 dir="auto" :title="threadSubject">
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
							<template #trigger>
								<span class="avatar-more">
									{{ moreParticipantsString }}
								</span>
							</template>
							<RecipientBubble v-for="participant in threadParticipants.slice(participantsToDisplay)"
								:key="participant.email"
								:title="participant.email"
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
			<ThreadSummary v-if="showSummaryBox" :loading="summaryLoading" :summary="summaryText" />
			<ThreadEnvelope v-for="env in thread"
				:key="env.databaseId"
				:envelope="env"
				:mailbox-id="$route.params.mailboxId"
				:thread-subject="threadSubject"
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
import { showError } from '@nextcloud/dialogs'

import { prop, uniqBy } from 'ramda'
import debounce from 'lodash/fp/debounce.js'
import { loadState } from '@nextcloud/initial-state'

import { summarizeThread } from '../service/AiIntergrationsService.js'
import { getRandomMessageErrorMessage } from '../util/ErrorMessageFactory.js'
import Loading from './Loading.vue'
import logger from '../logger.js'
import Error from './Error.vue'
import RecipientBubble from './RecipientBubble.vue'
import ThreadEnvelope from './ThreadEnvelope.vue'
import ThreadSummary from './ThreadSummary.vue'
import { mapStores } from 'pinia'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'Thread',
	components: {
		RecipientBubble,
		ThreadSummary,
		AppContentDetails,
		Error,
		Loading,
		ThreadEnvelope,
		Popover,
	},

	data() {
		return {
			summaryLoading: false,
			loading: true,
			message: undefined,
			errorMessage: '',
			error: undefined,
			expandedThreads: [],
			participantsToDisplay: 999,
			resizeDebounced: debounce(500, this.updateParticipantsToDisplay),
			enabledThreadSummary: loadState('mail', 'llm_summaries_available', false),
			summaryText: '',
			summaryError: false,
		}
	},

	computed: {
		...mapStores(useMainStore),
		moreParticipantsString() {
			// Returns a number showing the number of thread participants that are not shown in the avatar-header
			return `+${this.threadParticipants.length - this.participantsToDisplay}`
		},
		threadId() {
			return parseInt(this.$route.params.threadId, 10)
		},
		thread() {
			const envelope = this.mainStore.getEnvelope(this.threadId)
			if (envelope === undefined) {
				return []
			}

			const envelopes = this.mainStore.getEnvelopesByThreadRootId(envelope.accountId, envelope.threadRootId)
			if (envelopes.length === 0) {
				return []
			}

			const currentMailbox = this.mainStore.getMailbox(envelope.mailboxId)
			const trashMailbox = this.mainStore.getMailboxes(envelope.accountId).find(mailbox => mailbox.specialRole === 'trash')
			const junkMailbox = this.mainStore.getMailboxes(envelope.accountId).find(mailbox => mailbox.specialRole === 'junk')

			let limitEnvelopesToCurrentMailbox = false
			const mailboxesToIgnore = []

			if (trashMailbox !== undefined) {
				if (currentMailbox.databaseId === trashMailbox.databaseId) {
					limitEnvelopesToCurrentMailbox = true
				}
				mailboxesToIgnore.push(trashMailbox.databaseId)
			}

			if (junkMailbox !== undefined) {
				if (currentMailbox.databaseId === junkMailbox.databaseId) {
					limitEnvelopesToCurrentMailbox = true
				}
				mailboxesToIgnore.push(junkMailbox.databaseId)
			}

			if (limitEnvelopesToCurrentMailbox) {
				return envelopes.filter(envelope => envelope.mailboxId === currentMailbox.databaseId)
			} else {
				return envelopes.filter(envelope => !mailboxesToIgnore.includes(envelope.mailboxId))
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
		showSummaryBox() {
			return this.thread.length > 2 && this.enabledThreadSummary && !this.summaryError
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
		async updateSummary() {
			if (this.thread.length <= 2 || !this.enabledThreadSummary) return

			this.summaryLoading = true
			try {
				this.summaryText = await summarizeThread(this.thread[0].databaseId)
			} catch (error) {
				this.summaryError = true
				showError(t('mail', 'Summarizing thread failed.'))
				logger.error('Summarizing thread failed', { error })
			} finally {
				this.summaryLoading = false
			}
		},
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
			this.updateSummary()

		},
		async fetchThread() {
			this.loading = true
			this.errorMessage = ''
			this.error = undefined
			const threadId = this.threadId

			try {
				const thread = await this.mainStore.fetchThread(threadId)
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
	padding: 0 0 10px 0;
	// somehow ios doesn't care about this !important rule
	// so we have to manually set left/right padding to chidren
	// for 100% to be used
	box-sizing: content-box !important;
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
	// while scrolling, the back button overlaps with subject on small screen
	padding-left: 86px;
	// grow and try to fill 100%
	flex: 1 1 auto;
	h2,
	p {
		padding-bottom: 7px;
		margin-bottom: 0;
		// some h2 styling coming from server add some space on top
		margin-top: 5px;
	}

	p {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.transparency {
		opacity: 0.6;
		a {
			font-weight: bold;
		}
	}
}
@media only screen and (max-width: 1024px) {
	#mail-thread-header-fields,
	h2 {
		margin-top: -20px;
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
	.app-content-details {
		min-width: 100% !important;
	}
	.action-items, .reply-buttons, .envelope__header__left__unsubscribe {
		display: none !important;
	}
	.envelope {
		border: none !important;
	}
}

.message-source {
	font-family: monospace;
	white-space: pre-wrap;
	user-select: text;
}

.avatar-header {
	height: var(--default-clickable-area);
	overflow: hidden;
	display: flex;
	align-items: stretch;

	::deep(.v-popper--theme-dropdown.v-popper__popper .v-popper__inner) {
		height: 300px;
		width: 250px;
		overflow: auto;
	}
}
.avatar-more {
	display: inline;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
	cursor: pointer;
}

.v-popper.avatar-more {
	padding: 6px;
}

.avatar-hidden {
	visibility: hidden;
}

.app-content-list-item-star.icon-starred {
	display: none;
}

.user-bubble__wrapper {
	height: var(--default-clickable-area);
	padding: var(--default-grid-baseline);
	margin-right: var(--default-grid-baseline);
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
}

.v-popper__popper--shown .user-bubble__wrapper {
	margin-right: 0 !important;

	.user-bubble__content {
		padding: calc(var(--default-grid-baseline));
	}

	.user-bubble__wrapper {
		padding: 0;
	}
}

.user-bubble__title {
	cursor: pointer;
}
</style>
