<!--
  - @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @copyright 2020 Gary Kim <gary@garykim.dev>
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
	<div class="new-message-attachments">
		<ul>
			<li v-for="attachment in value" :key="attachment.id">
				<div class="new-message-attachment-name">
					{{ attachment.displayName }}
				</div>
				<div class="new-message-attachments-action svg icon-delete" @click="onDelete(attachment)" />
			</li>
			<li v-if="uploading" class="attachments-upload-progress">
				<div :class="{'icon-loading-small': uploading}" />
				<div>{{ uploading ? t('mail', 'Uploading {percent}% …', {percent: uploadProgress}) : '' }}</div>
			</li>
		</ul>

		<input ref="localAttachments"
			type="file"
			multiple
			style="display: none;"
			@change="onLocalAttachmentSelected">
	</div>
</template>

<script>
import map from 'lodash/fp/map'
import trimStart from 'lodash/fp/trimCharsStart'
import { getRequestToken } from '@nextcloud/auth'
import { formatFileSize } from '@nextcloud/files'
import prop from 'lodash/fp/prop'
import { getFilePickerBuilder, showWarning } from '@nextcloud/dialogs'
import sum from 'lodash/fp/sum'
import sumBy from 'lodash/fp/sumBy'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import Vue from 'vue'

import logger from '../logger'
import { getFileSize } from '../service/FileService'
import { shareFile } from '../service/FileSharingService'
import { uploadLocalAttachment } from '../service/AttachmentService'

export default {
	name: 'ComposerAttachments',
	props: {
		value: {
			type: Array,
			required: true,
		},
		bus: {
			type: Object,
			required: true,
		},
		uploadSizeLimit: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			uploading: false,
			uploads: {},
		}
	},
	computed: {
		uploadProgress() {
			let uploaded = 0
			let total = 0
			for (const id in this.uploads) {
				uploaded += this.uploads[id].uploaded
				total += this.uploads[id].total
			}
			return ((uploaded / total) * 100).toFixed(1)
		},
	},
	created() {
		this.bus.$on('onAddLocalAttachment', this.onAddLocalAttachment)
		this.bus.$on('onAddCloudAttachment', this.onAddCloudAttachment)
		this.bus.$on('onAddCloudAttachmentLink', this.onAddCloudAttachmentLink)
	},
	methods: {
		onAddLocalAttachment() {
			this.$refs.localAttachments.click()
		},
		emitNewAttachments(attachments) {
			this.$emit('input', this.value.concat(attachments))
		},
		totalSizeOfUpload() {
			return Object.values(this.value).reduce((acc, upload) => {
				if (!upload.type === 'local') {
					// Ignore link shares
					return acc
				}

				return acc + upload.size
			}, 0)
		},
		onLocalAttachmentSelected(e) {
			this.uploading = true

			Vue.set(this, 'uploads', {})

			const toUpload = sumBy(prop('size'), Object.values(e.target.files))
			const newTotal = toUpload + this.totalSizeOfUpload()
			logger.debug('checking upload size limit', {
				existingUploads: this.totalSizeOfUpload(),
				toUpload,
				limit: this.uploadSizeLimit,
				newTotal,
			})
			if (this.uploadSizeLimit && newTotal > this.uploadSizeLimit) {
				this.showAttachmentFileSizeWarning(e.target.files.length)

				this.uploading = false
				return
			}

			const progress = (id) => (prog, uploaded) => {
				this.uploads[id].uploaded = uploaded
			}

			const promises = map((file) => {
				Vue.set(this.uploads, file.name, {
					total: file.size,
					uploaded: 0,
				})

				return uploadLocalAttachment(file, progress(file.name)).then(({ file, id }) => {
					logger.info('uploaded')
					this.emitNewAttachments([{
						fileName: file.name,
						displayName: trimStart('/', file.name),
						id,
						size: file.size,
						type: 'local',
					}])
				})

			}, e.target.files)

			const done = Promise.all(promises)
				.catch((error) => logger.error('could not upload all attachments', { error }))
				.then(() => (this.uploading = false))

			this.$emit('upload', done)

			return done
		},
		async onAddCloudAttachment() {
			const picker = getFilePickerBuilder(t('mail', 'Choose a file to add as attachment')).setMultiSelect(true).build()

			try {
				const paths = await picker.pick(t('mail', 'Choose a file to add as attachment'))
				const fileSizes = await Promise.all(paths.map(getFileSize))
				const newTotal = sum(fileSizes) + this.totalSizeOfUpload()

				if (this.uploadSizeLimit && newTotal > this.uploadSizeLimit) {
					this.showAttachmentFileSizeWarning(paths.length)

					return
				}

				this.emitNewAttachments(paths.map(function(name) {
					return {
						fileName: name,
						displayName: trimStart('/', name),
						type: 'cloud',
					}
				}))
			} catch (error) {
				logger.error('could not choose a file as attachment', { error })
			}
		},
		async onAddCloudAttachmentLink() {
			const picker = getFilePickerBuilder(t('mail', 'Choose a file to share as a link')).build()

			try {
				const path = await picker.pick(t('mail', 'Choose a file to share as a link'))
				const url = await shareFile(path, getRequestToken())

				this.appendToBodyAtCursor(`<a href="${url}">${url}</a>`)
			} catch (error) {
				logger.error('could not choose a file as attachment link', { error })
			}
		},
		showAttachmentFileSizeWarning(num) {
			showWarning(n(
				'mail',
				'The attachment exceed the allowed attachments size of {size}. Please share the file via link instead.',
				'The attachments exceed the allowed attachments size of {size}. Please share the files via link instead.',
				num,
				{
					size: formatFileSize(this.uploadSizeLimit),
				}
			))
		},
		onDelete(attachment) {
			this.$emit(
				'input',
				this.value.filter((a) => a !== attachment)
			)
		},
		appendToBodyAtCursor(toAppend) {
			this.bus.$emit('appendToBodyAtCursor', toAppend)
		},
	},
}
</script>

<style scoped>
.new-message-attachments li {
	padding: 10px;
}

.new-message-attachments-action {
	display: inline-block;
	vertical-align: middle;
	padding: 10px;
	opacity: 0.5;
}

/* attachment filenames */
.new-message-attachment-name {
	display: inline-block;
}

/* Colour the filename with a different color during attachment upload */
.new-message-attachment-name.upload-ongoing {
	color: #0082c9;
}

/* Colour the filename in red if the attachment upload failed */
.new-message-attachment-name.upload-warning {
	color: #d2322d;
}

/* Red ProgressBar for failed attachment uploads */
.new-message-attachment-name.upload-warning .ui-progressbar-value {
	border: 1px solid #e9322d;
	background: #e9322d;
}

.attachments-upload-progress {
	display: flex;
}

.attachments-upload-progress > div {
	padding-left: 3px;
}
</style>
