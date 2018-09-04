<template>
	<div id="app-content">
		<div id="app-content-wrapper">
			<MessageList/>
			<Message/>
		</div>
	</div>
</template>

<script>
	import Message from "./Message";
	import MessageList from "./MessageList";

	export default {
		name: "FolderContent",
		components: {
			Message,
			MessageList
		}
	}
</script>
