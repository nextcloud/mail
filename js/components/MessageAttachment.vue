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
	<div>
		<img v-if="isImage"
			 class="mail-attached-image"
			 :src="url">
		<br>
		<img class="attachment-icon"
			 :src="mimeUrl"/>
		<span class="attachment-name"
			  :title="fileName (humanFileSize(size))">{{fileName}}
			<span class="attachment-size">({{humanFileSize(size)}})</span>
		</span>
		<button v-if="isCalendarEvent"
				class="button icon-add attachment-import calendar"
				:title="t('mail', 'Import into calendar')"></button>
		<button class="button icon-download attachment-download"
				:title="t('mail', 'Download attachment')"></button>
		<button class="icon-folder attachment-save-to-cloud"
				:title="t('mail', 'Save to Files')"></button>
	</div>
</template>

<script>
	export default {
		name: "MessageAttachment",
		props: {
			id: Number,
			url: String,
			mimeUrl: String,
			isImage: Boolean,
			isCalendarEvent: Boolean,
		}
	}
</script>

<style scoped>

</style>