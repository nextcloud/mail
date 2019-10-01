<template>
	<div class="main-container flex-container">
		<div class="filtersets-container">
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
						<div v-if="filterSetNameEdit !== filterSet.id" class="list-text">
							<div>{{ filterSet.name }}</div>
						</div>
						<div v-else class="list-text">
							<input style="margin: 0px;width: 100%;" v-model="filterSetName"></input>
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
				<button slot="header" @click="addFilterSet()">{{ t("mail", "Add") }}</button>
				<button slot="header" @click="rmFilterSet()">{{ t("mail", "Remove") }}</button>
				<template v-if="selFilterSetID !== -1">
					<button v-if="filterSets[selFilterSetID].parsed" slot="header" @click="rawEdit()">{{ t("mail", "Raw Edit") }}</button>
					<button v-else slot="header" @click="parse()">{{ t("mail", "Parse") }}</button>
				</template>
			</draggable>
		</div>
		<div v-if="selFilterSetID !== -1" class="filter-container">
			<SieveFilters v-if="$store.getters.getFilterSetByID(accountID,selFilterSetID).parsed"
			:accountID="accountID" 
			:filterSetID="selFilterSetID" />
			<SieveFiltersRaw v-else 
			:accountID="accountID" 
			:filterSetID="selFilterSetID" />
		</div>
	</div>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'
	import SieveFilters from "./SieveFilters"
	import SieveFiltersRaw from "./SieveFiltersRaw"
	import {parseSieveScript, makeSieveScript, ParseSieveError} from "../service/SieveParserService"
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
							"accountID": 1,
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
						"id": this.filterSets[this.selFilterSetID].id,
						"name": this.filterSets[this.selFilterSetID].name,
						"parsed": false,
						"raw": raw,
						"parseError": "user raw editing",
					}
				})
			},
			parse() {
				try {
					let {req, filters} = parseSieveScript(this.filterSets[this.selFilterSetID].raw)
					this.$store.commit("updateFilterSet", {
						"accountID": this.accountID,
						"filterSetID": this.selFilterSetID,
						"value": {
							"id": this.filterSets[this.selFilterSetID].id,
							"name": this.filterSets[this.selFilterSetID].name,
							"parsed": true,
							"require": req,
						}
					})
					for (filter of filters) {
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
								"id": this.filterSets[this.selFilterSetID].id,
								"name": this.filterSets[this.selFilterSetID].name,
								"parsed": false,
								"raw": this.filterSets[this.selFilterSetID].raw,
								"parseError": e.message,
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
				// 2 buttons if no filterset selected
				if (this.selFilterSetID === -1) {
					index = index-2
				} else {
					index = index-3
				}
				if (index >= 0) { // ignore buttons
					this.selFilterSetID = this.$store.state.sieveFilterSets[this.accountID][index].id;
					if (this.selFilterSetID !== this.filterSetNameEdit) { // close edit on change
						this.filterSetNameEdit = -1;
						this.rawSieveScript = ''
					}
				}
			},
			addFilterSet () {
				this.$store.commit("newFilterSet", {
					"accountID": this.accountID,
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
/*		created(){
			this.$store.dispatch("fetchSieveScripts", this.accountID);
		}, */
	}
</script>
