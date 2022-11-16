<template>
	<div class="html-message-body">
		<MdnRequest :message="message" />
		<div v-if="hasBlockedContent" id="mail-message-has-blocked-content" style="color: #000000">
			{{ t('mail', 'The images have been blocked to protect your privacy.') }}
			<Actions type="tertiary" :menu-title="t('mail', 'Show images')">
				<ActionButton
					@click="displayIframe">
					<template #icon>
						<IconImage :size="20" />
					</template>
					{{ t('mail', 'Show images temporarily') }}
				</ActionButton>
				<ActionButton v-if="sender"
					@click="onShowBlockedContent">
					<template #icon>
						<IconMail :size="20" />
					</template>
					{{ t('mail', 'Always show images from {sender}', { sender }) }}
				</ActionButton>
				<ActionButton v-if="domain"
					@click="onShowBlockedContentForDomain">
					<template #icon>
						<IconDomain :size="20" />
					</template>
					{{ t('mail', 'Always show images from {domain}', { domain }) }}
				</ActionButton>
			</Actions>
		</div>
		<div id="message-container" :class="{scroll: !fullHeight}">
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
import { NcActionButton as ActionButton, NcActions as Actions } from '@nextcloud/vue'
import IconImage from 'vue-material-design-icons/ImageSizeSelectActual'
import IconMail from 'vue-material-design-icons/Email'
import IconDomain from 'vue-material-design-icons/Domain'

import logger from '../logger'
import MdnRequest from './MdnRequest'
const scout = new PrintScout()

export default {
	name: 'MessageHTMLBody',
	components: {
		MdnRequest,
		Actions,
		ActionButton,
		IconImage,
		IconMail,
		IconDomain,
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
			hasBlockedContent: false,
			isSenderTrusted: this.message.isSenderTrusted,
		}
	},
	computed: {
		sender() {
			return this.message.from[0]?.email
		},
		domain() {
			return this.sender?.split('@').pop()
		},
	},
	beforeMount() {
		scout.on('beforeprint', this.onBeforePrint)
	},
	mounted() {
		iframeResizer({ log: false, heightCalculationMethod: 'taggedElement' }, this.$refs.iframe)
	},
	beforeDestroy() {
		scout.off('beforeprint', this.onBeforePrint)
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

			this.$emit('load')
			if (this.isSenderTrusted) {
				this.displayIframe()
			}
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
		async onShowBlockedContentForDomain() {
			this.displayIframe()
			// TODO: there might be more than one @ in an email address
			await trustSender(this.domain, 'domain', true)
		},
	},
}
</script>

<style lang="scss" scoped>
// account for 8px margin on iframe body
.html-message-body {
	margin-left: 50px;
	margin-top: 2px;
	display: flex;
	flex-direction: column;
	height: 100%;
	background-color: #FFFFFF;
}
#mail-message-has-blocked-content {
	margin-left: 10px;
	color: var(--color-text-maxcontrast) !important;
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
:deep(.button-vue__text) {
	border: none !important;
	font-weight: normal !important;
	padding-left: 14px !important;
	padding-right: 10px !important;
	text-decoration: underline !important;
}
.message-frame {
	width: 100%;
}
:deep(.button-vue__icon) {
	display: none !important;
}
</style>
