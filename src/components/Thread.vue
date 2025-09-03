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
			<ThreadEnvelope v-for="(env, index) in thread"
				:key="env.databaseId"
				:envelope="env"
				:mailbox-id="$route.params.mailboxId"
				:thread-subject="threadSubject"
				:expanded="expandedThreads.includes(env.databaseId)"
				:full-height="thread.length === 1"
				:thread-index="index"
				@delete="$emit('delete', env.databaseId)"
				@loaded="addLoadedThread"
				@move="onMove(env.databaseId)"
				@toggle-expand="toggleExpand(env.databaseId)"
				@print="print" />
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
import moment from '@nextcloud/moment'

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
	props: {
		currentAccountEmail: {
			type: String,
			required: true,
		},
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
			loadedThreads: 0,
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

			if (this.mainStore.getPreference('layout-message-view', 'threaded') === 'singleton') {
				return [envelope]
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
			}).filter(participant => participant.email !== this.currentAccountEmail)
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
		window.addEventListener('keydown', this.handleKeyDown)
	},
	beforeDestroy() {
		window.removeEventListener('resize', this.resizeDebounced)
		window.removeEventListener('keydown', this.handleKeyDown)
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
			if (this.mainStore.getPreference('layout-message-view', 'threaded') === 'threaded') {
				await this.fetchThread()
			}
			this.updateParticipantsToDisplay()
			this.updateSummary()
			this.loadedThreads = 0
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
				} else if (error?.response?.status === 500) {
					this.error = { message: t('mail', 'Email was not able to be opened') }
					this.loading = false
				} else {
					this.errorMessage = t('mail', 'Could not load your message thread')
				}
			}
		},
		async handleKeyDown(event) {
			if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
				event.preventDefault()

				this.thread.forEach((thread) => {
					if (!this.expandedThreads.includes(thread.databaseId)) this.expandedThreads.push(thread.databaseId)
				})

				while (true) {
					if (this.loadedThreads === this.thread.length) {
						break
					}
					await new Promise(resolve => setTimeout(resolve, 100))
				}

				const virtualIframe = document.createElement('iframe')
				virtualIframe.style.display = 'none'
				document.body.appendChild(virtualIframe)
				const virtualIframeDocument = virtualIframe.contentDocument || virtualIframe.contentWindow.document
				virtualIframeDocument.open()
				virtualIframeDocument.write(`<html><head><title>${t('mail', 'Print')}</title></head><body></body></html>`)
				virtualIframeDocument.close()

				virtualIframeDocument.body.appendChild(this.addThreadInfo(virtualIframeDocument))

				const messageContainers = document.querySelectorAll('#message-container')
				for (const [index, messageContainer] of messageContainers.entries()) {
					const iframe = messageContainer.querySelector('iframe')

					this.addMessageInfo(virtualIframeDocument, index)

					if (!iframe) {
						const div = virtualIframeDocument.createElement('div')
						div.innerHTML = messageContainer.innerHTML
						virtualIframeDocument.body.appendChild(div)
						continue
					}

					iframe.setAttribute('data-iframe-size', 'true')

					if (!iframe.contentWindow.document.readyState === 'complete') {
						await new Promise((resolve) => {
							iframe.contentWindow.onload = resolve
						})
					}

					const iframeDocument = iframe.contentDocument || iframe.contentWindow.document
					const iframeContent = iframeDocument.body.innerHTML
					const div = virtualIframeDocument.createElement('div')

					div.innerHTML = iframeContent
					virtualIframeDocument.body.appendChild(div)
				}

				const images = virtualIframeDocument.querySelectorAll('img')
				let imagesLoaded = 0

				images.forEach((img) => {
					img.addEventListener('load', () => {
						imagesLoaded++
						if (imagesLoaded === images.length) {
							virtualIframe.contentWindow.print()
							this.removeIframe(virtualIframe)
						}
					})
					img.addEventListener('error', () => {
						imagesLoaded++
						if (imagesLoaded === images.length) {
							virtualIframe.contentWindow.print()
							this.removeIframe(virtualIframe)
						}
					})
				})

				if (images.length === 0) {
					virtualIframe.contentWindow.print()
					this.removeIframe(virtualIframe)
				}

			}
		},
		removeIframe(virtualIframe) {
			setTimeout(() => {
				document.body.removeChild(virtualIframe)
			}, 500)
		},
		addMessageInfo(virtualIframeDocument, index) {
			const hr = virtualIframeDocument.createElement('hr')
			hr.style.border = '1px solid black'

			const subjectSpan = virtualIframeDocument.createElement('p')
			subjectSpan.style.fontWeight = 'bold'
			subjectSpan.textContent = t('mail', 'Subject') + ': ' + this.thread[index].subject

			const senderSpan = virtualIframeDocument.createElement('p')
			senderSpan.style.fontWeight = 'bold'
			senderSpan.textContent = t('mail', 'From') + ': ' + this.thread[index].from[0].label + ' <' + this.thread[index].from[0].email + '>'

			const dateSpan = virtualIframeDocument.createElement('p')
			dateSpan.style.fontWeight = 'bold'
			dateSpan.textContent = t('mail', 'Date') + ': ' + moment.unix(this.thread[index].dateInt).format('LLL')

			const recipientSpan = virtualIframeDocument.createElement('p')
			recipientSpan.style.fontWeight = 'bold'
			recipientSpan.textContent = t('mail', 'To') + ': ' + this.thread[index].to[0].label + this.thread[index].to[0].email

			virtualIframeDocument.body.appendChild(hr)
			virtualIframeDocument.body.appendChild(subjectSpan)
			virtualIframeDocument.body.appendChild(senderSpan)
			virtualIframeDocument.body.appendChild(dateSpan)
			virtualIframeDocument.body.appendChild(recipientSpan)
		},
		addThreadInfo(document) {
			const threadInfo = document.createElement('div')
			threadInfo.style.marginTop = '20px'
			threadInfo.style.marginBottom = '20px'
			threadInfo.className = 'mail-thread-info'

			const subjectLine = document.createElement('h2')
			subjectLine.textContent = `${this.threadSubject}`
			threadInfo.appendChild(subjectLine)

			const participantsLine = document.createElement('p')
			participantsLine.textContent = this.threadParticipants
				.map(participant => `${participant.label} <${participant.email}>`)
				.join(', ')
			threadInfo.appendChild(participantsLine)

			return threadInfo
		},
		addLoadedThread() {
			this.loadedThreads++
		},
		print(threadIndex) {
			setTimeout(() => {
				try {
					const messages = Array.from(document.querySelectorAll('.html-message-body, .mail-message-body'))

					let message

					if (threadIndex !== undefined) {
						message = messages[threadIndex * 2] ?? messages.pop()
					} else {
						// By default, we print the last opened message in the thread
						message = messages.pop()
					}

					const iframe = message.querySelector('iframe')

					if (iframe === null) {
						// Handle plain text messages
						const messageContainer = message.querySelector('#message-container')

						if (messageContainer) {
							// Create a new iframe
							const newIframe = document.createElement('iframe')
							newIframe.style.display = 'none' // Hide the iframe
							document.body.appendChild(newIframe)

							// Insert the message content into the iframe
							const iframeDocument = newIframe.contentDocument || newIframe.contentWindow.document
							iframeDocument.open()
							iframeDocument.write(`
								<html>
									<head>
										<title>${this.threadSubject}</title>
									</head>
									<body>
										<div class="message-container">${messageContainer.innerHTML}</div>
									</body>
								</html>
							`)

							const threadInfo = this.addThreadInfo(iframeDocument)
							iframeDocument.body.insertBefore(threadInfo, iframeDocument.body.firstChild)

							setTimeout(() => {
								threadInfo.remove()
							}, 5000)

							iframeDocument.close()

							newIframe.contentWindow.print()

							// Clean up: remove the iframe after printing
							setTimeout(() => {
								document.body.removeChild(newIframe)
							}, 500)
						}

						return
					}

					const iframeDocument = iframe.contentDocument || iframe.contentWindow.document

					const threadInfo = this.addThreadInfo(iframeDocument)
					iframeDocument.body.insertBefore(threadInfo, iframeDocument.body.firstChild)

					setTimeout(() => {
						threadInfo.remove()
					}, 200)

					iframe.contentWindow.print()
				} catch (error) {
					showError(t('mail', 'Could not print message'))
				}
			}, 100)
		},
	},
}
</script>

