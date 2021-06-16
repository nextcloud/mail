<template>
	<div id="mail-content">
		<MdnRequest :message="message" />
		<div v-if="hasBlockedContent" id="mail-message-has-blocked-content" style="color: #000000">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<Actions default-icon="icon-toggle" :menu-title="t('mail', 'Show')">
				<ActionButton icon="icon-toggle"
					@click="displayIframe">
					{{ t('mail', 'Show images temporarily') }}
				</ActionButton>
				<ActionButton icon="icon-toggle"
					@click="onShowBlockedContent">
					{{ t('mail', 'Always show images from {sender}', {sender: message.from[0].email}) }}
				</ActionButton>
				<ActionButton icon="icon-toggle"
					@click="onShowBlockedContentForDomain">
					{{ t('mail', 'Always show images from {domain}', {domain: getDomain()}) }}
				</ActionButton>
			</Actions>
		</div>
		<div v-if="loading" class="icon-loading" />
		<div id="message-container" :class="{hidden: loading, scroll: !fullHeight}">
			<iframe ref="iframe"
				class="message-frame"
				:title="t('mail', 'Message frame')"
				:src="url"
				seamless
				@load="onMessageFrameLoad" />
		</div>
	</div>
</template>

<script>
import { iframeResizer } from 'iframe-resizer'
import PrintScout from 'printscout'
import { trustSender } from '../service/TrustedSenderService'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Actions from '@nextcloud/vue/dist/Components/Actions'

import logger from '../logger'
import MdnRequest from './MdnRequest'
const scout = new PrintScout()

export default {
	name: 'MessageHTMLBody',
	components: {
		MdnRequest,
		Actions,
		ActionButton,
	},
	props: {
		url: {
			type: String,
			required: true,
		},
		fullHeight: {
			type: Boolean,
			required: false,
			default: false,
		},
		message: {
			required: true,
			type: Object,
		},
	},
	data() {
		return {
			loading: true,
			hasBlockedContent: false,
			isSenderTrusted: this.message.isSenderTrusted,
		}
	},
	beforeMount() {
		scout.on('beforeprint', this.onBeforePrint)
		scout.on('afterprint', this.onAfterPrint)
	},
	mounted() {
		iframeResizer({}, this.$refs.iframe)
	},
	beforeDestroy() {
		scout.off('beforeprint', this.onBeforePrint)
		scout.off('afterprint', this.onAfterPrint)
		this.$refs.iframe.iFrameResizer.close()
	},
	methods: {
		getIframeDoc() {
			const iframe = this.$refs.iframe
			return iframe.contentDocument || iframe.contentWindow.document
		},
		onMessageFrameLoad() {
			const iframeDoc = this.getIframeDoc()
			this.hasBlockedContent
				= iframeDoc.querySelectorAll('[data-original-src]').length > 0
				|| iframeDoc.querySelectorAll('[data-original-style]').length > 0
				|| iframeDoc.querySelectorAll('style[data-original-content]').length > 0

			this.loading = false
			if (this.isSenderTrusted) {
				this.displayIframe()
			}
		},
		onAfterPrint() {
			this.$refs.iframe.style.setProperty('height', '')
		},
		onBeforePrint() {
			this.$refs.iframe.style.setProperty('height', `${this.getIframeDoc().body.scrollHeight}px`, 'important')
		},
		displayIframe() {
			const iframeDoc = this.getIframeDoc()
			logger.debug('showing external images')
			iframeDoc.querySelectorAll('[data-original-src]').forEach((node) => {
				node.style.display = null
				node.setAttribute('src', node.getAttribute('data-original-src'))
			})
			iframeDoc
				.querySelectorAll('[data-original-style]')
				.forEach((node) => node.setAttribute('style', node.getAttribute('data-original-style')))
			iframeDoc
				.querySelectorAll('style[data-original-content]')
				.forEach((node) => {
					node.innerHTML = node.getAttribute('data-original-content')
				})
			this.hasBlockedContent = false
		},
		async onShowBlockedContent() {
			this.displayIframe()
			await trustSender(this.message.from[0].email, 'individual', true)
		},
		getDomain() {
			return this.message.from[0].email.split('@').pop()
		},
		async onShowBlockedContentForDomain() {
			this.displayIframe()
			// TODO: there might be more than one @ in an email address
			await trustSender(this.getDomain(), 'domain', true)
		},
	},
}
</script>

<style lang="scss" scoped>
// account for 8px margin on iframe body
#mail-content {
	margin-left: 48px;
	margin-top: 2px;
	display: flex;
	flex-direction: column;
	height: 100%;
	background-color: #FFFFFF;
}
#mail-message-has-blocked-content {
	margin-left: 8px;
}

#message-container {
	flex: 1;
	display: flex;
	background-color: #FFFFFF;

	// TODO: collapse quoted text and remove inner scrollbar
	&.scroll {
		max-height: 50vh;
		overflow-y: auto;
	}
}
::v-deep .icon-toggle {
	background-image: var(--icon-toggle-000) !important;
}
::v-deep .action-item__menutoggle--with-title {
	background-color: var(--color-background-hover) !important;
}
.message-frame {
	width: 100%;
}
</style>
