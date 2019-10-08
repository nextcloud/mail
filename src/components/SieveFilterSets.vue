<template>
	<div class="main-container flex-container">
		<div class="filtersets-container">
				<button class="sieve-button" @click="addFilterSet()">{{ t("mail", "Add") }}</button>
				<button class="sieve-button" @click="rmFilterSet()">{{ t("mail", "Remove") }}</button>
				<template v-if="selFilterSetID !== -1">
					<button class="sieve-button" v-if="$store.getters.getFilterSetByID(accountID, selFilterSetID).parsed" @click="rawEdit()">{{ t("mail", "Raw Edit") }}</button>
					<button class="sieve-button" v-else @click="parse()">{{ t("mail", "Parse") }}</button>
				</template>
			<draggable
			class="list-group"
			v-model="filterSets"
			@choose="selFilterSet($event.oldIndex)"
			>
				<div
				v-for="filterSet in filterSets"
				:class="setClassOnSelect(filterSet.id)"
				:key="filterSet.id"
				>
					<div class="flex-line">
						<div>
							<input type="checkbox" class="active-script" :checked="filterSet.active" @input="toggleActive(filterSet.id)"
							style="height: 13px; min-height: 13px; margin-left: 3px; cursor: auto;">
						</div>
						<div v-if="filterSetNameEdit !== filterSet.id" class="list-text">
							{{ filterSet.name }}
						</div>
						<div v-else class="list-text">
							<input style="margin: 0px;width: 100%;" v-model="filterSetName">
						</div>
						<div>
							<Actions v-if="filterSetNameEdit !== filterSet.id">
								<ActionButton icon="icon-rename" @click="filterSetNameEdit = filterSet.id; filterSetName = filterSet.name">Edit</ActionButton>
							</Actions>
							<Actions v-else>
								<ActionButton icon="icon-confirm" @click="filterSetNameEditConfirm()">Save</ActionButton>
							</Actions>
						</div>
					</div>
				</div>
			</draggable>
		</div>
		<template v-if="selFilterSetID !== -1">
			<SieveFilters v-if="$store.getters.getFilterSetByID(accountID,selFilterSetID).parsed"
			:accountID="accountID" 
			:filterSetID="selFilterSetID" />
			<SieveFiltersRaw v-else 
			:accountID="accountID" 
			:filterSetID="selFilterSetID" />
		</template>
	</div>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'
	import SieveFilters from "./SieveFilters"
	import SieveFiltersRaw from "./SieveFiltersRaw"
	import {parseSieveScript, makeSieveScript, ParseSieveError, getScripts} from "../service/FiltersService"
	import Vue from 'vue'
	import Logger from '../logger'

	export default {
		name: 'SieveFilterSets',
		components: {
			Actions,
			ActionButton,
			draggable,
			Message,
			SieveFilters,
			SieveFiltersRaw,
		},
		props: {
			accountID: Number,
		},
		data() {
			return {
				filterSetName: "",
				filterSetNameEdit: -1,
				selFilterSetID: -1,
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
							"accountID": this.accountID,
					})
				}
			},
		},
		methods: {
			rawEdit() {
				const raw = makeSieveScript(this.$store.state.sieveFilters[this.accountID][this.selFilterSetID])
				this.$store.commit("updateFilterSet", {
					"accountID": this.accountID,
					"filterSetID": this.selFilterSetID,
					"value": {
						"id": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).id,
						"name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).name,
						"original_name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).original_name,
						"parsed": false,
						"active": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).active,
						"changed": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).changed,
						"raw": raw,
					}
				})
				this.$store.commit("rmFilters", {
					"accountID": this.accountID,
					"filterSetID": this.selFilterSetID,
				})
			},
			parse() {
				try {
					let filters = parseSieveScript(this.filterSets[this.selFilterSetID].raw)
					this.$store.commit("updateFilterSet", {
						"accountID": this.accountID,
						"filterSetID": this.selFilterSetID,
						"value": {
							"id": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).id,
							"active": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).active,
							"name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).name,
							"original_name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).original_name,
							"changed": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).changed,
							"parsed": true,
						}
					})
					for (const filter of filters) {
						this.$store.commit("newFilter", {
							"accountID": this.accountID,
							"filterSetID": this.selFilterSetID,
							"filter": filter,
						})
					}
				} catch (e) {
					if (e instanceof ParseSieveError) {
						this.$store.commit("updateFilterSet", {
							"accountID": this.accountID,
							"filterSetID": this.selFilterSetID,
							"value": {
								"id": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).id,
								"name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).name,
								"active": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).active,
								"parsed": false,
								"raw": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).raw,
								"parseError": e.message,
								"original_name": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).original_name,
								"changed": this.$store.getters.getFilterSetByID(this.accountID, this.selFilterSetID).changed,
							}
						})
					} else {
						throw e
					}
				}
			},
			setClassOnSelect(filterSetID) {
				if (filterSetID === this.selFilterSetID) {
					return "list-group-item list-group-item-a"
				} else {
					return "list-group-item"
				}
			},
			filterSetNameEditConfirm () {
				if (this.$store.getters.getFilterSetByName(this.accountID, this.filterSetName) === undefined) {
					this.$store.commit("updateFilterSetName", {
						"accountID": this.accountID,
						"filterSetID": this.filterSetNameEdit,
						"name": this.filterSetName,

					});
					this.filterSetNameEdit = -1;
				} else {
					if (this.$store.getters.getFilterSetByName(this.accountID, this.filterSetName).id === this.filterSetNameEdit) {
						this.filterSetNameEdit = -1
					} else {
						Message.warning({
							"type": "warning",
							"message": t("mail", "Filterset names must be unique."),
							"position": "bottom-center",
						});
					}
				}
			},
			selFilterSet (index) {
				this.selFilterSetID = this.$store.state.sieveFilterSets[this.accountID][index].id;
				if (this.selFilterSetID !== this.filterSetNameEdit) { // close edit on change
					this.filterSetNameEdit = -1;
					this.rawSieveScript = ''
				}
			},
			addFilterSet () {
				this.$store.commit("newFilterSet", {
					"accountID": this.accountID,
					"raw": "",
					"changed": true,
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
			toggleActive(filterSetID) {
				this.$store.commit('toggleActiveSieve', {
					"accountID": this.accountID,
					"filterSetID": filterSetID,
				})
			},
		},
		watch: {
			accountID: function () {
				this.filterSetNameEdit = -1;
				this.selFilterSetID = -1;
				this.$store.dispatch("fetchSieveScripts", this.accountID);
			},
		},
/*		created(){
			this.$store.dispatch("fetchSieveScripts", this.accountID);
		}, */
	}
</script>
