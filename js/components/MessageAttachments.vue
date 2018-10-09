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
			<MessageAttachment v-for="attachment in attachments"
							   :key="attachment.id"
							   :id="attachment.id"
							   :fileName="attachment.fileName"
							   :size="attachment.size"
							   :url="attachment.downloadUrl"
							   :isImage="attachment.isImage"
							   :isCalendarEvent="attachment.isCalendarEvent"
							   :mimeUrl="attachment.mimeUrl"/>
		</div>
		<p v-if="moreThanOne">
			<button class="attachments-save-to-cloud"
					:class="{'icon-folder' : !savingToCloud, 'icon-loading-small' : savingToCloud}"
					:disabled="savingToCloud"
					v-on:click="saveAll">
				{{ t('mail', 'Save all to Files') }}
			</button>
		</p>
	</div>
</template>

<script>
	import MessageAttachment from './MessageAttachment'
	import {saveAttachmentsToFiles} from '../service/AttachmentService'

	export default {
		name: "MessageAttachments",
		components: {
			MessageAttachment
		},
		props: {
			attachments: Array,
		},
		data () {
			return {
				savingToCloud: false,
			}
		},
		computed: {
			moreThanOne () {
				return this.attachments.length > 1
			}
		},
		methods: {
			saveAll () {
				const pickDestination = () => {
					return new Promise((res, rej) => {
						OC.dialogs.filepicker(
							t('mail', 'CChoose a folder to store the attachments in'),
							res,
							false,
							'httpd/unix-directory',
							true
						)
					})
				}
				const saveAttachments = (accountId, folderId, messageId) => directory => {
					return saveAttachmentsToFiles(
						accountId,
						folderId,
						messageId,
						directory
					)
				}
				const accountId = this.$route.params.accountId
				const folderId = this.$route.params.folderId
				const messageId = this.$route.params.messageId

				return pickDestination()
					.then(dest => {
						this.savingToCloud = true
						return dest
					})
					.then(saveAttachments(accountId, folderId, messageId))
					.then(() => console.info('saved'))
					.catch(e => console.error('not saved', e))
					.then(() => this.savingToCloud = false)
			}
		}
	}
</script>

<style>
	.mail-message-attachments {
		margin-bottom: 20px;
	}

	/* show icon + text for Download all button
		as well as when there is only one attachment */
	.attachments-save-to-cloud {
		background-position: 9px center;
		padding-left: 32px;
	}
</style>
