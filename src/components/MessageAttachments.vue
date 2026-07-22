<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="attachments.length > 0" class="mail-message-attachments" :class="hasNextLine ? 'has-next-line' : ''">
		<div class="mail-message-attachments--wrapper" :class="(hasNextLine === true && isToggled === true) ? 'hide' : ''">
			<div class="attachments">
				<MessageAttachment
					v-for="(attachment, idx) in attachments"
					:id="attachment.id"
					ref="attachments"
					:key="attachment.id"
					:file-name="attachment.fileName"
					:size="attachment.size"
					:url="attachment.downloadUrl"
					:is-image="attachment.isImage"
					:is-calendar-event="attachment.isCalendarEvent"
					:mime="attachment.mime"
					:mime-url="attachment.mimeUrl"
					:can-preview="canPreview(fileInfos[idx])"
					@open="showViewer(fileInfos[idx])" />
			</div>
		</div>

		<NcButton
			v-if="hasNextLine"
			variant="tertiary"
			size="small"
			:disabled="savingToCloud"
			class="attachment-button"
			@click="isToggled = !isToggled">
			<template #icon>
				<ChevronDown v-if="isToggled" />
				<ChevronUp v-if="!isToggled" />
			</template>
			<span v-if="isToggled">
				{{ n('mail', 'View {count} more attachment', 'View {count} more attachments', (attachments.length - visible), { count: attachments.length - visible }) }}
			</span>
			<span v-else>
				{{ t('mail', 'View fewer attachments') }}
			</span>
		</NcButton>

		<p v-if="moreThanOne" class="attachments-button-wrapper">
			<FilePicker
				v-if="isFilePickerOpen"
				:name="t('mail', 'Choose a folder to store the attachments in')"
				:buttons="saveAttachementButtons"
				:allow-pick-directory="true"
				:multiselect="false"
				:mimetype-filter="['httpd/unix-directory']"
				@close="() => isFilePickerOpen = false" />

			<NcButton
				variant="tertiary"
				size="small"
				:disabled="savingToCloud"
				class="attachment-button"
				@click="() => isFilePickerOpen = true">
				<template #icon>
					<CloudDownload v-if="!savingToCloud" />
					<IconLoading v-else class="spin" />
				</template>
				{{ t('mail', 'Save all to Files') }}
			</NcButton>

			<NcButton
				variant="tertiary"
				size="small"
				class="attachment-button"
				@click="downloadZip">
				<template #icon>
					<Download />
				</template>
				{{ t('mail', 'Download Zip') }}
			</NcButton>
		</p>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { FilePickerVue as FilePicker } from '@nextcloud/dialogs/filepicker.js'
import { generateUrl } from '@nextcloud/router'
import { NcLoadingIcon as IconLoading, NcButton } from '@nextcloud/vue'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronUp from 'vue-material-design-icons/ChevronUp.vue'
import CloudDownload from 'vue-material-design-icons/CloudDownloadOutline.vue'
import Download from 'vue-material-design-icons/TrayArrowDown.vue'
import MessageAttachment from './MessageAttachment.vue'
import Logger from '../logger.js'
import AttachmentMixin from '../mixins/AttachmentMixin.js'
import { saveAttachmentsToFiles } from '../service/AttachmentService.js'

export default {
	name: 'MessageAttachments',
	components: {
		NcButton,
		MessageAttachment,
		IconLoading,
		Download,
		CloudDownload,
		ChevronDown,
		ChevronUp,
		FilePicker,
	},

	mixins: [AttachmentMixin],
	props: {
		envelope: {
			required: true,
			type: Object,
		},

		attachments: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			visible: 0,
			savingToCloud: false,
			showPreview: false,
			attachmentImageURL: '',
			hasNextLine: false,
			isToggled: false,
			saveAttachementButtons: [
				{
					label: t('mail', 'Choose'),
					callback: this.saveAll,
					type: 'primary',
				},
			],

			isFilePickerOpen: false,
		}
	},

	computed: {

		moreThanOne() {
			return this.attachments.length > 1
		},

		zipUrl() {
			return generateUrl('/apps/mail/api/messages/{id}/attachments', {
				id: this.envelope.databaseId,
			})
		},
	},

	mounted() {
		let prevTop = null
		this.visible = 0
		this.$nextTick(function() {
			if (this.$refs.attachments) {
				this.$refs.attachments.some((attachment, i) => {
					const top = attachment.$el.getBoundingClientRect().top
					if (prevTop !== null && prevTop !== top) {
						this.isToggled = true
						this.hasNextLine = true
						return true
					} else {
						prevTop = top
						this.visible++
					}
					return false
				})
			}
		})
	},

	methods: {

		saveAll(dest) {
			const path = dest[0].path
			this.savingToCloud = true
			const id = this.$route.params.threadId

			saveAttachmentsToFiles(id, path).then(() => {
				Logger.info('saved')
				showSuccess(t('mail', 'Attachments saved to Files'))
			}).catch((error) => {
				Logger.error('not saved', error)
				showError(t('mail', 'Error while saving attachments'))
			}).finally(() => {
				this.savingToCloud = false
			})
		},

		downloadZip() {
			window.location = this.zipUrl
		},
	},
}
</script>

<style lang="scss" scoped>
@use '../../css/variables.scss';

.attachments {
	width: 100%;
    box-sizing: border-box;
    position: relative;
    display: flex;
    flex-wrap: wrap;
    margin: 10px 0;
}

/* show icon + text for Download all button
		as well as when there is only one attachment */
.attachments-button-wrapper {
	text-align: center;
	display: flex;
	align-items: center;
}

.attachment-button {
	color: var(--color-text-lighter);
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  50% {
    transform: rotate(180deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.spin {
  animation: spin 1s linear infinite;
}

.oc-dialog {
	z-index: 10000000;
}

.mail-message-attachments {
	display:flex;
	flex-wrap: wrap;
	padding: 10px 12px 10px 46px;
	margin-top: 4px;
	margin-bottom: 0;
	position: sticky;
	bottom: -2px;
	background: var(--color-main-background);

	@media (max-width: #{variables.$breakpoint-mobile}) {
        padding: 10px 12px 10px 12px;
		flex-direction: column;
    }
}

.mail-message-attachments--wrapper {
	display:flex;
	width:100%;
	height:auto;
	overflow-y: auto;
	overflow-x: hidden;
	max-height: 60vh;
}

.mail-message-attachments--wrapper.hide {
	display:flex;
	overflow: clip;
	max-height: 70px;
}
</style>
