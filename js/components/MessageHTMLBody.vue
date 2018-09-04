<template>
	<div id="mail-content">
		<div id="show-images-text">
			<!--{{ t 'The images have been blocked to protect your privacy.' }}-->
			<button id="show-images-button">
				<!--{{ t 'Show images from this	sender' }}-->
			</button>
		</div>
		<div class="icon-loading">
			<iframe :srcdoc="body"
					sandbox=""
					seamless>
			</iframe>
		</div>
	</div>
</template>

<script>
	export default {
		name: "MessageHTMLBody",
		props: [
			'body',
		]
	};
</script>
