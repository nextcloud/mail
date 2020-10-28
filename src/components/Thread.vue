<template>
	<AppContentDetails id="mail-message">
		<Loading v-if="loading" />
		<template v-else>
			<div id="mail-thread-header">
				<div id="mail-thread-header-fields">
					<h2 :title="threadSubject">
						{{ threadSubject }}
					</h2>
					<div class="avatar-header">
						<RecipientBubble v-for="participant in threadParticipants"
							:key="participant.email"
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
				@move="onMove(env.databaseId)"
				@toggleExpand="toggleExpand(env.databaseId)" />
		</template>
	</AppContentDetails>
</template>

<script>
import AppContentDetails from '@nextcloud/vue/dist/Components/AppContentDetails'
import { prop, uniqBy } from 'ramda'

import { getRandomMessageErrorMessage } from '../util/ErrorMessageFactory'
import Loading from './Loading'
import logger from '../logger'
import RecipientBubble from './RecipientBubble'
import ThreadEnvelope from './ThreadEnvelope'

export default {
	name: 'Thread',
	components: {
		RecipientBubble,
		AppContentDetails,
		Loading,
		ThreadEnvelope,
	},

	data() {
		return {
			loading: true,
			message: undefined,
			errorMessage: '',
			error: undefined,
			expandedThreads: [],
		}
	},
	computed: {
		threadId() {
			return parseInt(this.$route.params.threadId, 10)
		},
		thread() {
			return this.$store.getters.getEnvelopeThread(this.threadId)
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
			return thread[0].subject
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
	},
	methods: {
		toggleExpand(threadId) {
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
			await this.fetchThread()
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
				if (error.isError) {
					this.errorMessage = t('mail', 'Could not load your message thread')
					this.error = error
					this.loading = false
				}
			}
		},
	},
}
</script>

<style lang="scss">
#mail-message {
	flex-grow: 1;
}

.mail-message-body {
	flex: 1;
	margin-bottom: 60px;
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
	top: var(--header-height);
	background: -webkit-linear-gradient(var(--color-main-background), var(--color-main-background) 80%, rgba(255,255,255,0));
	background: -o-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
	background: -moz-linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
	background: linear-gradient(var(--color-main-background), var(--color-main-background)  80%, rgba(255,255,255,0));
}

#mail-thread-header-fields {
	// initial width
	width: 0;
	padding-left: 60px;
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

#mail-content, .mail-signature {
	margin: 10px 38px 50px 60px;

	.mail-message-body-html & {
		margin-bottom: -44px; // accounting for the sticky attachment button
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
	border-bottom: 1px dotted #07d;
	text-decoration: none;
	word-wrap: break-word;
}

.icon-reply-white,
.icon-reply-all-white {
	height: 44px;
	min-width: 44px;
	margin: 0;
	padding: 9px 18px 10px 32px;
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

	#header,
	.app-navigation,
	#reply-composer,
	#forward-button,
	#mail-message-has-blocked-content,
	.app-content-list,
	.message-composer,
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
