<template>
	<div class="wrapper">
		<EmptyContent v-if="hint"
			:title="hint">
			<template #icon>
				<IconLoading :size="20" />
			</template>
			<transition name="fade">
				<em v-if="slowHint && slow">{{ slowHint }}</em>
			</transition>
		</EmptyContent>
		<IconLoading v-else class="container" />
	</div>
</template>

<script>
import { NcLoadingIcon as IconLoading, NcEmptyContent as EmptyContent } from '@nextcloud/vue'
export default {
	name: 'Loading',
	components: {
		IconLoading,
		EmptyContent,
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
