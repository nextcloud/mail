<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<MessageList/>
			<slot/>
		</div>
	</div>
</template>

<script>
	import MessageList from "./MessageList";

	export default {
		name: "FolderContent",
		components: {
			MessageList
		}
	}
</script>
