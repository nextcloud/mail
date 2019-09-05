<template>
  <Content app-name="mail">
    <Navigation />
    <Sieve />
  </Content>
</template>

<script>
  import Content from 'nextcloud-vue/dist/Components/Content'

  import Navigation from '../components/Navigation'
  import Sieve from '../components/Sieve'

  export default {
      name: 'SieveView',
      components: {
	  Content,
	  Navigation,
	  Sieve,
      },
      computed: {
      },
      methods: {
      },
  }
</script>
