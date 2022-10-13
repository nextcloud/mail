<template>
	<div class="wrapper">
		<div v-if="hint" class="emptycontent">
			<IconLoading :size="20" />
			<h2>{{ hint }}</h2>
			<transition name="fade">
				<em v-if="slowHint && slow">{{ slowHint }}</em>
			</transition>
		</div>
		<IconLoading v-else class="container" />
	</div>
</template>

<script>
import { NcLoadingIcon as IconLoading } from '@nextcloud/vue'
export default {
	name: 'Loading',
	components: {
		IconLoading,
	},
	props: {
		hint: {
			type: String,
			default: '',
			required: false,
		},
		slowHint: {
			type: String,
			default: '',
			required: false,
		},
	},
	data() {
		return {
			slow: false,
			slowTimer: undefined,
		}
	},
	mounted() {
		clearTimeout(this.slowTimer)

		this.slowTimer = setTimeout(() => {
			this.slow = true
		}, 3500)
	},
	beforeDestroy() {
		clearTimeout(this.slowTimer)
	},
}
</script>

<style lang="scss" scoped>
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
	opacity: 0;
}

.wrapper {
	display: flex;
	justify-content: space-around;
	flex-direction: column;
	flex: 1 auto;
}
</style>
