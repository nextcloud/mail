<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="new-message-attachments">
		<div v-if="hasNextLine"
			class="new-message-attachments--counter"
			:class="{ 'new-message-attachments--counter--with-errors': hasAttachmentErrors }"
			@click="isToggle = !isToggle">
			<span>
				{{ n('mail', '{count} attachment', '{count} attachments', attachments.length, { count: attachments.length }) }} ({{ formatBytes(totalSizeOfUpload()) }})
			</span>
			<ChevronUp v-if="isToggle" :size="20" />
			<ChevronDown v-if="!isToggle" :size="20" />
		</div>
		<ul class="new-message-attachments--list"
			:class="{
				hide: isToggle,
				active: !isToggle && hasNextLine,
			}">
			<ComposerAttachment v-for="attachment in attachments"
				ref="attachments"
				:key="attachment.id"
				:attachment="attachment"
				:uploading="uploading"
				@on-delete-attachment="onDelete(attachment)" />
		</ul>

		<input ref="localAttachments"
			type="file"
			multiple
			style="display: none;"
			@change="onLocalAttachmentSelected">
		<FilePicker v-if="isAttachementPickerOpen"
			:name="t('mail','Choose a file to add as attachment')"
			:buttons="attachementPickerButtons"
			:filter-fn="filterAttachements"
			@close="()=>isAttachementPickerOpen = false" />
		<FilePicker v-if="isLinkPickerOpen"
			:name="t('mail','Choose a file to share as a link')"
			:multiselect="false"
			:buttons="linkPickerButtons"
			:filter-fn="filterAttachements"
			@close="()=>isLinkPickerOpen = false" />
	</div>
</template>

<script>
import map from 'lodash/fp/map.js'
import trimStart from 'lodash/fp/trimCharsStart.js'
import { getRequestToken } from '@nextcloud/auth'
import { formatFileSize } from '@nextcloud/files'
import prop from 'lodash/fp/prop.js'
import { showWarning } from '@nextcloud/dialogs'
import { FilePickerVue as FilePicker } from '@nextcloud/dialogs/filepicker.js'
import sumBy from 'lodash/fp/sumBy.js'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import Vue from 'vue'

import logger from '../logger.js'
import { getFileData } from '../service/FileService.js'
import { shareFile } from '../service/FileSharingService.js'
import { uploadLocalAttachment } from '../service/AttachmentService.js'

import ComposerAttachment from './ComposerAttachment.vue'

import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronUp from 'vue-material-design-icons/ChevronUp.vue'

const mimes = [
	'image/gif',
	'image/jpeg',
	'image/pjpeg',
	'image/png',
	'image/webp',
]

