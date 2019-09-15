<template>
	<Content app-name="mail">
		<Navigation />
		<AppContent>
			<div id="sieve-app-content">
				<h2>{{ t("mail", "Filter Configuration") }}</h2>
				<tabs :options="{ useUrlFragment: false }">
					<tab v-for="accountID in $store.getters.sieveAccountList()"
						:key="accountID"
						:name="$store.getters.getAccount(accountID).emailAddress">

						<div id="server-container">
								{{ tls(accountID) }}{{ $store.getters.getAccount(accountID).sieveHost }}&nbsp;
								@&nbsp;{{ $store.getters.getAccount(accountID).sieveUser }}
						</div>

					<SieveFilterSets :accountID="accountID" />
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

	export default {
		name: 'SieveView',
		components: {
			Content,
			AppContent,
			Navigation,
			SieveFilterSets,
			Tab,
			Tabs,
		},
		methods: {
			tls (accountID) {
				if (this.$store.getters.getAccount(accountID).sieveSslMode === "tls") {
					return "tls://"
				} else {
					return ""
				}
			}
		},
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
		width: 20%;
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
	.flex-line {
		display: flex;
		align-items: center;
	}
	.flex-container {
		display: flex;
		align-items: flex-start;
	}
</style>
