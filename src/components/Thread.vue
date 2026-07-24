<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<AppContentDetails id="mail-message">
		<!-- Show outer loading screen only if we have no data about the thread -->
		<Loading v-if="loading && thread.length === 0" :hint="t('mail', 'Loading thread')" />
		<Error
			v-else-if="errorTitle || errorMessage"
			:error="errorTitle ? errorTitle : t('mail', 'Not found')"
			:message="errorMessage" />
		<template v-else>
			<div id="mail-thread-header">
				<div id="mail-thread-header-fields">
					<h2 dir="auto" :title="threadSubject">
						{{ threadSubject }}
					</h2>
				</div>
			</div>
			<ThreadSummary v-if="showSummaryBox" :loading="summaryLoading" :summary="summaryText" />
			<ThreadEnvelope
				v-for="(env, index) in thread"
				ref="envelopeRefs"
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
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { NcAppContentDetails as AppContentDetails } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import Error from './Error.vue'
import Loading from './Loading.vue'
import ThreadEnvelope from './ThreadEnvelope.vue'
import ThreadSummary from './ThreadSummary.vue'
import logger from '../logger.js'
import { summarizeThread } from '../service/AiIntergrationsService.js'
import useMainStore from '../store/mainStore.js'
import { getRandomMessageErrorMessage } from '../util/ErrorMessageFactory.js'
import { buildHtmlMessageContent, buildMessageHeader, PRINT_DOCUMENT_STYLE, waitForImages } from '../util/printMessage.ts'
import { wait } from '../util/wait.js'

