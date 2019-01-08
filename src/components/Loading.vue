<template>
	<div v-if="hint" class="emptycontent">
		<a class="icon-loading"></a>
		<h2>{{ hint }}</h2>
	</div>
	<div v-else class="container icon-loading"></div>
</template>

<script>
	export default {
		name: "Loading",
		props: {
			hint: String
		}
	}
</script>

<style scoped>

</style>