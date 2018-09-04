<template>
	<span :title="email"
		  v-tooltip="{ content: 'label', placement: 'bottom' }">{{ label }}</span>
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
