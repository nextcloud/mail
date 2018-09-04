<template>
	<div class="app-content-list-item">
		<div class="app-content-list-item-line-one"
			 :title="from">
			{{from}}
		</div>
		<div class="app-content-list-item-line-two"
			 :title="subject">
			{{subject}}
		</div>
		<div class="app-content-list-item-details date">
			<Moment timestamp="1536048354000" />
		</div>
	</div>
</template>

<script>
	import Moment from "./Moment";
	export default {
		name: "MessageListItem",
		components: {Moment},
		props: [
			'from',
			'subject',
		]
	}
</script>
