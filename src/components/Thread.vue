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
				@move="onMove(env.databaseId)"
				@print-shortcut="printThread"
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
import {
	BROWSER_PRINT_NOTICE_ID,
	buildBrowserPrintNotice,
	buildMessageHeader,
	isPrintShortcut,
	PRINT_CONTENT_HEIGHT,
	PRINT_DOCUMENT_STYLE,
	PRINT_FRAME_ID,
	PRINT_STAGING_STYLE,
	renderHtmlMessage,
	renderPlainTextMessage,
	waitForImages,
} from '../util/printMessage.ts'
import { wait } from '../util/wait.js'

/**
 * How long Ctrl/Cmd+P waits for the messages it expanded to render before it
 * prints the ones that did.
 */
const THREAD_RENDER_TIMEOUT = 30000
const THREAD_RENDER_POLL_INTERVAL = 100

/**
 * How long a print rendering is kept around in case the browser emits no
 * `afterprint`. Long enough not to interrupt a print dialog that is still open.
 */
const PRINT_CLEANUP_TIMEOUT = 60000

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
			isolatedPrint: false,
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

		/**
		 * Thread indices of the currently expanded messages, in thread order.
		 * Those are the messages that are rendered, and therefore the ones that
		 * can be printed.
		 *
		 * @return {number[]}
		 */
		expandedIndices() {
			return this.thread.flatMap((envelope, index) => (
				this.expandedThreads.includes(envelope.databaseId) ? [index] : []
			))
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

	mounted() {
		// A print started from the browser itself (its menu, or right-click →
		// Print) cannot be served, see `buildBrowserPrintNotice`. The notice that
		// says so is put up once and shown for print media only, rather than
		// staged from a `beforeprint` handler, so that it is also there for a
		// print the browser announces late or not at all.
		if (!document.getElementById(BROWSER_PRINT_NOTICE_ID)) {
			document.body.appendChild(buildBrowserPrintNotice(document))
		}
	},

	beforeDestroy() {
		window.removeEventListener('keydown', this.handleKeyDown)
		document.getElementById(BROWSER_PRINT_NOTICE_ID)?.remove()
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

		/**
		 * Take the print shortcut while the app itself has the focus. A message
		 * has a window of its own and its keydowns never reach here, so
		 * `MessageHTMLBody` listens in its frame and emits `print-shortcut`
		 * instead — both end up in `printThread`.
		 *
		 * @param {KeyboardEvent} event the app window's keydown event
		 */
		async handleKeyDown(event) {
			if (!isPrintShortcut(event)) {
				return
			}
			event.preventDefault()

			await this.printThread()
		},

		/**
		 * Print every message of the thread, expanding the ones that are still
		 * collapsed so that there is something to print of them.
		 *
		 * @return {Promise<void>}
		 */
		async printThread() {
			// Asking again while the previous print is still being prepared, or
			// while its dialog is still open, would stage the thread a second time
			// and expand it all over again.
			if (this.isolatedPrint) {
				return
			}

			this.thread.forEach((envelope) => {
				if (!this.expandedThreads.includes(envelope.databaseId)) {
					this.expandedThreads.push(envelope.databaseId)
				}
			})

			// Expanding a message loads it from the backend, and only a rendered
			// message can be printed. Give up eventually so that one message
			// failing to load doesn't hold the print back for good.
			const deadline = Date.now() + THREAD_RENDER_TIMEOUT
			while (!this.threadPrintable() && Date.now() < deadline) {
				await wait(THREAD_RENDER_POLL_INTERVAL)
			}

			await this.printMessages(this.expandedIndices)
		},

		/**
		 * Whether every message of the thread is rendered and can be printed.
		 *
		 * The messages are asked themselves rather than counted as they report
		 * being loaded: a message emits `loaded` whenever its loading state
		 * settles, which is neither once per message nor once per thread.
		 *
		 * @return {boolean}
		 */
		threadPrintable() {
			const envelopes = this.$refs.envelopeRefs
			return envelopes?.length === this.thread.length
				&& envelopes.every((component) => component.printable)
		},

		async print(threadIndex) {
			await this.printMessages([threadIndex])
		},

		/**
		 * Print the given thread messages by rendering them into a dedicated,
		 * hidden iframe and printing that iframe's own document.
		 *
		 * This is deliberately isolated from the main document: the app layout is
		 * never mutated, so nothing needs to be reloaded afterwards, and the
		 * messages are printed as a standalone page instead of inside the
		 * surrounding UI. The frame is only a container for that document — what
		 * the browser prints and paginates is the document itself, so the print
		 * still follows the paper size and orientation of the print dialog.
		 *
		 * @param {number[]} indices thread indices to print, in order
		 * @return {Promise<void>}
		 */
		async printMessages(indices) {
			// Set for as long as a print of our own is being prepared or is
			// waiting on its dialog, so that a second print does not stage the
			// thread once more on top of the first.
			if (this.isolatedPrint) {
				return
			}
			this.isolatedPrint = true

			const frame = document.createElement('iframe')
			frame.id = PRINT_FRAME_ID
			// The frame is parked off-screen rather than hidden, see
			// `PRINT_STAGING_STYLE`, so it stays in the accessibility tree and
			// would be announced without this.
			frame.setAttribute('aria-hidden', 'true')
			// The messages are copied in from the sanitized message frames, but
			// unlike those this frame is not protected by the backend's
			// Content-Security-Policy. The sandbox stands in for it: without
			// `allow-scripts` no script or inline event handler in the content can
			// run. The two capabilities that are granted are the ones this frame
			// needs — `allow-same-origin` to let us populate it, `allow-modals` to
			// let it open the print dialog.
			frame.setAttribute('sandbox', 'allow-same-origin allow-modals')
			frame.style.cssText = `${PRINT_STAGING_STYLE} height: ${PRINT_CONTENT_HEIGHT}; border: 0;`
			document.body.appendChild(frame)

			let cleanupTimeout = null
			let cleanedUp = false
			const cleanup = () => {
				if (cleanedUp) {
					return
				}
				cleanedUp = true
				clearTimeout(cleanupTimeout)
				this.isolatedPrint = false
				frame.remove()
			}

			try {
				const doc = frame.contentDocument
				doc.open()
				doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>')
				doc.close()
				doc.title = this.threadSubject

				const style = doc.createElement('style')
				style.textContent = PRINT_DOCUMENT_STYLE
				doc.head.appendChild(style)

				indices.forEach((index) => this.appendPrintMessage(doc.body, index))

				if (!await waitForImages(doc)) {
					logger.warn('Printing without the images that did not load in time')
				}

				frame.contentWindow.addEventListener('afterprint', cleanup, { once: true })
				// A backstop for a browser that never delivers `afterprint`, armed
				// only now: it must not be able to take the frame away while the
				// print is still being prepared. Long enough not to interrupt a
				// print dialog that is still open.
				cleanupTimeout = setTimeout(cleanup, PRINT_CLEANUP_TIMEOUT)

				// Firefox prints whatever window has the focus, and would print the
				// app around the frame without this.
				frame.contentWindow.focus()
				// Blocking in every browser that matters, but `afterprint` is what
				// the cleanup waits for: the spec allows `print()` to return before
				// the dialog is dismissed, and removing the frame while its
				// document is still being printed empties the print.
				frame.contentWindow.print()
			} catch (error) {
				cleanup()
				logger.error('Could not print message', { error })
				showError(t('mail', 'Could not print message'))
			}
		},

		/**
		 * Append the printable rendering of one thread message to `parent`: its
		 * print header followed by the message body as it is currently rendered.
		 *
		 * The body goes into a shadow root of its own, so that the messages of a
		 * thread cannot restyle one another while all of them still share — and
		 * paginate over — the pages of one print document. The header stays
		 * outside of it, out of reach of the message's own CSS.
		 *
		 * `parent` always belongs to the sandboxed print frame's document: the
		 * email markup is never copied into the app's own document.
		 *
		 * @param {HTMLElement} parent element to append the message to
		 * @param {number} index thread index of the message to append
		 */
		appendPrintMessage(parent, index) {
			const envelope = this.thread[index]
			// Looked up by envelope rather than by index: a `ref` in a `v-for`
			// collects the components as they render, in an order Vue does not
			// promise to be the one of the source array.
			const envelopeComponent = this.$refs.envelopeRefs?.find((component) => component.envelope?.databaseId === envelope?.databaseId)
			if (!envelope || !envelopeComponent) {
				return
			}

			const doc = parent.ownerDocument
			const messageEl = envelopeComponent.$el
			const message = doc.createElement('div')
			message.className = 'print-message'
			message.appendChild(buildMessageHeader(doc, envelope))
			parent.appendChild(message)

			const iframe = messageEl.querySelector('iframe')
			if (iframe?.contentDocument) {
				renderHtmlMessage(message, iframe.contentDocument)
				return
			}
			if (iframe) {
				// Mailvelope renders a decrypted PGP message into a frame of
				// its own, served from the extension. That document belongs to
				// another origin and cannot be read, let alone copied, so such
				// a message prints as its header alone rather than taking the
				// print of the whole thread down with it.
				logger.warn('Message body is in a frame of another origin and cannot be printed', {
					databaseId: envelope.databaseId,
				})
				return
			}

			const messageContainer = messageEl.querySelector('#message-container')
			if (messageContainer) {
				renderPlainTextMessage(message, messageContainer)
			}
		},
	},
}
</script>

<style lang="scss">
@use '../styles/variables.scss';

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
	// so we have to manually set left/right padding to children
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
</style>
