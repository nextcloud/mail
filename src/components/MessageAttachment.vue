<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="attachment" :class="{'message-attachment--can-preview': canPreview }">
		<div class="mail-attachment-img--wrapper" @click="$emit('open', $event)">
			<img v-if="isImage"
				class="mail-attached-image"
				:src="url">
			<img v-else class="attachment-icon" :src="mimeUrl">
		</div>
		<div class="mail-attached--content" @click="$emit('open', $event)">
			<span class="attachment-name"
				:title="label">{{ name }}
			</span>
			<span class="attachment-size">{{ humanReadable(size) }}</span>
		</div>
		<FilePicker v-if="isFilePickerOpen"
			:name="t('mail', 'Choose a folder to store the attachment in')"
			:buttons="saveAttachementButtons"
			:allow-pick-directory="true"
			:multiselect="false"
			:mimetype-filter="['httpd/unix-directory']"
			@close="()=>isFilePickerOpen = false" />
		<Actions :boundaries-element="boundariesElement">
			<template v-if="!showCalendarPopover">
				<ActionButton v-if="isCalendarEvent"
					class="attachment-import calendar"
					:disabled="loadingCalendars"
					@click.stop="loadCalendars">
					<template #icon>
						<IconAdd v-if="!loadingCalendars" :size="20" />
						<IconLoading v-else-if="loadingCalendars" :size="20" />
					</template>
					{{ t('mail', 'Import into calendar') }}
				</ActionButton>
				<ActionButton class="attachment-download"
					@click="download">
					<template #icon>
						<IconDownload :size="20" />
					</template>
					{{ t('mail', 'Download attachment') }}
				</ActionButton>
				<ActionButton class="attachment-save-to-cloud"
					:disabled="savingToCloud"
					@click.stop="()=>isFilePickerOpen = true">
					<template #icon>
						<IconSave v-if="!savingToCloud" :size="20" />
						<IconLoading v-else-if="savingToCloud" :size="20" />
					</template>
					{{ t('mail', 'Save to Files') }}
				</ActionButton>
			</template>
			<template v-else>
				<ActionButton @click="closeCalendarPopover">
					<template #icon>
						<IconArrow :size="20" />
					</template>
					{{ t('mail', 'Go back') }}
				</ActionButton>
				<ActionButton v-for="entry in calendarMenuEntries"
					:key="entry.text"
					@click="entry.action">
					{{ entry.text }}
				</ActionButton>
			</template>
		</Actions>
	</div>
</template>

<script>

import { formatFileSize } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { FilePickerVue as FilePicker } from '@nextcloud/dialogs/filepicker.js'
import { mixin as onClickOutside } from 'vue-on-click-outside'

import { NcActions as Actions, NcActionButton as ActionButton, NcLoadingIcon as IconLoading } from '@nextcloud/vue'

import IconAdd from 'vue-material-design-icons/Plus.vue'
import IconArrow from 'vue-material-design-icons/ArrowLeft.vue'
import IconSave from 'vue-material-design-icons/FolderOutline.vue'
import IconDownload from 'vue-material-design-icons/TrayArrowDown.vue'
import Logger from '../logger.js'

import { downloadAttachment, saveAttachmentToFiles } from '../service/AttachmentService.js'
import { getUserCalendars, importCalendarEvent } from '../service/DAVService.js'

