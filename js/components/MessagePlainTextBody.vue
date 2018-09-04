<template>
	<div>
		{{ body }}
	</div>
</template>

<script>
	export default {
		name: "MessagePlainTextBody",
		props: [
			'body',
		]
	};
</script>
