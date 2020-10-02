<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @license GNU AGPL version 3 or any later version
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
				:mime-url="attachment.mimeUrl" />
		</div>
		<p v-if="moreThanOne" class="attachments-button-wrapper">
			<button
				class="attachments-save-to-cloud"
				:class="{'icon-folder': !savingToCloud, 'icon-loading-small': savingToCloud}"
				:disabled="savingToCloud"
				@click="saveAll">
				{{ t('mail', 'Save all to Files') }}
			</button>
		</p>
	</div>
</template>

<script>
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { saveAttachmentsToFiles } from '../service/AttachmentService'

import MessageAttachment from './MessageAttachment'
import Logger from '../logger'

export default {
	name: 'MessageAttachments',
	components: {
		MessageAttachment,
	},
	props: {
		attachments: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			savingToCloud: false,
		}
	},
	computed: {
		moreThanOne() {
			return this.attachments.length > 1
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
	},
}
</script>

<style lang="scss">
.attachments {
	width: 300px;
}

/* show icon + text for Download all button
		as well as when there is only one attachment */
.attachments-button-wrapper {
	text-align: center;
}
.attachments-save-to-cloud {
	display: inline-block;
	margin: 16px;
	background-position: 16px center;
	padding: 12px;
	padding-left: 44px;
}
.oc-dialog {
	z-index: 10000000;
}
</style>
