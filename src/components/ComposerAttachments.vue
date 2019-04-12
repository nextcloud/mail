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
	<div class="new-message-attachments">
		<ul>
			<li v-for="attachment in value" :key="attachment.id">
				<div class="new-message-attachment-name">
					{{ attachment.displayName }}
				</div>
				<div class="new-message-attachments-action svg icon-delete" @click="onDelete(attachment)"></div>
			</li>
		</ul>
		<button class="button" :disabled="uploading" @click="onAddLocalAttachment">
			<span :class="{'icon-upload': !uploading, 'icon-loading-small': uploading}"></span>
			{{
				uploading
					? t('mail', 'Uploading {percent}% …', {percent: uploadProgress})
					: t('mail', 'Upload attachment')
			}}
		</button>
		<button class="button" @click="onAddCloudAttachment">
			<span class="icon-folder" />
			{{ t('mail', 'Add attachment from Files') }}
		</button>
		<input ref="localAttachments" type="file" multiple style="display: none;" @change="onLocalAttachmentSelected" />
	</div>
</template>

<script>
import _ from 'lodash'
import {translate as t} from 'nextcloud-server/dist/l10n'
import {pickFileOrDirectory} from 'nextcloud-server/dist/files'
import Vue from 'vue'

import {uploadLocalAttachment} from '../service/AttachmentService'

export default {
	name: 'ComposerAttachments',
	props: {
		value: {
			type: Array,
			required: true,
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
			for (let id in this.uploads) {
				uploaded += this.uploads[id].uploaded
				total += this.uploads[id].total
			}
			return ((uploaded / total) * 100).toFixed(1)
		},
	},
	methods: {
		onAddLocalAttachment() {
			this.$refs.localAttachments.click()
		},
		fileNameToAttachment(name, id) {
			return {
				fileName: name,
				displayName: _.trimStart(name, '/'),
				id,
				isLocal: !_.isUndefined(id),
			}
		},
		emitNewAttachment(attachment) {
			this.$emit('input', this.value.concat([attachment]))
		},
		onLocalAttachmentSelected(e) {
			this.uploading = true

			Vue.set(this, 'uploads', {})

			const progress = id => (prog, uploaded) => {
				this.uploads[id].uploaded = uploaded
			}

			const promises = _.map(e.target.files, file => {
				Vue.set(this.uploads, file.name, {
					total: file.size,
					uploaded: 0,
				})

				return uploadLocalAttachment(file, progress(file.name)).then(({file, id}) => {
					console.info('uploaded')
					return this.emitNewAttachment(this.fileNameToAttachment(file.name, id))
				})
			})

			const done = Promise.all(promises)
				.catch(console.error.bind(this))
				.then(() => (this.uploading = false))

			this.$emit('upload', done)

			return done
		},
		onAddCloudAttachment() {
			return pickFileOrDirectory(t('mail', 'Choose a file to add as attachment'))
				.then(path => this.emitNewAttachment(this.fileNameToAttachment(path)))
				.catch(console.error.bind(this))
		},
		onDelete(attachment) {
			this.$emit('input', this.value.filter(a => a !== attachment))
		},
	},
}
</script>

<style scoped>
button {
	/* TODO: remove for Nextcloud 15+ */
	/* https://github.com/nextcloud/server/pull/12138 */
	display: inline-block;
}

.new-message-attachments li {
	padding: 10px;
}

.new-message-attachments-action {
	display: inline-block;
	vertical-align: middle;
	padding: 22px;
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
</style>
