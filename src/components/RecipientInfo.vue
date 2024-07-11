<template>
	<div>
		<h5>Recipient Info</h5>
		<p>Name: {{ recipient.name }}</p>
		<p>Email: {{ recipient.email }}</p>
	</div>
</template>

<script>
export default {
	props: {
		recipient: {
			type: Object,
			required: true,
		},
	},
}
</script>