export default {
	name: 'MessageAttachment',
	components: {
		FilePicker,
		Actions,
		ActionButton,
		IconAdd,
		IconArrow,
		IconLoading,
		IconSave,
		IconDownload,
	},
	mixins: [onClickOutside],
	props: {
		id: {
			type: String,
			required: true,
		},
		fileName: {
			type: String,
			default: t('mail', 'Unnamed'),
			required: false,
		},
		url: {
			type: String,
			required: true,
		},
		size: {
			type: Number,
			required: true,
		},
		mime: {
			type: String,
			required: true,
		},
		mimeUrl: {
			type: String,
			required: true,
		},
		isImage: {
			type: Boolean,
			default: false,
		},
		isCalendarEvent: {
			type: Boolean,
			default: false,
		},
		canPreview: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			savingToCloud: false,
			loadingCalendars: false,
			calendars: [],
			showCalendarPopover: false,
			saveAttachementButtons: [
				{
					label: t('mail', 'Choose'),
					callback: this.saveToCloud,
					type: 'primary',
				},
			],
			isFilePickerOpen: false,
		}
	},
	computed: {
		name() {
			if (this.mime === 'message/rfc822') {
				return t('mail', 'Embedded message')
			}
			return this.fileName
		},
		label() {
			if (this.mime === 'message/rfc822') {
				return t('mail', 'Embedded message') + ' (' + formatFileSize(this.size) + ')'
			}
			return this.fileName + ' (' + formatFileSize(this.size) + ')'
		},
		calendarMenuEntries() {
			return this.calendars.map((cal) => {
				return {
					text: cal.displayname,
					action: this.importCalendar(cal.url),
				}
			})
		},
		boundariesElement() {
			return document.querySelector('#content-vue')
		},
	},
	methods: {
		humanReadable(size) {
			return formatFileSize(size)
		},
		async saveToCloud(dest) {
			const path = dest[0].path
			this.savingToCloud = true
			const id = this.$route.params.threadId

			try {
				await saveAttachmentToFiles(id, this.id, path)
				Logger.info('saved')
				showSuccess(t('mail', 'Attachment saved to Files'))
			} catch (e) {
				Logger.error('not saved', { error: e })
				showError(t('mail', 'Attachment could not be saved'))
			} finally {
				this.savingToCloud = false
			}
		},
		download() {
			window.location = this.url
		},
		loadCalendars() {
			this.loadingCalendars = true
			getUserCalendars().then((calendars) => {
				this.calendars = calendars
				this.showCalendarPopover = true
				this.loadingCalendars = false
			})
		},
		closeCalendarPopover() {
			this.showCalendarPopover = false
		},
		importCalendar(url) {
			return () => {
				downloadAttachment(this.url)
					.then(importCalendarEvent(url))
					.then(() => {
						showSuccess(t('mail', 'calendar imported'))
					})
					.catch((error) => {
						Logger.error('Could not import event', { error })
						showError(t('mail', 'Could not create event'))
					})
					.then(() => (this.showCalendarPopover = false))
			}
		},
	},
}
</script>

<style lang="scss" scoped>

@media screen and (max-width: 1024px) {
	.attachment{
		width: 100% !important;
	}
}

@media screen and (min-width: 1025px) and (max-width: 1500px) {
	.attachment{
		width: calc(50% - 4px)!important;
	}
}

.attachment {
	height: auto;
    display: inline-flex;
    flex-wrap: wrap;
    justify-content: space-between;
	width: calc(33.3334% - 4px);
    margin: 2px;
	padding: 5px;
    position: relative;
    align-items: center;
	flex-grow: 1;

	&:hover {
		border-radius: 6px;
	}
}

.attachment:hover,
.attachment span:hover {
	background-color: var(--color-background-hover);

	&.message-attachment--can-preview * {
		cursor: pointer;
	}
}

.mail-attachment-img--wrapper {
	height: 44px;
	width: 44px;
	overflow: hidden;
	display:flex;
	justify-content: center;
	position: relative;
	border-radius: 6px;

	img {
		transition: 0.3s;
		opacity: 1;
		width: 44px;
		height: 44px;
	}

	.mail-attached-image {
		width: 100px;
	}
}

.mail-attached--content {
	width: calc(100% - 100px);
	display: flex;
    flex-direction: column;
}

.mail-attached-image {
	display: block;
	max-width: 100%;
	border-radius: var(--border-radius);
}

.attachment-import-popover {
	inset-inline-end: 32px;
	top: 42px;
}

.mail-attached-image:hover {
	opacity: 0.8;
}

.attachment-name {
	display: inline-block;
	width: 100%;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	vertical-align: middle;
}

/* show attachment size less prominent */
.attachment-size {
	-ms-filter: 'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)';
	opacity: 0.5;
	font-size: 12px;
	line-height: 14px;
}

.attachment-icon {
	vertical-align: middle;
	text-align: start;
	margin-bottom: 20px;
}

.action-item {
	transition: 0.4s;
}

.mail-message-attachments {
	overflow-x: auto;
	overflow-y: auto;
}
</style>
