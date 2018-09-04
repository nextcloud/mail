<template>
	<span :title="email"
		  v-tooltip.bottom="label">{{ label }}</span>
</template>

<script>
	export default {
		name: "Address",
		props: [
			'email',
			'label',
		]
	}
</script>
