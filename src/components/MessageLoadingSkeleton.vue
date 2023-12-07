<template>
	<div class="message-loading-skeleton">
		<div class="message-loading-skeleton__body">
			<div class="message-loading-skeleton__body__line-one">
					&nbsp;
			</div>
			<div class="message-loading-skeleton__body__line-two">
					&nbsp;
			</div>
			<div class="message-loading-skeleton__body__line-three">
					&nbsp;
			</div>
			<div class="message-loading-skeleton__body__line-four">
					&nbsp;
			</div>
			<div class="message-loading-skeleton__body__line-five">
					&nbsp;
			</div>
			<div class="message-loading-skeleton__body__line-six">
					&nbsp;
			</div>
		</div>
	</div>
</template>

<script>

export default {
	name: 'MessageLoadingSkeleton',
}
</script>

<style lang="scss" scoped>

/* skeleton */
.message-loading-skeleton {
	display: flex;
	flex-grow: 1;
	align-items: flex-start;
	padding: 8px;
	width: 100%;
	&__body {
		max-height: 500px;
		max-width: 1200px;
		flex: 1 1 100%;
		display: flex;
		flex-direction: column;
		margin-left: 50px;
		&__line-one,
		&__line-two,
		&__line-three,
		&__line-four,
		&__line-five,
		&__line-six {
			white-space: nowrap;
			height: 13px;
			background: linear-gradient(-45deg, var(--color-background-hover), var(--color-background-dark), var(--color-background-darker), var(--color-placeholder-light));
			background-size: 400% 400%;
			animation: gradient 3s ease-in infinite;
		}
		&__line-one {
			width: 10%;
			margin-bottom: 25px;
		}
		&__line-two {
			width: 80%;
			margin-bottom: 6px;
		}
		&__line-three {
			width: 50%;
			margin-bottom: 6px;
		}
		&__line-four {
			width: 70%;
			margin-bottom: 6px;
		}
		&__line-five {
			width: 40%;
			margin-bottom: 25px;
		}
		&__line-six {
			width: 30%;
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
