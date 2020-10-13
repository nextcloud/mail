<template>
	<div class="app-content-list filter-navigation">
		<ul id="filter-list">
			<AppNavigationNew :text="t('mail', 'Add Filterrule')" button-class="icon-add" @click="addRule" />
			<template v-for="(rule, index) in filterrules">
				<AppContentListEntry
					:key="'rule_' + index"
					:to="{
						name: 'filterRules',
						params: {
							accountId: account.id,
							ruleIndex: index,
							filterrule: filterrules[index],
							filterSet: activefilterset,
							supportedsievestructure: supportedsievestructure,
							back: $route,
						},
					}"
					:exact="true"
					:title="rule.name"
					icon="icon-filter">
					<template slot="info">
						<label class="comment"> {{ rule.comment }} </label>
						<label class="description" />
					</template>
					<template slot="actions">
						<ActionButton icon="icon-settings" :title="t('mail', 'edit')">
							{{ t('mail', 'Edit this filter') }}
						</ActionButton>
						<ActionButton icon="icon-copy" :title="t('mail', 'copy')">
							{{ t('mail', 'Copy this filter') }}
						</ActionButton>
						<ActionButton icon="icon-paste" :title="t('mail', 'paste')">
							{{ t('mail', 'Paste this filter') }}
						</ActionButton>
						<ActionButton v-if="index != 0" :title="t('mail', 'up')" icon="icon-triangle-n">
							{{ t('mail', 'Move this filter Up') }}
						</ActionButton>
						<ActionButton v-if="true" :title="t('mail', 'down')" icon="icon-triangle-s">
							{{ t('mail', 'Move this filter down') }}
						</ActionButton>
						<ActionButton icon="icon-delete" :title="t('mail', 'delete')" @click="removeRule(index)">
							{{ t('mail', 'Delete this filter') }}
						</ActionButton>
					</template>
				</AppContentListEntry>
			</template>
		</ul>
	</div>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContentListEntry from './AppContentListEntry'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'

export default {
	name: 'SieveFilterNavigation',
	components: {
		ActionButton,
		AppContentListEntry,
		AppNavigationNew,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		activefilterset: {
			type: String,
			required: true,
		},
		filterrules: {
			type: Array,
			required: true,
		},
		supportedsievestructure: {
			type: Object,
			required: true,
		},
	},
	computed: {
		id() {
			return 'filter-navigation'
		},
	},
	methods: {
		addRule() {
			const rule = {
				index: -1,
				name: 'new Rule',
				parsedrule: { actions: [], conditions: { 'condition-verb': 'if', testlist: { tests: [] } } },
				rule: '',
				type: 'rule',
			}
			this.filterrules.push(rule)
		},
		removeRule(index) {
			this.filterrules.splice(index, 1)
		},
	},
}
</script>

<style lang="scss" scoped>
@import '@nextcloud/vue/src/assets/variables.scss';
#app-navigation.filter-navigation {
	left: $navigation-width;
	top: 300px;
}
</style>
