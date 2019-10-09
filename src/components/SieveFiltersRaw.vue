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
	<div class="filter-container">
		<div class="flex-horizontal">
			<div class="flex-line">
				<div class="list-text">
					<div v-if="$store.getters.getFilterSetByID(accountID,filterSetID).parseError !== undefined">
						{{ t("mail", "Filterset not parsable. Reason: ")+$store.state.sieveFilterSets[accountID][filterSetID].parseError }}
					</div>
				</div>
				<div>
					<Actions v-if="!rawSieveScriptEdit">
						<ActionButton icon="icon-rename" 
						@click="rawSieveScript = $store.getters.getFilterSetByID(accountID,filterSetID).raw; rawSieveScriptEdit=true">
							Edit
						</ActionButton>
					</Actions>
					<Actions v-else>
						<ActionButton icon="icon-confirm" @click="rawSieveConfirm">Save</ActionButton>
					</Actions>
				</div>
			</div>
			<div v-if="!rawSieveScriptEdit">
				<textarea :value="$store.getters.getFilterSetByID(accountID,filterSetID).raw" 
				v-autosize disabled="true" style="width: 100%; resize: none;"></textarea>
			</div>
			<div v-else>
				<textarea v-autosize v-model="rawSieveScript" style="width: 100%; resize: none;"></textarea>
			</div>
		</div>
	</div>
</template>

<script>
	import Vue from 'vue'
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import Autosize from 'vue-autosize'
	import Logger from '../logger'

	Vue.use(Autosize)

	export default {
		name: 'SieveFiltersRaw',
		components: {
			Actions,
			ActionButton,
		},
		props: {
			accountID: Number,
			filterSetID: Number, 
		},
		data() {
			return {
				rawSieveScript: "",
				rawSieveScriptEdit: false,
			}
		},
		computed: {
		},
		methods: {
			rawSieveConfirm () {
				this.$store.commit("updateRawSieveScript", {
					accountID: this.accountID,
					filterSetID: this.filterSetID,
					raw: this.rawSieveScript,
				});
				this.rawSieveScriptEdit = false;
			},
		},
		watch: {
			filterSetID: function () {
				this.rawSieveScriptEdit = false;
			},
		},
	}
</script>
