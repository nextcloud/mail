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
		<Actions :boundaries-element="boundariesElement">
			<ActionButton
				v-if="isCalendarEvent"
				class="attachment-import calendar"
				:disabled="loadingCalendars"
				@click.stop="loadCalendars">
				<template #icon>
					<IconAdd v-if="!loadingCalendars" :size="20" />
					<IconLoading v-else-if="loadingCalendars" :size="20" />
				</template>
				{{ t('mail', 'Import into calendar') }}
			</ActionButton>
			<ActionButton
				class="attachment-download"
				@click="download">
				<template #icon>
					<IconDownload :size="20" />
				</template>
				{{ t('mail', 'Download attachment') }}
			</ActionButton>
			<ActionButton
				class="attachment-save-to-cloud"
				:disabled="savingToCloud"
				@click.stop="saveToCloud">
				<template #icon>
					<IconSave v-if="!savingToCloud" :size="20" />
					<IconLoading v-else-if="savingToCloud" :size="20" />
				</template>
				{{ t('mail', 'Save to Files') }}
			</ActionButton>
			<div
				v-on-click-outside="closeCalendarPopover"
				class="popovermenu bubble attachment-import-popover hidden"
				:class="{open: showCalendarPopover}">
				<PopoverMenu :menu="calendarMenuEntries">
					<template #icon>
						<IconAdd :size="20" />
					</template>
				</PopoverMenu>
			</div>
		</Actions>
	</div>
</template>

<script>

import { formatFileSize } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { mixin as onClickOutside } from 'vue-on-click-outside'

import { NcPopoverMenu as PopoverMenu, NcActions as Actions, NcActionButton as ActionButton, NcLoadingIcon as IconLoading } from '@nextcloud/vue'

import IconAdd from 'vue-material-design-icons/Plus'
import IconSave from 'vue-material-design-icons/Folder'
import IconDownload from 'vue-material-design-icons/Download'
import Logger from '../logger'

import { downloadAttachment, saveAttachmentToFiles } from '../service/AttachmentService'
import { getUserCalendars, importCalendarEvent } from '../service/DAVService'

export default {
	name: 'MessageAttachment',
	components: {
		PopoverMenu,
		Actions,
		ActionButton,
		IconAdd,
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
		saveToCloud() {
			const saveAttachment = (id, attachmentId) => (directory) => {
				return saveAttachmentToFiles(id, attachmentId, directory)
			}
			const id = this.$route.params.threadId
			const picker = getFilePickerBuilder(t('mail', 'Choose a folder to store the attachment in'))
				.setMultiSelect(false)
				.addMimeTypeFilter('httpd/unix-directory')
				.setModal(true)
				.setType(1)
				.allowDirectories(true)
				.build()

			return picker
				.pick()
				.then((dest) => {
					this.savingToCloud = true
					return dest
				})
				.then(saveAttachment(id, this.id))
				.then(() => Logger.info('saved'))
				.catch((e) => Logger.error('not saved', { error: e }))
				.then(() => (this.savingToCloud = false))
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
					.then(() => Logger.info('calendar imported'))
					.catch((e) => Logger.error('import error', { error: e }))
					.then(() => (this.showCalendarPopover = false))
			}
		},
	},
}
</script>

<style lang="scss" scoped>

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
	right: 32px;
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
	text-align: left;
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
