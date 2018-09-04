<template>
	<span :title="email"
		  v-tooltip="tooltipData">{{ label }}</span>
</template>

<script>
	export default {
		name: "Address",
		props: [
			'email',
			'label',
		],
		data () {
			return {
				tooltipData: {
					placement: 'bottom',
				}
			};
		}
	}
</script>
