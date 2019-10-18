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
		<div style="text-align: left; font-size: 10px">{{ t("mail", "For incoming mail:") }}</div>
		<div style="text-align: center;">
			<button :style="toggleTestArrayType('allof')" @click="changeType('allof')">{{ t("mail", "matching all of the following rules") }}</button>
			<button :style="toggleTestArrayType('anyof')" @click="changeType('anyof')">{{ t("mail", "matching any of the following rules") }}</button>
		</div>
		<div v-for="test in testsEdit.list" class="flex-line">
			<div class="list-text">
				<select v-model="test.type" @change="testTypeChange(test)">
					<option v-for="key in sieveTestsKeys" :value="key" :selected="key === test.type">{{ sieveTests[key].name }}</option>>
				</select>

				<!-- subject/to/from-->
				<span v-if="['subject', 'from', 'to', 'content'].indexOf(test.type) > -1">
					<select v-model="test.opts.matchType" @change="commit()">
						<option v-for="mT in sieveTests[test.type].matchTypes" :value="mT" :selected="mT === test.opts.matchType">
							{{ matchTypes[mT].name }}
						</option>
					</select>
					<button :style="test.opts.negate ? 'background-color: silver;' : ''" 
					@click="test.opts.negate = !test.opts.negate; commit()">{{ t("mail", "not") }}</button>
					<input style="margin: 0;" v-model="test.opts.value" @change="commit()"></input>
				</span>
				<span v-if="['exists'].indexOf(test.type) > -1">
					<button :style="test.opts.negate ? 'background-color: silver;' : ''" 
					@click="test.opts.negate = !test.opts.negate; commit()">{{ t("mail", "not") }}</button>
					<input style="margin: 0;" v-model="test.opts.value" @change="commit()"></input>
				</span>
			
			</div>
			<div>
				<Actions v-if="testsEdit.list.length > 1">
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
</template>

<script>
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import {sieveTestsBlueprint, matchTypeBlueprint} from '../service/FiltersService'
	
	export default {
		name: 'SieveTests',
		components: {
			Actions,
			ActionButton,
		},
		props: {
			value: Object,
		},
		data() {
			return {
				testsEdit: JSON.parse(JSON.stringify(this.value)),
				sieveTests: sieveTestsBlueprint,
				sieveTestsKeys: Object.keys(sieveTestsBlueprint),
				matchTypes: matchTypeBlueprint,
			}
		},
		methods: {
			commit() {
				this.$emit("input", this.testsEdit)
			},
			changeType(newType) {
				this.testsEdit.type = newType
				this.commit()
			},
			testTypeChange(test) {
				test.opts = JSON.parse(JSON.stringify(this.sieveTests[test.type].opts_default))
				this.commit()
			},
			addTest() {
				let newID = 0
				while (this.testsEdit.list.find(x => x.id === newID) !== undefined){
					newID += 1
				}
				this.testsEdit.list.push({
					"id": newID,
					"type": "subject",
					"opts": JSON.parse(JSON.stringify(this.sieveTests["subject"].opts_default))
				})
				this.commit()
			},
			rmTest(testID){
				this.testsEdit.list = this.testsEdit.list.filter(x => x.id !== testID)
				this.commit()
			},
			toggleTestArrayType(button) {
				if (button === this.testsEdit.type) {
					return "background-color: silver;"
				}
			},
		},
		watch: {
			value: function () {
				this.testsEdit = JSON.parse(JSON.stringify(this.value));
			},
		},
	}
</script>