<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="wrapper">
		<NcEmptyContent
			v-if="hint"
			class="empty-content"
			:name="hint">
			<template #icon>
				<NcLoadingIcon />
			</template>
			<template #description>
				<transition name="fade">
					<em v-if="slowHint && slow">{{ slowHint }}</em>
				</transition>
			</template>
		</NcEmptyContent>
		<NcLoadingIcon v-else class="container" />
	</div>
</template>

<script>
import { NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
export default {
	name: 'Loading',
	components: {
		NcLoadingIcon,
		NcEmptyContent,
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

.empty-content {
	height: 100%;
	display: flex;
}

.wrapper {
	display: flex;
	justify-content: center;
	flex-direction: column;
	flex: 1 auto;
	align-items: center;
	height: 100%;
}
</style>