<style lang="scss">
#mail-message {
	margin-bottom: 30vh;
	width: 100%;
	max-width: 100%;

	.icon-loading {
		&:only-child:after {
			margin-top: calc(var(--default-line-height) - var(--default-grid-baseline));
		}
	}
}

.mail-message-body {
	flex: 1;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	position: relative;
}

#mail-thread-header {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	padding: 0 0 calc(var(--default-grid-baseline) * 2) 0;
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
	margin-bottom: 5px;

	&::before {
		content: '';
		position: absolute;
		top: 0;
		inset-inline-start: 50%;
		transform: translateX(-50%);
		width: 100vw;
		height: 100%;
		background: var(--color-main-background);
		border-bottom: var(--border-width-input-focused) solid var(--color-border);
		z-index: -1;
	}
}

#mail-thread-header-fields {
	// initial width
	width: 0;
	// while scrolling, the back button overlaps with subject on small screen
	// 66px to allign with the sender Envelope -> 8px margin + 2px border+ avatar -> 40px width  + envelope__header -> 8px padding + sender-> margin 8px
	padding-inline-start: 66px;
	// grow and try to fill 100%
	flex: 1 1 auto;
	h2,
	p {
		padding-bottom: calc(var(--default-grid-baseline) * 2);
		margin-bottom: 0;
		// some h2 styling coming from server add some space on top
		margin-top: var(--default-grid-baseline);
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
	#mail-thread-header-fields {
		margin-top: -20px;
	}
}

.attachment-popover {
	position: sticky;
	bottom: calc(var(--default-grid-baseline) * 3);
	text-align: center;
}

.tooltip-inner {
	text-align: start;
}

#mail-content {
	margin: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 10) 0 calc(var(--default-grid-baseline) * 14);
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
	border-bottom: var(--border-width-input) dotted #07d;
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
		background-position: calc(var(--default-grid-baseline) * 3) center;
	}
}

.avatar-header {
	height: var(--default-clickable-area);
	overflow: hidden;
	display: flex;
	align-items: stretch;

	:deep(.v-popper--theme-dropdown.v-popper__popper .v-popper__inner) {
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
	padding: calc(var(--default-grid-baseline) * 2);
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
	margin-inline-end: var(--default-grid-baseline);
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
}

.v-popper__popper--shown .user-bubble__wrapper {
	margin-inline-end: 0 !important;

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
