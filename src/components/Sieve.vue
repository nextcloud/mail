<template>
  <AppContent>
    <div>
      <tabs :options="{ useUrlFragment: false }" @clicked="tabClicked" @changed="tabChanged">
        <tab v-for="account in sieveAccounts" :key="account.id" :name="account.email">
          <p>{{ account.host }}:{{ account.port }}</p>
        </tab>
      </tabs>
    </div>
  </AppContent>
</template>

<script>
  import AppContent from 'nextcloud-vue/dist/Components/AppContent'
  import {Tab, Tabs} from 'vue-tabs-component'

  import Logger from '../logger'

  export default {
      name: 'Sieve',
      components: {
	  AppContent,
	  Tab,
	  Tabs,
      },
      props: {
      },
      data() {
	  return {
	      sieveAccounts: [ 
		  {
		      "id": 1,
		      "email": "foo@bar.org",
		      "host": "localhost",
		      "port": 4190,
		  },
		  {
		      "id": 2,
		      "email": "oof@rab.org",
		      "host": "imap.rab.org",
		      "port": 4191,
		  },
	      ]
	  }
      },
      computed: {
      },
      watch: {
      },
      methods: {
	  tabClicked (selectedTab) {
              console.log('Current tab re-clicked:' + selectedTab.tab.name);
          },
          tabChanged (selectedTab) {
              console.log('Tab changed to:' + selectedTab.tab.name);
          },
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
  color: inherit;
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
    padding: 4em 2em;
  }
}
</style>
