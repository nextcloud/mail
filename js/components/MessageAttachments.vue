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
	<v-template>
		<div class="attachments">
			<MessageAttachment v-for="attachment in attachments"
							   :key="attachment.id"/>
		</div>
		<p v-if="moreThanOne">
			<button class="icon-folder attachments-save-to-cloud">
				{{ t('mail', 'Save all to Files') }}
			</button>
		</p>
	</v-template>
</template>

<script>
	import MessageAttachment from "./MessageAttachment";

	export default {
		name: "MessageAttachments",
		components: {
			MessageAttachment
		},
		props: {
			attachments: Array,
		},
		computed: {
			moreThanOne () {
				return this.attachments.length > 1
			}
		}
	}
</script>

<style scoped>

</style>