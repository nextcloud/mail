<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<MessageList />
			<div class="app-content-detail">
				todo: message details
			</div>
		</div>
	</div>
</template>

<script>
	import MessageList from "./MessageList";
	export default {
		name: "FolderContent",
		components: {MessageList}
	}
</script>
