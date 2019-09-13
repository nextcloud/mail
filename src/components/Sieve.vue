<template>
  <AppContent>
    <div id="sieve-app-content">
      <h2>{{ t("mail", "Sieve Configuration") }}</h2>
      <tabs :options="{ useUrlFragment: false }" @clicked="tabClicked">
	<tab v-for="accountID in $store.getters.trueAccountList" :key="accountID" :name="$store.getters.getAccount(accountID).emailAddress">


	  <div class="flex-line" id="server-container">
	    <div style="width: 80px">
	      Server:
	    </div>

	    <div v-if="!serverEdit" style="width: auto">
	      <span v-if="$store.getters.getAccount(accountID).managesieveSTARTTLS">
		tls://
	      </span>
	      <span>
		{{ $store.getters.getAccount(accountID).managesieveHost }}:{{ $store.getters.getAccount(accountID).managesievePort }}
	      </span>
	    </div>
	    <div v-else class="flex-line" style="width: auto">
		<vSelect v-model="managesieveConnDetails.STARTTLS" :options="serverTLSOptions" :reduce="x => x.val"/>
	      <div>
		<input v-model="managesieveConnDetails.host" >:
		<input v-model.number="managesieveConnDetails.port" type="number">
	      </div>
	    </div>

	    <div v-if="!serverEdit">
	      <Actions>
		<ActionButton icon="icon-rename" @click="setServerEdit(accountID)">Edit</ActionButton>
	      </Actions>
	    </div>
	    <div v-else>
	      <Actions>
		<ActionButton icon="icon-confirm" @click="updateServer(accountID)">Edit</ActionButton>
	      </Actions>
	    </div>
	  </div>


	  <SieveFilterSets :accountID="accountID" />
	</tab>
      </tabs>
    </div>
  </AppContent>
</template>

<script>
  import AppContent from 'nextcloud-vue/dist/Components/AppContent'
  import Actions from 'nextcloud-vue/dist/Components/Actions'
  import ActionButton from 'nextcloud-vue/dist/Components/ActionButton'
  import {Tab, Tabs} from 'vue-tabs-component'
  import vSelect from 'vue-select'
  import Message from 'vue-m-message'
  import SieveFilterSets from './SieveFilterSets'

  import Logger from '../logger'

  export default {
      name: 'Sieve',
      components: {
	  AppContent,
	  Actions,
	  ActionButton,
	  Message,
	  vSelect,
	  Tab,
	  Tabs,
	  SieveFilterSets,
      },
      props: {
      },
      data() {
	  return {
	      serverEdit: false,
	      managesieveConnDetails: {},
	      serverTLSOptions: [
		  {
		      val: 1,
		      label: t("mail","use STARTTLS"),
		  },
		  {
		      val: 0,
		      label: t("mail","no STARTTLS"),
		  },
	      ],
	  }
      },
      computed: {
      },
      watch: {
      },
      methods: {
	  tabClicked (accountID) {
	      this.serverEdit = false;
          },
	  setServerEdit (accountID) {
	      this.managesieveConnDetails.host = this.$store.getters.getAccount(accountID).managesieveHost;
	      this.managesieveConnDetails.port = this.$store.getters.getAccount(accountID).managesievePort;
	      this.managesieveConnDetails.STARTTLS = this.$store.getters.getAccount(accountID).managesieveSTARTTLS;
	      this.serverEdit = true;
	  },
	  updateServer (accountID) {
	      this.managesieveConnDetails.accountID = accountID;
	      this.$store.commit("setManagesieveConnDetails", this.managesieveConnDetails);
	      this.serverEdit = false;
	  },
      },
  }
</script>

<style lang="scss">
  @import "vue-select/src/scss/vue-select.scss";

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
  .flip-list-move {
      transition: transform 0.5s;
  }
  .no-move {
      transition: transform 0s;
  }
  .ghost {
      opacity: 0.5;
      background: #c8ebfb;
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
  #server-container {
      padding: .5em 1em;
      color: dimgray;
      font-weight: bold;
      font-size: 16px;
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
