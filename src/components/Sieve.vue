<template>
  <AppContent>
    <div id="sieve-app-content">
      <h2>{{ t("mail", "Sieve Configuration") }}</h2>
      <tabs :options="{ useUrlFragment: false }" @clicked="tabClicked" @changed="tabChanged">
	<tab v-for="accountID in trueAccountList" :key="accountID" :name="getAccount(accountID).emailAddress">


	  <div class="flex-line" id="server-container">
	    <div style="width: 80px">
	      Server:
	    </div>

	    <div v-if="!serverEdit" style="width: auto">
	      <span v-if="getAccount(accountID).managesieveSTARTTLS">
		tls://
	      </span>
	      <span>
		{{ getAccount(accountID).managesieveHost }}:{{ getAccount(accountID).managesievePort }}
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


	  <div class="main-container flex-container">
	    <div class="filtersets-container">
	      <draggable
		group="filterSets"
		class="list-group"
		tag="ul"
		v-model="filterSets"
		v-bind="dragOptions"
		@start="isDragging = true"
		@end="isDragging = false"
		@choose="selFilterSet($event.oldIndex-2)"
		>
		<button slot="header" @click="addFilterSet">{{ t("mail", "Add") }}</button>
		<button slot="header" @click="rmFilterSet">{{ t("mail", "Remove") }}</button>
		<div
		  v-for="filterSet in filterSets"
		  :class="setClassOnSelect(filterSet.id, -1)"
		  :key="filterSet.id"
		  >
		  <div class="flex-line">
		    <div v-if="!filterSet.nameEdit" class="list-text">
		      <div>{{ filterSet.name }}</div>
		    </div>
		    <div v-else class="list-text">
		      <input style="margin: 0;" v-model="filterSet.name"></input>
		    </div>
		    <div>
		      <Actions v-if="!filterSet.nameEdit">
			<ActionButton icon="icon-rename" @click="filterSet.nameEdit = true">Edit</ActionButton>
		      </Actions>
		      <Actions v-else>
			<ActionButton icon="icon-confirm" @click="filterSet.nameEdit = false">Edit</ActionButton>
		      </Actions>
		    </div>
		  </div>
		</div>
	      </draggable>
	    </div>

	    <div class="filter-container">
	      <draggable
		group="filters"
		class="list-group"
		tag="ul"
		v-model="filterSets.find(x => x.id === selFilterSetID).filters"
		v-bind="dragOptions"
		@start="isDragging = true"
		@end="isDragging = false"
		@choose="selFilter($event.oldIndex-2)"
		>
		<button slot="header" @click="addFilter">{{ t("mail", "Add") }}</button>
		<button slot="header" @click="rmFilter">{{ t("mail", "Remove") }}</button>
		<div
		  v-for="filter in filterSets.find(x => x.id === selFilterSetID).filters"
		  :class="setClassOnSelect(selFilterSetID, filter.id)"
		  :key="filter.id"
		  >
		  <div class="flex-horizontal"> 
		    <div class="flex-line">
		      <div v-if="!filter.expanded" class="list-text">
			<div>{{ filter.name }}</div>
		      </div>
		      <div v-else class="list-text">
			<input style="margin: 0;" v-model="filter.name"></input>
		      </div>
		      <div>
			<Actions v-if="!filter.expanded">
			  <ActionButton icon="icon-rename" @click="filter.expanded = true">Edit</ActionButton>
			</Actions>
			<Actions v-else>
			  <ActionButton icon="icon-confirm" @click="filter.expanded = false">Edit</ActionButton>
			</Actions>
		      </div>
		    </div>
		    <div v-if="filter.expanded"> Hello World!! </div>
		  </div>
		</div>
	      </draggable>
	    </div>
	  </div>
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
  import draggable from 'vuedraggable'
  import vSelect from 'vue-select'
  import Message from 'vue-m-message'
  import {fetchAll as fetchAllAccounts} from '../service/AccountService'
  import { mapGetters, mapMutations } from 'vuex'


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
	  draggable,
      },
      props: {
      },
      data() {
	  return {
	      filterSets: [
		  {
		      "id": 0,
		      "name": "Filter #2",
		      "nameEdit": false,
		      "filters": [
			  {
			      "id": 1,
			      "expanded": false,
			      "name": "test 0.1",
			  },
			  {
			      "id": 2,
			      "expanded": false,
			      "name": "test 0.2",
			  },
		      ],
		  },
		  {
		      "id": 1,
		      "name": "Filter #2_1",
		      "nameEdit": false,
		      "filters": [
			  {
			      "id": 0,
			      "expanded": false,
			      "name": "test 1.0",
			  },
			  {
			      "id": 1,
			      "expanded": false,
			      "name": "test 1.1",
			  },
			  {
			      "id": 2,
			      "expanded": false,
			      "name": "test 1.2",
			  },
		      ],
		  },
	      ],
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
	      selFilterSetID: 0,
	      selFilterID: -1,
	  }
      },
      computed: {
	  ...mapGetters([
	      "trueAccountList",
	      "getAccount",
	  ]),
	  ...mapMutations([
	      "setManagesieveConnDetails",
	  ]),
	  dragOptions() {
	      return {
		  animation: 0,
		  group: "description",
		  disabled: false,
		  ghostClass: "ghost"
	      };
	  },
      },
      watch: {
      },
      methods: {
	  tabClicked (selectedTab) {
              console.log('Current tab re-clicked:' + selectedTab.tab.name);
          },
          tabChanged (selectedTab) {
	      this.serverEdit = false;
              console.log('Tab changed to:' + selectedTab.tab.name);
          },
	  setServerEdit (accountID) {
	      this.managesieveConnDetails.host = this.getAccount(accountID).managesieveHost;
	      this.managesieveConnDetails.port = this.getAccount(accountID).managesievePort;
	      this.managesieveConnDetails.STARTTLS = this.getAccount(accountID).managesieveSTARTTLS;
	      this.serverEdit = true;
	  },
	  updateServer (accountID) {
	      this.managesieveConnDetails.accountID = accountID;
	      console.log(this.managesieveConnDetails);
	      this.setManagesieveConnDetails(this.managesieveConnDetails);
	      this.serverEdit = false;
	  },
	  setClassOnSelect(filterSetID, filterID) {
	      if (filterSetID === this.selFilterSetID && 
		  (filterID === this.selFilterID || filterID === -1) ) {
		  return "list-group-item list-group-item-a"
	      } else {
		  return "list-group-item"
	      }
	  },
	  selFilterSet (index) {
	      if (index >= 0) { // ignore buttons
		  var oldFilterSet = this.filterSets.find(x => x.id === this.selFilterSetID);
		  this.selFilterID = -1;
		  this.filterSets[index].class = "list-group-item list-group-item-a";
		  this.selFilterSetID = this.filterSets[index].id;
	      }
	      console.log(fetchAllAccounts());
	  },
	  selFilter (index) {
	      if (index >= 0) { // ignore buttons
		  var thisFilterSet = this.filterSets.find(x => x.id === this.selFilterSetID);
		  if (this.selFilterID !== -1) { // dont unselect if none selected
		      thisFilterSet.filters.find(x => x.id === this.selFilterID).class = "list-group-item";
		  }
		  this.selFilterID = thisFilterSet.filters[index].id;
	      }
	  },

	  uniqueFilterSetName(name) {
	      var nameIndex = 1;
	      if (this.filterSets.find(x => x.name === name) === undefined) {
		  console.log(this.filterSets.find(x => x.id === name));
		  return name;
	      } else {
		  while (this.filterSets.find(x => x.name === name+"_"+nameIndex) !== undefined) {
		      nameIndex += 1;
		  }
		  return name + "_" + nameIndex;
	      }
	  },
	  addFilterSet () {
	      var newID = 0;
	      var newName = "Filter #";
	      while (this.filterSets.find(x => x.id === newID) !== undefined) {
		  newID += 1;
	      }
	      newName = this.uniqueFilterSetName(newName+newID);
	      this.filterSets.push( {
		  "id": newID,
		  "name": newName,
		  "nameEdit": false,
		  "filters": [],
	      } );
	  },
	  addFilter () {
	      var newID = 0;
	      var thisFilterSet = this.filterSets.find(x => x.id === this.selFilterSetID);
	      while (thisFilterSet.filters.find(x => x.id === newID) !== undefined) {
		  newID += 1;
	      }
	      thisFilterSet.filters.push( {
		  "id": newID,
		  "name": "empty",
		  "expanded": false,
	      } );
	  },
	  rmFilterSet() {
	      if (this.filterSets.length > 1) { 
		  var dropIndex = this.filterSets.findIndex(x => x.id === this.selFilterSetID);
		  if (dropIndex === 0) {
		      this.selFilterSet(1);
		  } else {
		      this.selFilterSet(0);
		  }
		  this.filterSets.splice(dropIndex,1);
	      } else {
		  Message.warning({
		      "type": "warning",
		      "message": t("mail", "At least one filter set must exist."),
		      "position": "bottom-center",
		  });
	      }
	  },
	  rmFilter() {
	      var thisFilterSet = this.filterSets.find(x => x.id === this.selFilterSetID);
	      if (this.selFilterID === -1) {
		  Message.warning({
		      "type": "warning",
		      "message": t("mail", "No filter selected."),
		      "position": "bottom-center",
		  });
	      } else { 
		  var dropIndex = thisFilterSet.filters.findIndex(x => x.id === this.selFilterID);
		  if (thisFilterSet.filters.length === 1) {
		      thisFilterSet.filters.splice(dropIndex,1);
		      this.selFilterID = -1;
		  } else {
		      if (dropIndex === thisFilterSet.filters.length-1) {
			  this.selFilter(dropIndex-1);
		      } else {
			  this.selFilter(dropIndex+1);
		      }
		  thisFilterSet.filters.splice(dropIndex,1);
		  }
	      }
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
