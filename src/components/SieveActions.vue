<!--
  - @copyright Copyright (c) 2019, Merlin Mittelbach <merlin.mittelbach@memit.de>
  -
  - @author 2019, Merlin Mittelbach <merlin.mittelbach@memit.de>
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
	<div class="flex-horizontal filter-test-field">
		<div style="text-align: left; font-size: 10px">{{ t("mail", "Do:") }}</div>
		<div v-for="action in actionsEdit" class="flex-line">
			<div class="list-text">
				<select v-model="action.type" @change="actionTypeChange(action)">
					<option v-for="key in sieveActionsKeys" 
					:value="key" 
					:selected="key === action.type">{{ sieveActions[key].name }}</option>>
				</select>

				<!-- move/copy -->
				<span v-if="['move','copy'].indexOf(action.type) > -1">
					<select v-model="action.opts.value" @change="commit()">
						<option v-for="folder in $store.getters.getFolders(accountID)" 
						:value="folder.id" 
						:selected="folder.id === action.opts.value"> {{ folderName(folder) }} </option>
					</select>
				</span>

			</div>
			<div>
				<Actions v-if="actionsEdit.length > 1">
					<ActionButton icon="icon-delete" @click="rmAction(action.id)">{{ t("mail", "Delete") }}</ActionButton>
				</Actions>
			</div>
		</div>
		<div>
			<Actions>
				<ActionButton icon="icon-add" @click="addAction">{{ t("mail", "Add") }}</ActionButton>
			</Actions>
		</div>
	</div>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import {sieveActionsBlueprint} from '../service/FiltersService'
	import {translate as mailboxTranslator} from '../l10n/MailboxTranslator.js'
	
	export default {
		name: 'SieveActions',
		components: {
			Actions,
			ActionButton,
		},
		props: {
			value: Array,
			accountID: Number,
		},
		data() {
			return {
				actionsEdit: JSON.parse(JSON.stringify(this.value)),
				sieveActions: sieveActionsBlueprint,
				sieveActionsKeys: Object.keys(sieveActionsBlueprint),
			}
		},
		methods: {
			commit() {
				this.$emit("input", this.actionsEdit)
			},
			actionTypeChange(action) {
				action.opts = JSON.parse(JSON.stringify(this.sieveActions[action.type].opts_default))
				this.commit()
			},
			addAction() {
				let newID = 0
				while (this.actionsEdit.find(x => x.id === newID) !== undefined){
					newID += 1
				}
				this.actionsEdit.push({
					"id": newID,
					"type": "move",
					"opts": JSON.parse(JSON.stringify(this.sieveActions["move"].opts_default))
				})
				this.commit()
			},
			rmAction(actionID){
				this.actionsEdit = this.actionsEdit.filter(x => x.id !== actionID)
				this.commit()
			},
			folderName(folder) {
				return mailboxTranslator(folder)
			},
		},
		watch: {
			value: function () {
				this.actionsEdit = JSON.parse(JSON.stringify(this.value));
			},
		},
	}
</script>