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
					<div class="flex-horizontal filter-test-field">
						<div style="text-align: left; font-size: 10px">{{ t("mail", "For incoming mail:") }}</div>
						<div style="text-align: center;">
							<button :style="toggleTestArrayType('allof')" @click="filterEdit.testArrayType = 'allof'">{{ t("mail", "matching all of the following rules") }}</button>
							<button :style="toggleTestArrayType('anyof')" @click="filterEdit.testArrayType = 'anyof'">{{ t("mail", "matching any of the following rules") }}</button>
							<button :style="toggleTestArrayType('any')" @click="filterEdit.testArrayType = 'any'">{{ t("mail", "all messages") }}</button>
						</div>
						<div v-for="test in filterEdit.tests" class="flex-line">
							<div class="list-text">
								<select v-model="test.type" @change="testTypeChange(test)">
									<option v-for="key in sieveTestsKeys" :value="key" :selected="key === test.type">{{ sieveTests[key].name }}</option>>
								</select>

								<!-- subject/to/from-->
								<span v-if="['subject', 'from', 'to'].indexOf(test.type) > -1">
									<select v-model="test.opts.matchType">
										<option v-for="mT in sieveTests[test.type].matchTypes" :value="mT" :selected="mT === test.opts.matchType">
											{{ matchTypes[mT].name }}
										</option>
									</select>
									<button :style="test.opts.negate ? 'background-color: silver;' : ''" 
									@click="test.opts.negate = !test.opts.negate">{{ t("mail", "not") }}</button>
									<input style="margin: 0;" v-model="test.opts.value"></input>
								</span>
							
							</div>
							<div>
								<Actions v-if="filterEdit.tests.length > 1">
									<ActionButton icon="icon-delete" @click="rmTest(test.id)">{{ t("mail", "Delete") }}</ActionButton>
								</Actions>
							</div>
						</div>
						<div>
							<Actions>
								<ActionButton icon="icon-add" @click="addTest">{{ t("mail", "Add") }}</ActionButton>
							</Actions>
						</div>
					</div>
					<div class="flex-horizontal filter-test-field">
						<div style="text-align: left; font-size: 10px">{{ t("mail", "Do the following:") }}</div>
						<div v-for="action in filterEdit.actions" class="flex-line">
							<div class="list-text">
								<select v-model="action.type" @change="actionTypeChange(action)">
									<option v-for="key in sieveActionsKeys" 
									:value="key" 
									:selected="key === action.type">{{ sieveActions[key].name }}</option>>
								</select>

								<!-- move/copy -->
								<span v-if="['move','copy'].indexOf(action.type) > -1">
									<select v-model="action.opts.value">
										<option v-for="folder in $store.getters.getFolders(accountID)" 
										:value="folder.id" 
										:selected="folder.id === action.opts.value"> {{ folderName(folder) }} </option>
									</select>
								</span>
							
							</div>
							<div>
								<Actions v-if="filterEdit.actions.length > 1">
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
				</div>
			</div>
		</div>
		<button slot="header" @click="addFilter()">{{ t("mail", "Add") }}</button>
		<button slot="header" @click="rmFilter()">{{ t("mail", "Remove") }}</button>
		<button slot="header" @click="rawEdit()">{{ t("mail", "Raw Edit") }}</button>
	</draggable>
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import draggable from 'vuedraggable'
	import Message from 'vue-m-message'
	import Logger from '../logger'
	import {sieveActionsBlueprint, sieveTestsBlueprint, matchTypeBlueprint} from '../service/SieveParserService'
	import {translate as mailboxTranslator} from '../l10n/MailboxTranslator.js'

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
				filterEdit: -1,
				selFilterID: -1,
				sieveTests: sieveTestsBlueprint,
				sieveTestsKeys: Object.keys(sieveTestsBlueprint),
				matchTypes: matchTypeBlueprint,
				sieveActions: sieveActionsBlueprint,
				sieveActionsKeys: Object.keys(sieveActionsBlueprint),
			}
		},
/*		created () {
			this.sieveests = sieveTestsBlueprint
		},*/
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
			testTypeChange(test) {
				test.opts = JSON.parse(JSON.stringify(this.sieveTests[test.type].opts))
			},
			addTest() {
				let newID = 0
				while (this.filterEdit.tests.find(x => x.id === newID) !== undefined){
					newID += 1
				}
				this.filterEdit.tests.push({
					"id": newID,
					"type": "subject",
					"opts": JSON.parse(JSON.stringify(this.sieveTests["subject"].opts))
				})
			},
			rmTest(testID){
				this.filterEdit.tests = this.filterEdit.tests.filter(x => x.id !== testID)
			},
			actionTypeChange(action) {
				action.opts = JSON.parse(JSON.stringify(this.sieveActions[action.type].opts))
			},
			addAction() {
				let newID = 0
				while (this.filterEdit.actions.find(x => x.id === newID) !== undefined){
					newID += 1
				}
				this.filterEdit.actions.push({
					"id": newID,
					"type": "move",
					"opts": JSON.parse(JSON.stringify(this.sieveActions["move"].opts))
				})
			},
			rmAction(actionID){
				this.filterEdit.actions = this.filterEdit.actions.filter(x => x.id !== actionID)
			},
			folderName(folder) {
				return mailboxTranslator(folder)
			},
			toggleTestArrayType(button) {
				if (button === this.filterEdit.testArrayType) {
					return "background-color: silver;"
				}
			},
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
				if (index >= 0) { // ignore buttons
					this.filterSetNameEdit = -1;
					this.selFilterID = this.$store.state.sieveFilters[this.accountID][this.filterSetID][index].id;
					if (this.selFilterID !== this.filterEdit.id) {
						this.filterEdit = -1;
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
						"testArrayType": "allof",
						"tests": [{
							"id": 0,
							"type": "subject",
							"opts": JSON.parse(JSON.stringify(this.sieveTests["subject"].opts))
						}],
						"actions": [{
							"id": 0,
							"type": "move",
							"opts": JSON.parse(JSON.stringify(this.sieveActions["move"].opts))
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
				this.filterEdit = -1;
				this.selFilterID = -1;
			},
		},
	}
</script>

<style>
</style>