export default {
	name: 'Thread',
	components: {
		ThreadSummary,
		AppContentDetails,
		Error,
		Loading,
		ThreadEnvelope,
	},

	data() {
		return {
			summaryLoading: false,
			loading: true,
			message: undefined,
			errorMessage: '',
			errorTitle: '',
			expandedThreads: [],
			enabledThreadSummary: loadState('mail', 'llm_summaries_available', false),
			summaryText: '',
			summaryError: false,
			loadedThreads: 0,
		}
	},

	computed: {
		...mapStores(useMainStore),
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
			const trashMailbox = this.mainStore.getMailboxes(envelope.accountId).find((mailbox) => mailbox.specialRole === 'trash')
			const junkMailbox = this.mainStore.getMailboxes(envelope.accountId).find((mailbox) => mailbox.specialRole === 'junk')

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
				return envelopes.filter((envelope) => envelope.mailboxId === currentMailbox.databaseId)
			} else {
				return envelopes.filter((envelope) => !mailboxesToIgnore.includes(envelope.mailboxId))
			}
		},

		threadSubject() {
			const thread = this.thread
			if (thread.length === 0) {
				logger.warn('thread is empty')
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
		window.addEventListener('keydown', this.handleKeyDown)
	},

	beforeDestroy() {
		window.removeEventListener('keydown', this.handleKeyDown)
	},

	methods: {
		async updateSummary() {
			if (this.thread.length <= 2 || !this.enabledThreadSummary) {
				return
			}

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

		toggleExpand(threadId) {
			if (this.thread.length === 1) {
				return
			}
			if (!this.expandedThreads.includes(threadId)) {
				logger.debug(`expand thread ${threadId}`)
				this.expandedThreads.push(threadId)
			} else {
				logger.debug(`collapse thread ${threadId}`)
				this.expandedThreads = this.expandedThreads.filter((t) => t !== threadId)
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
			this.errorTitle = ''
			if (this.mainStore.getPreference('layout-message-view', 'threaded') === 'threaded') {
				await this.fetchThread()
			}
			this.updateSummary()
			this.loadedThreads = 0
		},

		async fetchThread() {
			this.loading = true
			this.errorMessage = ''
			this.errorTitle = ''
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
					this.errorTitle = t('mail', 'Could not load your message thread')
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
			if (!(event.ctrlKey || event.metaKey) || event.key !== 'p') {
				return
			}
			event.preventDefault()

			try {
				this.thread.forEach((envelope) => {
					if (!this.expandedThreads.includes(envelope.databaseId)) {
						this.expandedThreads.push(envelope.databaseId)
					}
				})

				while (this.loadedThreads < this.thread.length) {
					await wait(100)
				}

				const indices = this.thread
					.map((envelope, index) => index)
					.filter((index) => this.expandedThreads.includes(this.thread[index].databaseId))
				await this.printMessages(indices)
			} catch (error) {
				logger.error('Could not print message', { error })
				showError(t('mail', 'Could not print message'))
			}
		},

		addLoadedThread() {
			this.loadedThreads++
		},

		async print(threadIndex) {
			try {
				await this.printMessages([threadIndex ?? this.thread.length - 1])
			} catch (error) {
				logger.error('Could not print message', { error })
				showError(t('mail', 'Could not print message'))
			}
		},

		/**
		 * Print the given thread messages by rendering them into a dedicated,
		 * hidden iframe and printing that iframe's own document.
		 *
		 * This is deliberately isolated from the main document: the messages
		 * carry their own (untrusted) CSS, which would otherwise leak into
		 * the app and could shrink or clip the whole page. Because the iframe
		 * document is printed as a standalone page, long emails also paginate
		 * across pages instead of being cut off, and the app layout is never
		 * mutated — so nothing needs to be reloaded afterwards.
		 *
		 * @param {number[]} indices thread indices to print, in order
		 * @return {Promise<void>}
		 */
		async printMessages(indices) {
			const frame = document.createElement('iframe')
			frame.setAttribute('aria-hidden', 'true')
			// The message HTML is copied into this iframe from the sanitized
			// message frame, but unlike that frame it is not protected by the
			// backend's Content-Security-Policy. Sandbox it without
			// `allow-scripts` so no script or inline event handler in the
			// content can run; `allow-same-origin` lets us populate it and
			// `allow-modals` lets it open the print dialog.
			frame.setAttribute('sandbox', 'allow-same-origin allow-modals')
			frame.style.cssText = 'position: fixed; left: -9999px; top: 0; width: 0; height: 0; border: 0;'
			document.body.appendChild(frame)

			let cleanedUp = false
			const cleanup = () => {
				if (cleanedUp) {
					return
				}
				cleanedUp = true
				frame.remove()
			}

			try {
				const doc = frame.contentDocument || frame.contentWindow.document
				doc.open()
				doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>')
				doc.close()
				doc.title = this.threadSubject

				const style = doc.createElement('style')
				style.textContent = PRINT_DOCUMENT_STYLE
				doc.head.appendChild(style)

				indices.forEach((index) => this.appendMessageToDocument(doc, index))

				await waitForImages(doc)

				frame.contentWindow.addEventListener('afterprint', cleanup, { once: true })
				frame.contentWindow.focus()
				frame.contentWindow.print()

				// Safety net: some browsers don't emit `afterprint`. The delay
				// is long enough not to abort an open print dialog.
				setTimeout(cleanup, 60000)
			} catch (error) {
				cleanup()
				throw error
			}
		},

		appendMessageToDocument(doc, index) {
			const envelope = this.thread[index]
			const envelopeComponent = this.$refs.envelopeRefs?.[index]
			if (!envelope || !envelopeComponent) {
				return
			}

			const messageEl = envelopeComponent.$el
			const message = doc.createElement('div')
			message.className = 'print-message'
			message.appendChild(buildMessageHeader(doc, envelope))

			const iframe = messageEl.querySelector('iframe')
			if (iframe) {
				const sourceDocument = iframe.contentDocument || iframe.contentWindow.document
				message.appendChild(buildHtmlMessageContent(doc, sourceDocument))
			} else {
				const messageContainer = messageEl.querySelector('#message-container')
				if (messageContainer) {
					const content = doc.createElement('div')
					content.className = 'print-message-content'
					content.innerHTML = messageContainer.innerHTML
					message.appendChild(content)
				}
			}

			doc.body.appendChild(message)
		},
	},
}
</script>

<style lang="scss">
@use '../../css/variables.scss';

#mail-message {
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
	margin-bottom: 0;
	position: relative;
	border-radius: 5px;
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

@media only screen and (max-width: #{variables.$breakpoint-mobile}) {
    #mail-thread-header {
        position: sticky !important;
        top: 29px !important;
    }
}

#mail-thread-header-fields {
	// initial width
	width: 0;
	// while scrolling, the back button overlaps with subject on small screen
	// envelope margin (2×baseline) + border (2px) + header padding (--border-radius-container) + avatar (10×baseline) + sender margin (2×baseline)
	padding-inline-start: calc(var(--default-grid-baseline) * 14 + var(--border-radius-container) + 2px);
	// grow and try to fill 100%
	flex: 1 1 auto;
	background: var(--color-main-background);
	margin-inline-end: 5px;
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

@media only screen and (max-width: #{variables.$breakpoint-mobile}) {
    #mail-thread-header-fields {
        padding-inline-start: 48px;
    }
}

@media only screen and (max-width: #{variables.$breakpoint-mobile}) {
	#mail-thread-header-fields {
		margin-top: -32px;
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

@media only screen and (max-width: #{variables.$breakpoint-mobile}) {
    #mail-content {
        margin: calc(var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 3) 0 calc(var(--default-grid-baseline) * 3);
    }
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
	overflow-wrap: break-word;
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

.app-content-list-item-star.icon-starred {
	display: none;
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

// The "Print message" action and Ctrl/Cmd+P render into an isolated iframe
// and print that, so these rules only matter for a native browser print
// (browser menu / right-click → Print) of the live page. They hide the app
// chrome and let the document flow so the message body isn't printed inside
// the surrounding UI. Because the message body keeps its own sandboxed
// iframe, none of its CSS leaks into the app, and these rules revert
// automatically once printing is done — nothing needs to be reloaded.
@media print {
	html,
	body {
		height: auto !important;
		min-height: 0 !important;
		overflow: visible !important;
		position: static !important;
	}

	.app-navigation,
	.app-content-list,
	.message-composer,
	.reply-buttons,
	#reply-composer,
	#mail-message-has-blocked-content,
	.mail-message-attachments {
		display: none !important;
	}

	#mail-thread-header {
		position: static !important;
	}
}
</style>
