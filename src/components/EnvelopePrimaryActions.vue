<template>
	<ul class="list-item-content__actions--primary">
		<slot />
	</ul>
</template>

<script>
export default {
	name: 'EnvelopePrimaryActions',
}
</script>

<style>
.list-item-content__actions--primary {
	display: flex;
	justify-content: space-between;
}

.list-item-content__actions--primary .action--primary:first-child {
	padding-left: 5px;
}

.list-item-content__actions--primary .action--primary:last-child {
	padding-right: 5px;
}

.list-item-content__actions--primary .action--primary .action-button {
	align-items: center;
	flex-direction: column;
	padding: 10px 10px 0 10px;

	.material-design-icon {
		margin-bottom: -15px;
	}
}

.list-item-content__actions--primary .action--primary .action-button__text,
.list-item-content__actions--primary .action--primary .action-button__icon {
	height: auto;
}
</style>
