<template>
	<div class="app-content-list-item">
		<div class="app-content-list-item-icon">
			<Avatar :label="from"/>
		</div>
		<div class="app-content-list-item-line-one"
			 :title="from">
			{{from}}
		</div>
		<div class="app-content-list-item-line-two"
			 :title="subject">
			{{subject}}
		</div>
		<div class="app-content-list-item-details date">
			<Moment timestamp="1536048354000"/>
		</div>
	</div>
</template>

<script>
	import Avatar from "./Avatar";
	import Moment from "./Moment";

	export default {
		name: "MessageListItem",
		components: {
			Avatar,
			Moment
		},
		props: [
			'from',
			'subject',
		]
	}
</script>
