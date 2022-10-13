<template>
	<div id="emptycontent">
		<slot> {{ t('mail', 'Messages marked as important will show up here') }} </slot>
	</div>
</template>

<script>
export default {
	name: 'EmptyMailboxSection',
}
</script>
<style scoped lang="scss">
#emptycontent {
	margin-top: 10px;
	color: var(--color-text-maxcontrast);
	font-size: inherit;
	text-align: left;
	padding-left: 16px;
}
</style>
