<template>
	<div>
		<div v-for="i in numberOfLines" :key="i" class="item-list__entry">
			<div
				class="item-avatar" />
			<div class="item__details">
				<h3>&nbsp;</h3>
				<p class="message">
					&nbsp;
				</p>
				<p class="message-preview">
					&nbsp;
				</p>
			</div>
		</div>
	</div>
</template>

<script>

export default {
	name: 'LoadingSkeleton',
	props: {
		numberOfLines: {
			type: Number,
			default: 10,
		},
		withAvatar: {
			type: Boolean,
			default: true,
		},
	},
}
</script>

<style lang="scss" scoped>

/* skeleton */
.item-list__entry {
	display: flex;
	align-items: flex-start;
	padding: 8px;
	.item-avatar {
		position: relative;
		top: 4px;
		border-radius: 50px;
		width: 44px;
		height: 44px;
		background: linear-gradient(-45deg, var(--color-background-hover), var(--color-background-dark), var(--color-background-darker), var(--color-placeholder-light));
		background-size: 400% 400%;
		animation: gradient 3s ease-in infinite;
	}
	.item__details {
		padding-left: 8px;
		max-height: 70px;
		flex-grow: 1;
		display: flex;
		flex-direction: column;
		h3,
		.message.message-preview {
			white-space: nowrap;
			background-color: var(--color-background-hover);
		}
		h3 {
			font-size: 100%;
			margin: 0;
			background: linear-gradient(-45deg, var(--color-background-hover), var(--color-background-dark), var(--color-background-darker), var(--color-placeholder-light));
			background-size: 400% 400%;
			animation: gradient 3s ease-in infinite;
			width: 40%;
			height: 15px;
		}
		.message-preview {
			width: 100%;
			height: 15px;
			margin-top: 5px;
			background: linear-gradient(-45deg, var(--color-background-hover), var(--color-background-dark), var(--color-background-darker), var(--color-placeholder-light));
			background-size: 400% 400%;
			animation: gradient 3s ease-in infinite;
		}
		.message {
			width: 70%;
			height: 15px;
			margin-top: 5px;
			background: linear-gradient(-45deg, var(--color-background-hover), var(--color-background-dark), var(--color-background-darker), var(--color-placeholder-light));
			background-size: 400% 400%;
			animation: gradient 3s ease-in infinite;
		}
	}
	@keyframes gradient {
		0% {
			background-position: 0% 50%;
		}
		50% {
			background-position: 100% 50%;
		}
		100% {
			background-position: 0% 50%;
		}
	}
}

</style>
