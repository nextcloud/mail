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
	<Content app-name="mail">
		<Navigation />
		<AppContent>
			<div id="sieve-app-content">
				<h2>{{ t("mail", "Filter Configuration") }}</h2>
				<div v-if="$store.getters.sieveAccountList().length === 0">
					{{ t("mail", "No Filter Account found.") }}
				</div>
				<tabs v-else :options="{ useUrlFragment: false }"
					@changed="tabChanged"
					>
					<tab v-for="accountID in $store.getters.sieveAccountList()"
						:key="accountID"
						:id="accountID"
						:name="$store.getters.getAccount(accountID).emailAddress">

						<div id="server-container" class="flex-line">
							<div class="list-text">
								{{ $store.getters.getAccount(accountID).sieveUser }}&nbsp;
								@&nbsp;{{ tls(accountID) }}{{ $store.getters.getAccount(accountID).sieveHost }}
							</div>
							<div v-if="!$store.state.sieveLoading">
								<Actions>
									<ActionButton icon="icon-history" @click="$store.dispatch('fetchSieveScripts', accountID)">Reload</ActionButton>
								</Actions>
								<Actions>
									<ActionButton icon="icon-checkmark" @click="saveScripts(accountID)">Save</ActionButton>
								</Actions>
							</div>
							<div v-else>
								<Actions>
									<ActionButton disabled icon="icon-history">Reload</ActionButton>
								</Actions>
								<Actions>
									<ActionButton disabled icon="icon-checkmark"">Save</ActionButton>
								</Actions>
							</div>
						</div>
					<div v-if="$store.state.sieveLoading" class="main-container flex-container">
						<div class="loading-container">
							<div><ClipLoader :loading="true" color="dimgray" /></div>
							<div style="font-size: 17px;">Scripts loading...</div>
						</div>
					</div>
					<SieveFilterSets v-else :accountID="accountID" />
					</tab>
				</tabs>
			</div>
		</AppContent>
	</Content>
</template>

<script>
	import Content from 'nextcloud-vue/dist/Components/Content'
	import AppContent from 'nextcloud-vue/dist/Components/AppContent'
	import Navigation from '../components/Navigation'
	import SieveFilterSets from '../components/SieveFilterSets'
	import {Tab, Tabs} from 'vue-tabs-component'
	import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
	import Actions from 'nextcloud-vue/dist/Components/Actions'
	import { ClipLoader } from '@saeris/vue-spinners'

	export default {
		name: 'SieveView',
		components: {
			Actions,
			ActionButton,
			Content,
			AppContent,
			Navigation,
			SieveFilterSets,
			Tab,
			Tabs,
			ClipLoader,
		},
		methods: {
			tabChanged (selectedTab) {
				this.$store.dispatch("fetchSieveScripts", selectedTab.tab.id)
			},
			tls (accountID) {
				if (this.$store.getters.getAccount(accountID).sieveSslMode === "tls") {
					return "tls://"
				} else {
					return ""
				}
			},
		},
		/*mounted: function() {
			this.$store.dispatch("fetchSieveScripts", this.$store.getters.sieveAccountList()[0])
		}*/
	}
</script>

<style>

	.tabs-component {
		margin: 4em 0;
	}

	.tabs-component-tabs {
		border: solid 1px #ddd;
		border-radius: 6px;
		margin-bottom: 5px;
	}

	@media (min-width: 700px) {
		.tabs-component-tabs {
		border: 0;
		align-items: stretch;
		display: flex;
		justify-content: flex-start;
		margin-bottom: -1px;
		}
	}

	.tabs-component-tab {
		color: #999;
		font-size: 14px;
		font-weight: 600;
		margin-right: 0;
		list-style: none;
	}

	.tabs-component-tab:not(:last-child) {
		border-bottom: dotted 1px #ddd;
	}

	.tabs-component-tab:hover {
		color: #666;
	}

	.tabs-component-tab.is-active {
		color: #000;
	}

	.tabs-component-tab.is-disabled * {
		color: #cdcdcd;
		cursor: not-allowed !important;
	}

	@media (min-width: 700px) {
		.tabs-component-tab {
		background-color: #fff;
		border: solid 1px #ddd;
		border-radius: 3px 3px 0 0;
		margin-right: .5em;
		transform: translateY(2px);
		transition: transform .3s ease;
		}

		.tabs-component-tab.is-active {
		border-bottom: solid 1px #fff;
		z-index: 2;
		transform: translateY(0);
		}
	}

	.tabs-component-tab-a {
		align-items: center;
		color: var(--color-text-light);
		display: flex;
		padding: .75em 1em;
		text-decoration: none;
	}

	.tabs-component-panels {
		padding: 4em 0;
	}

	@media (min-width: 700px) {
		.tabs-component-panels {
		border-top-left-radius: 0;
		background-color: #fff;
		border: solid 1px #ddd;
		border-radius: 0 6px 6px 6px;
		box-shadow: 0 0 10px rgba(0, 0, 0, .05);
		padding: 0;
		display: flex;
		flex-direction: column;
		}
	}
	#sieve-app-content {
		padding: 2em;
	}
	#server-container {
		padding: .5em 1em;
		color: dimgray;
		font-weight: bold;
		font-size: 16px;
		text-align: center;
		height: 60px;
	}
	.list-group-item {
		color: var(--color-text-lighter);
		border: solid 1px lightgray;
		border-radius: 4px;
		width: auto;
		min-height: 30px;
		text-align: center;
		font-size: medium;
		margin: 5px 0px;
		overflow: hidden;
		background-color: whitesmoke;
	}
	.list-group-item i {
		cursor: pointer;
	}
	.list-group-item-a {
		background-color: lightgrey;
	}
	.list-text {
		flex: 1;
		text-align: left;
		padding: 5px;
		width: 138px;
		overflow-y: scroll;
	}
	.list-input {
		margin: 5px !important;
	}
	.main-container {
		width: 100%;
		background-color: whitesmoke;
		padding: 2px;
		border-top: solid 1px lightgray;
	}
	.filtersets-container {
		width: 225px;
		border: solid 1px lightgray;
		border-radius: 5px;
		background-color: white;
		padding: 5px;
		margin: 2px;
	}
	.filter-container {
		background-color: white;
		border: solid 1px lightgray;
		border-radius: 5px;
		padding: 5px;
		margin: 2px;
		flex: 1;
	}
	.loading-container {
		background-color: white;
		border: solid 1px lightgray;
		border-radius: 5px;
		padding: 5px;
		margin: 2px;
		width: 100%;
		display: flex;
		align-items: center;
		flex-direction: column;
	}
	.flex-line {
		display: flex;
		align-items: center;
	}
	.flex-container {
		display: flex;
		align-items: flex-start;
	}
	.filter-test-field {
		border: 2px groove;
		border-radius: 5px;
		padding: 4px;
		background-color: white;
		margin: 4px;
	}
	.sieve-button {
		margin: 0
	}
</style>
