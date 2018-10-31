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
			<li v-for="attachment in value">
				<div class="new-message-attachment-name">
					{{attachment.displayName}}
				</div>
				<div class="new-message-attachments-action svg icon-delete"></div>
			</li>
		</ul>
		<button class="button"
				v-on:click="onAddLocalAttachment">
			<span class="icon-upload"/>
			{{ t('mail', 'Upload attachment') }}
		</button>
		<button class="button"
				v-on:click="onAddCloudAttachment">
			<span class="icon-folder"/>
			{{ t('mail', 'Add attachment from Files') }}
		</button>
		<input type="file"
			   ref="localAttachments"
			   v-on:change="onLocalAttachmentSelected"
			   multiple
			   style="display: none;">
	</div>
</template>

<script>
	import _ from 'lodash'
	import {translate as t} from 'nextcloud-server/dist/l10n'
	import {pickFileOrDirectory} from 'nextcloud-server/dist/files'

	import {uploadLocalAttachment} from '../service/AttachmentService'

	export default {
		name: 'ComposerAttachments',
		props: {
			value: {
				type: Array,
				required: true,
			}
		},
		methods: {
			onAddLocalAttachment () {
				this.$refs.localAttachments.click()
			},
			fileNameToAttachment (name, id) {
				return {
					fileName: name,
					displayName: _.trimStart(name, '/'),
					id,
					isLocal: !_.isUndefined(id)
				}
			},
			emitNewAttachment (attachment) {
				console.log('emit', attachment)
				this.$emit('input', this.value.concat([attachment]))
			},
			onLocalAttachmentSelected (e) {
				return Promise.all(
					_.map(
						e.target.files,
						file => uploadLocalAttachment(file)
							.then(({file, id}) => {
								console.info('uploaded')
								return this.emitNewAttachment(
									this.fileNameToAttachment(file.name, id)
								)
							})
					)
				).catch(console.error.bind(this))
			},
			onAddCloudAttachment () {
				return pickFileOrDirectory(t('mail', 'Choose a file to add as attachment'))
					.then(path => this.emitNewAttachment(
						this.fileNameToAttachment(path)
					))
					.catch(console.error.bind(this))
			}
		}
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
		opacity: .5;
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
