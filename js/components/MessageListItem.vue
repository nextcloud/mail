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
				<span class="modified live-relative-timestamp"
					  data-timestamp="1534968683000"
					  title="22. August 2018 22:11">x seconds ago</span>
		</div>
	</div>
</template>

<script>
	export default {
		name: "MessageListItem",
		props: [
			'from',
			'subject',
		]
	}
</script>
