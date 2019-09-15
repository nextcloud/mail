<template>
	<div class="main-container flex-container">
		<div class="filtersets-container">
			<draggable
			class="list-group"
			v-model="filterSets"
			@choose="selFilterSet($event.oldIndex-2)"
			>
				<div
				v-for="filterSet in filterSets"
				:class="setClassOnSelect(filterSet.id)"
				:key="filterSet.id"
				>
					<div class="flex-line">
						<div v-if="filterSetNameEdit !== filterSet.id" class="list-text">
							<div>{{ filterSet.name }}</div>
						</div>
						<div v-else class="list-text">
							<input style="margin: 0;" v-model="filterSetName"></input>
						</div>
						<div>
							<Actions v-if="filterSetNameEdit !== filterSet.id">
								<ActionButton icon="icon-rename" @click="filterSetNameEdit = filterSet.id; filterSetName = filterSet.name">Edit</ActionButton>
							</Actions>
							<Actions v-else>
								<ActionButton icon="icon-confirm" @click="filterSetNameEditConfirm">Save</ActionButton>
							</Actions>
						</div>
					</div>
				</div>
				<button slot="header" @click="addFilterSet()">{{ t("mail", "Add") }}</button>
				<button slot="header" @click="rmFilterSet()">{{ t("mail", "Remove") }}</button>
			</draggable>
		</div>
		<div v-if="selFilterSetID !== -1" class="filter-container">
			<SieveFilters v-if="$store.getters.getFilterSetByID(accountID,selFilterSetID).parsed"
			:accountID="accountID" 
			:filterSetID="selFilterSetID" />
			<div v-else class="flex-horizontal">
				<div class="flex-line">
					<div class="list-text">
						<div>{{ t("mail", "Filterset not parsable. Fallback to raw edit.") }}</div>
					</div>
					<div>
						<Actions v-if="rawSieveScript === ''">
							<ActionButton icon="icon-rename" 
							@click="rawSieveScript = $store.getters.getFilterSetByID(accountID,selFilterSetID).raw; ">
								Edit
							</ActionButton>
						</Actions>
						<Actions v-else>
							<ActionButton icon="icon-confirm" @click="rawSieveConfirm">Save</ActionButton>
						</Actions>
					</div>
				</div>
				<div v-if="rawSieveScript === ''">
					<textarea :value="$store.getters.getFilterSetByID(accountID,selFilterSetID).raw" 
					v-autosize disabled="true" style="width: 100%; resize: none;"></textarea>
				</div>
				<div v-else>
					<textarea v-autosize v-model="rawSieveScript" style="width: 100%; resize: none;"></textarea>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import Vue from 'vue'
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'
	import SieveFilters from "./SieveFilters"
	import Autosize from 'vue-autosize'

	Vue.use(Autosize)

	import Logger from '../logger'

	export default {
		name: 'SieveFilterSets',
		components: {
			Actions,
			ActionButton,
			draggable,
			Message,
			SieveFilters,
		},
		props: {
			accountID: Number,
		},
		data() {
			return {
				filterSetName: "",
				filterSetNameEdit: -1,
				selFilterSetID: -1,
				rawSieveScript: '',
			}
		},
		computed: {
			filterSets: {
				get() {
					return this.$store.state.sieveFilterSets[this.accountID]
				},
				set(value) {
					this.$store.commit("updateFilterSets", {
							value,
							"accountID": 1,
					})
				}
			},
		},
		methods: {
			setClassOnSelect(filterSetID) {
				if (filterSetID === this.selFilterSetID) {
					return "list-group-item list-group-item-a"
				} else {
					return "list-group-item"
				}
			},
			filterSetNameEditConfirm () {
					if (this.$store.getters.getFilterSetByName(accountID, this.filterSetName) === undefined) {
						this.$store.commit("updateFilterSetName", {
							"accountID": this.accountID,
							"filterSetID": this.filterSetNameEdit,
							"name": this.filterSetName,
						});
						this.filterSetNameEdit = -1;
					} else {
					Message.warning({
						"type": "warning",
						"message": t("mail", "Filterset names must be unique."),
						"position": "bottom-center",
					});
				}
			},
			rawSieveConfirm () {
				this.$store.commit("updateRawSieveScript", {
					accountID: this.accountID,
					filterSetID: this.selFilterSetID,
					raw: this.rawSieveScript,
				});
				this.rawSieveScript = '';
			},
			selFilterSet (index) {
				if (index >= 0) { // ignore buttons
					this.selFilterSetID = this.$store.state.sieveFilterSets[this.accountID][index].id;
					if (this.selFilterSetID !== this.filterSetNameEdit) { // close edit on change
						this.filterSetNameEdit = -1;
						this.rawSieveScript = ''
					}
				}
			},
			addFilterSet () {
				let newName = "Filter#";
				let counter = 0;
				newName += newID;
				if (this.$store.getters.getFilterSetByName(accountID, newName) !== undefined){
					while (this.$store.getters.getFilterSetByName(accountID, newName+"_"+counter) !== undefined) {
						counter += 1;
					}
					newName = newName+"_"+counter
				}
				this.$store.commit("newFilterSet", {
					"accountID": this.accountID,
					"name": newName,
					"raw": "",
				});
			},
			rmFilterSet() {
				var filterSetID = this.selFilterSetID;
				if (this.selFilterSetID === -1) {
					Message.warning({
						"type": "warning",
						"message": t("mail", "No filterset selected."),
						"position": "bottom-center",
					});
				} else {
					if (this.$store.state.sieveFilterSets[this.accountID].length === 1) {
						this.selFilterSetID = -1;
					} else {
						var dropIndex = this.$store.state.sieveFilterSets[this.accountID]
							.findIndex(x => x.id === this.selFilterSetID);
						if (dropIndex === this.$store.state.sieveFilterSets[this.accountID].length-1) {
							this.selFilterSet(dropIndex-1);
						} else {
							this.selFilterSet(dropIndex+1);
						}
					}
					this.$store.commit("rmFilterSet", {
						"accountID": this.accountID,
						"filterSetID": filterSetID,
					});
				}
			},
		},
		watch: {
			accountID: function () {
				this.filterSetNameEdit = -1;
				this.selFilterSetID = -1;
				this.$store.dispatch("fetchSieveScripts", this.accountID);
			},
		},
		created(){
			this.$store.dispatch("fetchSieveScripts", this.accountID);
		},
	}
</script>
