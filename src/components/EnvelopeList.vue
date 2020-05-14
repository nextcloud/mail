<template>
	<div>
		<transition-group name="list">
			<div id="list-refreshing" key="loading" class="icon-loading-small" :class="{refreshing: refreshing}" />
			<Envelope
				v-for="env in envelopesToShow"
				:key="env.uid"
				:data="env"
				:folder="folder"
				@delete="$emit('delete', env.uid)"
			/>
			<div
				v-if="collapsible && envelopes.length > collapseThreshold"
				:key="'list-collapse-' + this.searchQuery"
				class="collapse-expand"
				@click="$emit('update:collapsed', !collapsed)"
			>
				<template v-if="collapsed">{{ t('mail', 'Show all {nr} messages', {nr: envelopes.length}) }}</template>
				<template v-else>{{ t('mail', 'Collapse messages') }}</template>
			</div>
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
		searchQuery: {
			type: String,
			required: false,
			default: undefined,
		},
		refreshing: {
			type: Boolean,
			required: true,
		},
		loadingMore: {
			type: Boolean,
			required: true,
		},
		collapsible: {
			type: Boolean,
			required: false,
			default: false,
		},
		collapsed: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
			collapseThreshold: 5,
		}
	},
	computed: {
		envelopesToShow() {
			if (this.collapsible && this.collapsed) {
				return this.envelopes.slice(0, this.collapseThreshold)
			}
			return this.envelopes
		},
	},
}
</script>

<style lang="scss" scoped>
div {
	// So we can align the loading spinner in the Priority inbox
	position: relative;
}

.collapse-expand {
	text-align: center;
	margin-top: 10px;
	cursor: pointer;
	color: var(--color-text-maxcontrast);
}

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
	position: absolute;
	left: calc(50% - 8px);
	overflow: hidden;
	padding: 12px;
	background-color: var(--color-main-background);
	z-index: 1;
	border-radius: var(--border-radius-pill);
	border: 1px solid var(--color-border);
	top: -24px;
	opacity: 0;
	transition-property: top, opacity;
	transition-duration: 0.5s;
	transition-timing-function: ease-in-out;

	&.refreshing {
		top: 4px;
		opacity: 1;
	}
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
