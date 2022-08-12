<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="mail-message-attachments">
		<div class="attachments">
			<MessageAttachment
				v-for="attachment in attachments"
				:id="attachment.id"
				:key="attachment.id"
				:file-name="attachment.fileName"
				:size="attachment.size"
				:url="attachment.downloadUrl"
				:is-image="attachment.isImage"
				:is-calendar-event="attachment.isCalendarEvent"
				:mime="attachment.mime"
				:mime-url="attachment.mimeUrl"
				@click="showViewer(attachment)" />
			<AttachmentImageViewer v-if="attachmentImageURL && showPreview"
				:url="attachmentImageURL"
				@close="showPreview = false" />
		</div>
		<p v-if="moreThanOne" class="attachments-button-wrapper">
			<ButtonVue
				type="secondary"
				class="attachments-save-to-cloud"
				:disabled="savingToCloud"
				@click="saveAll">
				<template #icon>
					<IconLoading v-if="savingToCloud" :size="20" />
					<IconFolder v-else-if="!savingToCloud" :size="20" />
				</template>
				{{ t('mail', 'Save all to Files') }}
			</ButtonVue>
			<ButtonVue
				type="secondary"
				class="attachments-save-to-cloud"
				@click="downloadZip">
				<template #icon>
					<IconFolder :size="20" />
				</template>
				{{ t('mail', 'Download Zip') }}
			</ButtonVue>
		</p>
	</div>
</template>

<script>
import ButtonVue from '@nextcloud/vue/dist/Components/NcButton'
import IconLoading from '@nextcloud/vue/dist/Components/NcLoadingIcon'
import IconFolder from 'vue-material-design-icons/Folder'
import { generateUrl } from '@nextcloud/router'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { saveAttachmentsToFiles } from '../service/AttachmentService'

import MessageAttachment from './MessageAttachment'
import Logger from '../logger'
import AttachmentImageViewer from './AttachmentImageViewer'

export default {
	name: 'MessageAttachments',
	components: {
		AttachmentImageViewer,
		MessageAttachment,
		ButtonVue,
		IconLoading,
		IconFolder,
	},
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
			savingToCloud: false,
			showPreview: false,
			attachmentImageURL: '',
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
	methods: {
		saveAll() {
			const picker = getFilePickerBuilder(t('mail', 'Choose a folder to store the attachments in'))
				.setMultiSelect(false)
				.addMimeTypeFilter('httpd/unix-directory')
				.setModal(true)
				.setType(1)
				.allowDirectories(true)
				.build()

			const saveAttachments = (id) => (directory) => {
				return saveAttachmentsToFiles(id, directory)
			}
			const id = this.$route.params.threadId

			return picker
				.pick()
				.then((dest) => {
					this.savingToCloud = true
					return dest
				})
				.then(saveAttachments(id))
				.then(() => Logger.info('saved'))
				.catch((error) => Logger.error('not saved', { error }))
				.then(() => (this.savingToCloud = false))
		},
		downloadZip() {
			window.location = this.zipUrl
		},
		showViewer(attachment) {
			if (attachment.isImage) {
				this.showPreview = true
				this.attachmentImageURL = attachment.downloadUrl
			}
		},
	},
}
</script>

<style lang="scss">
.attachments {
	width: 230px;
	position: relative;
	display: flex;
}

/* show icon + text for Download all button
		as well as when there is only one attachment */
.attachments-button-wrapper {
	gap: 4px;
	display: flex;
	justify-content: center;
}
.oc-dialog {
	z-index: 10000000;
}
.mail-message-attachments {
	overflow-x: auto;
	overflow-y: auto;
}
</style>
