<template>
	<div>
		<div id="mail-content">
			{{ body }}
		</div>
		<div v-if="signature"
			 class="mail-signature">
			{{signature}}
		</div>
	</div>
</template>

<script>
	export default {
		name: "MessagePlainTextBody",
		props: [
			'body',
			'signature',
		]
	};
</script>