export default {
	name: 'ComposerAttachments',
	components: {
		FilePicker,
		ComposerAttachment,
		ChevronDown,
		ChevronUp,
	},
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
			// this need if we want to pass in value only corrected uploaded files
			attachments: [],
			isToggle: false,
			hasNextLine: false,
			isAttachementPickerOpen: false,
			isLinkPickerOpen: false,
			attachementPickerButtons: [
				{
					label: t('mail', 'Choose'),
					callback: this.onAddCloudAttachment,
					type: 'primary',
				},
			],
			linkPickerButtons: [
				{
					label: t('mail', 'Choose'),
					callback: this.onAddCloudAttachmentLink,
					type: 'primary',
				},
			],

		}
	},
	computed: {
		hasAttachmentErrors() {
			return this.attachments.some(attachment => attachment.error)
		},
		uploadProgress() {
			let uploaded = 0
			let total = 0
			for (const id in this.uploads) {
				uploaded += this.uploads[id].uploaded
				total += this.uploads[id].total
			}
			return ((uploaded / total) * 100).toFixed(1)
		},
		total() {
			let total = 0
			for (const id in this.uploads) {
				total += this.uploads[id].total
			}
			return total
		},
	},
	watch: {
		attachments() {
			this.$nextTick(function() {
				let prevTop = null
				this.$refs.attachments.some((attachment, i) => {
					const top = attachment.$el.getBoundingClientRect().top
					if (prevTop !== null && prevTop !== top) {
						if (!this.hasNextLine) {
							this.isToggle = true
							this.hasNextLine = true
						}
						return true
					} else {
						prevTop = top
						if (this.$refs.attachments.length === i + 1) {
							this.hasNextLine = false
							this.isToggle = false
							return true
						}
					}
					return false
				})
			})
		},
	},
	created() {
		this.bus.on('on-add-local-attachment', this.onAddLocalAttachment)
		this.bus.on('on-add-cloud-attachment', this.openAttachementPicker)
		this.bus.on('on-add-cloud-attachment-link', this.OpenLinkPicker)
		this.bus.on('on-add-message-as-attachment', this.onAddMessageAsAttachment)
		this.value.map(attachment => {
			this.attachments.push({
				id: attachment.id,
				fileName: attachment.fileName,
				displayName: trimStart('/', attachment.fileName),
				total: attachment.size,
				finished: true,
				sizeString: this.formatBytes(attachment.size),
				imageBlobURL: attachment.isImage ? attachment.downloadUrl : attachment.mimeUrl,
			})
			return attachment
		})
	},
	methods: {
		filterAttachements(node) {
			const downloadShareAttribute = node.attributes['share-attributes'] ? JSON.parse(node.attributes['share-attributes'])?.find((shareAttribute) => shareAttribute.key === 'download') : undefined
			const downloadPermissions = downloadShareAttribute !== undefined ? downloadShareAttribute.value : true
			return (node.permissions & OC.PERMISSION_READ) && downloadPermissions
		},
		openAttachementPicker() {
			this.isAttachementPickerOpen = true
		},
		OpenLinkPicker() {
			this.isLinkPickerOpen = true
		},
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
			// BUG - if choose again - progress lost/ move to complete()
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
				this.attachments.map((item, i) => {
					if (item.displayName === id) {
						this.attachments[i].progress = uploaded
						this.changeProgress(item, uploaded)
					}
					return item
				})

			}
			// TODO bug: cancel axios on close or delete attachment
			const promises = map((file) => {
				const controller = new AbortController()
				const attachment = {
					fileName: file.name,
					fileType: file.type,
					imageBlobURL: this.generatePreview(file),
					displayName: trimStart('/', file.name),
					progress: null,
					percent: 0,
					total: file.size,
					finished: false,
					error: false,
					hasPreview: false,
					controller,
				}
				this.attachments.push(attachment)

				Vue.set(this.uploads, file.name, {
					total: file.size,
					uploaded: 0,
				})
				try {
					return uploadLocalAttachment(file, progress(file.name), controller)
						.catch(() => {
							this.attachments.some(attachment => {
								if (attachment.displayName === file.name && !attachment.error) {
									this.$set(attachment, 'error', true)
									return true
								}
								return false
							})
						})
						.then(({ file, id }) => {
							logger.info('local attachment uploaded', { file, id })

							attachment.id = id

							this.emitNewAttachments([{
								fileName: file.name,
								displayName: trimStart('/', file.name),
								id,
								size: file.size,
								type: 'local',
							}])
						})
				} catch (error) {
					logger.error('Could not upload file', { file, error })
				}
			}, e.target.files)

			const done = Promise.all(promises)
				.catch((error) => logger.error('could not upload all attachments', { error }))
				.then(() => (this.uploading = false))

			this.$emit('upload', done)

			return done
		},
		async onAddCloudAttachment(nodes) {
			try {
				const paths = nodes.map(node => node.path)
				this.cloudAttachement = false
				// maybe fiiled front with placeholder loader...?
				const filesFromCloud = await Promise.all(paths.map(getFileData))

				const sum = filesFromCloud.reduce((sum, item) => {
					return sum + item.size
				}, 0)

				const newTotal = sum + this.totalSizeOfUpload()

				if (this.uploadSizeLimit && newTotal > this.uploadSizeLimit) {
					this.showAttachmentFileSizeWarning(paths.length)

					return
				}

				this.emitNewAttachments(paths.map((name, i) => {
					const _cloudFile = {
						fileName: name,
						displayName: trimStart('/', name),
						type: 'cloud',
						size: filesFromCloud[i].size,

					}
					const _toAttachmentData = {
						finished: true,
						imageBlobURL: this.generatePreview(_cloudFile),
						total: filesFromCloud[i].size,
						sizeString: this.formatBytes(filesFromCloud[i].size),
						hasPreview: filesFromCloud[i]['has-preview'],
						// dont know, may be it will be conflict if cloud & local has equal IDs?
						id: filesFromCloud[i].fileid,
						uploaded: 0,
					}

					this.attachments.push(Object.assign(_toAttachmentData, _cloudFile))

					return _cloudFile
				}))
			} catch (error) {
				logger.error('could not choose a file as attachment', { error })
			}
		},
		async onAddCloudAttachmentLink(nodes) {
			try {
				this.cloudAttachementLink = false
				const url = await shareFile(nodes[0].path, getRequestToken())

				this.appendToBodyAtCursor(`<a href="${url}">${url}</a>`)
			} catch (error) {
				logger.error('could not choose a file as attachment link', { error })
			}
		},
		/**
		 * Add a forwarded message as an attachment
		 *
		 * @param {object} data Payload
		 * @param {number} data.id Database id of the message to forward as an attachment
		 * @param {string} data.fileName File name of the attachment
		 */
		onAddMessageAsAttachment({ id, fileName }) {
			const attachment = {
				type: 'message',
				id,
				fileName,
			}
			this.attachments.push({
				...attachment,
				finished: true,
			})
			this.emitNewAttachments([attachment])
		},
		showAttachmentFileSizeWarning(num) {
			showWarning(n(
				'mail',
				'The attachment exceed the allowed attachments size of {size}. Please share the file via link instead.',
				'The attachments exceed the allowed attachments size of {size}. Please share the files via link instead.',
				num,
				{
					size: formatFileSize(this.uploadSizeLimit),
				},
			))
		},
		onDelete(attachment) {
			// If the attachment is still uploading, abort the upload
			if (!attachment.finished && attachment.controller) {
				attachment.controller.abort()
			}

			const val = {
				fileName: attachment.fileName,
				displayName: attachment.displayName,
				id: attachment.id,
				size: attachment.total,
				type: attachment.type,
			}

			this.attachments = this.attachments.filter(a => a !== attachment)

			this.$emit(
				'input',
				this.value.filter(a => {
					if (val.type === 'cloud') {
						return a.fileName !== val.fileName
					} else {
						return a.id !== val.id
					}
				}),
			)

			const updatedUploads = Object.keys(this.uploads)
				.filter(fileName => fileName !== attachment.fileName)
				.reduce((acc, fileName) => {
					acc[fileName] = this.uploads[fileName]
					return acc
				}, {})

			this.uploads = updatedUploads

			this.$emit('on-delete-attachment', attachment)
		},
		appendToBodyAtCursor(toAppend) {
			this.bus.emit('append-to-body-at-cursor', toAppend)
		},
		formatBytes(bytes, decimals = 2) {
			if (bytes === 0) return '0 B'
			const k = 1024
			const dm = decimals < 0 ? 0 : decimals
			const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
			const i = Math.floor(Math.log(bytes) / Math.log(k))
			return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
		},
		changeProgress(item, progress) {
			this.attachments.map((attachment, i) => {
				if (item.fileName === attachment.fileName) {
					if (!attachment.finished) {
						const _progress = progress <= attachment.total ? progress : attachment.total
						this.$set(attachment, 'progress', _progress)
						this.$set(attachment, 'sizeString', this.formatBytes(_progress))
						this.$set(attachment, 'percent', (_progress / attachment.total) * 100).toFixed(1)
						if (item.total <= _progress) {
							this.$set(attachment, 'finished', true)
						}
					}
				}
				return attachment
			})
		},
		generatePreview(file) {
			if (this.isImage(file)) {
				return URL.createObjectURL(file)
			} else {
				return false
			}
		},
		isImage(file) {
			return file.type && mimes.indexOf(file.type) !== -1
		},
	},
}
</script>

<style scoped lang="scss">

.new-message-attachments {
	&--counter {
		color: var(--color-text-maxcontrast);
		padding: 10px 20px;
		cursor:pointer;
		display:flex;
		align-items: center;

		&--with-errors {
			color:red;
		}
	}

	&--list {
		display: flex;
		flex-wrap: wrap;
		// 2 and a half attachment height
		overflow: auto;
		transition: max-height 0.5s cubic-bezier(0, 1, 0, 1);
		padding: 0 10px;

		&.hide {
			overflow: hidden;
			max-height:0;
			transition: max-height 0.5s cubic-bezier(0, 1, 0, 1);
		}

		&.active {
			overflow: auto;
			max-height: 287px;
		}
	}
}
</style>
