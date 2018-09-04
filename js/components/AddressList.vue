<template>
	<span>
		<template v-for="(entry, idx) in entries">
			<Address :key="entry.email"
					 :email="entry.email"
					 :label="entry.label"/><!--
			--><span
				v-if="idx+1 < entries.length">, </span>
		</template>
	</span>
</template>

<script>
	import Address from "./Address";

	export default {
		name: "AddressList",
		components: {Address},
		props: [
			'entries'
		]
	}
</script>
