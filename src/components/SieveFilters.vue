<template>
	<div class="filter-container">
		<button class="sieve-button" @click="addFilter()">{{ t("mail", "Add") }}</button>
		<button class="sieve-button" @click="rmFilter()">{{ t("mail", "Remove") }}</button>
		<draggable
			class="list-group"
			v-model="filters"
			@choose="selFilter($event.oldIndex)"
			>
			<div
				v-for="filter in filters"
				:class="setClassOnSelect(filter.id)"
				:key="filter.id"
				>
				<div class="flex-horizontal"> 
					<div class="flex-line">
						<div v-if="filterEdit.id !== filter.id" class="list-text">
							<div>{{ filter.name }}</div>
						</div>
						<div v-else class="list-text">
							<input style="margin: 0;" v-model="filterEdit.name"></input>
						</div>
						<div>
							<Actions v-if="filterEdit.id !== filter.id">
								<ActionButton icon="icon-rename" @click="filterEditStart(filter.id)">Edit</ActionButton>
							</Actions>
							<Actions v-else>
								<ActionButton icon="icon-confirm" @click="filterEditConfirm">Save</ActionButton>
							</Actions>
						</div>
					</div>
					<div v-if="filterEdit.id === filter.id" class="flex-horizontal">
						<SieveTests v-model="filterEdit.tests" />
						<SieveActions v-model="filterEdit.actions" :accountID="accountID"/>
					</div>
				</div>
			</div>
		</draggable>
	</div>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'
	import Logger from '../logger'
	import SieveTests from './SieveTests'
	import SieveActions from './SieveActions'
	import {sieveActionsBlueprint, sieveTestsBlueprint} from '../service/FiltersService'

	export default {
		name: 'SieveFilters',
		components: {
			Actions,
			ActionButton,
			draggable,
			Message,
			SieveTests,
			SieveActions,
		},
		props: {
			accountID: Number,
			filterSetID: Number, 
		},
		data() {
			return {
				filterEdit: -1,
				selFilterID: -1,
			}
		},
		computed: {
			filters:{
				get() {
					return this.$store.state.sieveFilters[this.accountID][this.filterSetID];
				},
				set(value) {
					this.$store.commit("updateFilters", {
						"accountID": this.accountID,
						"filterSetID": this.filterSetID,
						value,
					});
				}
			}, 
		},
		methods: {
			setClassOnSelect(filterID) {
				if (filterID === this.selFilterID) {
					return "list-group-item list-group-item-a"
				} else {
					return "list-group-item"
				}
			},
			filterEditStart(id) {
				this.filterEdit = JSON.parse(
					JSON.stringify(this.$store.getters.getFilterByID(this.accountID, this.filterSetID, id)))
			},
			filterEditConfirm () {
				this.$store.commit("updateFilter", {
					"accountID": this.accountID,
					"filterSetID": this.filterSetID,
					"filterID": this.filterEdit.id,
					"filter": this.filterEdit,
				});
				this.filterEdit = -1;
			},
			selFilter (index) {
				this.filterSetNameEdit = -1;
				this.selFilterID = this.$store.state.sieveFilters[this.accountID][this.filterSetID][index].id;
				if (this.selFilterID !== this.filterEdit.id) {
					this.filterEdit = -1;
				}
			},
			addFilter () {
				var newID = 0;
				while (this.$store.state.sieveFilters[this.accountID][this.filterSetID].find(x => x.id === newID) !== undefined) {
					newID += 1;
				}
				this.$store.commit("newFilter", {
					"accountID": this.accountID,
					"filterSetID": this.filterSetID,
					"filter": {
						"id": newID,
						"name": "Filter_"+newID,
						"tests": {
							"type": "allof",
							"list": [{
								"id": 0,
								"type": "subject",
								"opts": JSON.parse(JSON.stringify(sieveTestsBlueprint["subject"].opts_default))
							}],
						},
						"actions": [{
							"id": 0,
							"type": "move",
							"opts": JSON.parse(JSON.stringify(sieveActionsBlueprint["move"].opts_default))
						}],
					}
				});
			},
			rmFilter() {
				var filterID = this.selFilterID;
				if (this.selFilterID === -1) {
					Message.warning({
						"type": "warning",
						"message": t("mail", "No filter selected."),
						"position": "bottom-center",
					});
				} else { 
					if (this.$store.state.sieveFilters[this.accountID][this.filterSetID].length === 1) {
						this.selFilterID = -1;
					} else {
						var dropIndex = this.$store.state.sieveFilters[this.accountID][this.filterSetID]
							.findIndex(x => x.id === this.selFilterID);
						if (dropIndex === this.$store.state.sieveFilters[this.accountID][this.filterSetID]
							.length-1) {
							this.selFilter(dropIndex-1);
						} else {
							this.selFilter(dropIndex+1);
						}
					}
					this.$store.commit("rmFilter", {
						"accountID": this.accountID,
						"filterSetID": this.filterSetID,
						"filterID": filterID,
					});
				}
			},
		},
		watch: {
			filterSetID: function () {
				this.filterEdit = -1;
				this.selFilterID = -1;
			},
		},
	}
</script>

<style>
</style>
