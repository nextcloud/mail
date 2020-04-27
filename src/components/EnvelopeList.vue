<template>
	<div>
		<transition-group name="list">
			<div id="list-refreshing" key="loading" class="icon-loading-small" :class="{refreshing: refreshing}" />
			<Envelope
				v-for="env in envelopes"
				:key="env.uid"
				:data="env"
				:folder="folder"
				@delete="$emit('delete', env.uid)"
			/>
			<div id="load-more-mail-messages" key="loadingMore" :class="{'icon-loading-small': loadingMore}" />
		</transition-group>
	</div>
</template>

<script>
import Envelope from './Envelope'

export default {
	name: 'EnvelopeList',
	components: {
		Envelope,
	},
	props: {
		account: {
			type: Object,
			required: true,
		},
		folder: {
			type: Object,
			required: true,
		},
		envelopes: {
			type: Array,
			required: true,
		},
		refreshing: {
			type: Boolean,
			required: true,
		},
		loadingMore: {
			type: Boolean,
			required: true,
		},
	},
}
</script>

<style scoped>
#load-more-mail-messages {
	margin: 10px auto;
	padding: 10px;
	margin-top: 50px;
	margin-bottom: 50px;
}

/* TODO: put this in core icons.css as general rule for buttons with icons */
#load-more-mail-messages.icon-loading-small {
	padding-left: 32px;
	background-position: 9px center;
}

#list-refreshing {
	overflow-y: hidden;
	min-height: 0;
	transition-property: all;
	transition-duration: 0.5s;
	transition-timing-function: ease-in-out;
}

#list-refreshing.refreshing {
	min-height: 32px;
}

.list-enter-active,
.list-leave-active {
	transition: all var(--animation-quick);
}

.list-enter,
.list-leave-to {
	opacity: 0;
	height: 0px;
	transform: scaleY(0);
}
</style>
