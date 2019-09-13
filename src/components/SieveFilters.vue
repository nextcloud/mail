<template>
	<draggable
		class="list-group"
		v-model="filters"
		@choose="selFilter($event.oldIndex-2)"
		>
		<div
			v-for="filter in filters"
			:class="setClassOnSelect(filter.id)"
			:key="filter.id"
			>
			<div class="flex-horizontal"> 
				<div class="flex-line">
					<div v-if="filterNameEdit !== filter.id" class="list-text">
						<div>{{ filter.name }}</div>
					</div>
					<div v-else class="list-text">
						<input style="margin: 0;" v-model="filterName"></input>
					</div>
					<div>
						<Actions v-if="filterNameEdit !== filter.id">
							<ActionButton icon="icon-rename" @click="filterNameEdit = filter.id; filterName = filter.name">Edit</ActionButton>
						</Actions>
						<Actions v-else>
							<ActionButton icon="icon-confirm" @click="filterNameEditConfirm">Edit</ActionButton>
						</Actions>
					</div>
				</div>
				<div v-if="filterNameEdit === filter.id"> Hello World!! </div>
			</div>
		</div>
		<button slot="header" @click="addFilter()">{{ t("mail", "Add") }}</button>
		<button slot="header" @click="rmFilter()">{{ t("mail", "Remove") }}</button>
	</draggable>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'

	import Logger from '../logger'

	export default {
		name: 'SieveFilters',
		components: {
			Actions,
			ActionButton,
			draggable,
			Message,
		},
		props: {
			accountID: Number,
			filterSetID: Number, 
		},
		data() {
			return {
				filterName: "",
				filterNameEdit: -1,
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
			filterNameEditConfirm () {
				this.$store.commit("updateFilterName", {
					"accountID": this.accountID,
					"filterSetID": this.filterSetID,
					"filterID": this.filterNameEdit,
					"name": this.filterName,
				});
				this.filterNameEdit = -1;
			},
			selFilter (index) {
				if (index >= 0) { // ignore buttons
					this.filterSetNameEdit = -1;
					this.selFilterID = this.$store.state.sieveFilters[this.accountID][this.filterSetID][index].id;
					if (this.selFilterID !== this.filterNameEdit) {
						this.filterNameEdit = -1;
					}
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
						"name": "Filter#"+newID,
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
						console.log("Hello");
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
				this.filterNameEdit = -1;
				this.selFilterID = -1;
			},
		},
	}
</script>
