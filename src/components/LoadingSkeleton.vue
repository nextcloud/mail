<template>
	<div>
		<div v-for="i in numberOfLines" :key="i" class="item-list__entry">
			<NcAvatar v-if="withAvatar" class="item-avatar" :size="44" />
			<div class="item__details">
				<h3>&nbsp;</h3>
				<p class="message">
					&nbsp;
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar'

export default {
	name: 'LoadingSkeleton',
	components: {
		NcAvatar,
	},
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
	margin-top: auto;
	margin-bottom: auto;
	background-color: var(--color-background-dark) !important;
}
.item__details {
	padding-left: 8px;
	max-height: 44px;
	flex-grow: 1;
	overflow: hidden;
	display: flex;
	flex-direction: column;
h3,
.message {
	white-space: nowrap;
	background-color: var(--color-background-dark);
}
h3 {
	font-size: 100%;
	margin: 0;
}
.message {
	width: 80%;
	height: 15px;
	margin-top: 5px;
		}
	}
}
</style>
