<template>
	<span class="live-relative-timestamp" :data-timestamp="timestamp * 1000" :title="title">{{ formatted }}</span>
</template>

<script>
import {getLocale} from 'nextcloud-server/dist/l10n'
import moment from 'moment'

if (typeof OC !== 'undefined') {
	moment.locale(getLocale())
}

export default {
	name: 'Moment',
	props: {
		timestamp: {
			type: Number,
			required: true,
		},
		format: {
			type: String,
			default: 'LLL',
		},
	},
	computed: {
		title() {
			return moment.unix(this.timestamp).format(this.format)
		},
		formatted() {
			return moment.unix(this.timestamp).fromNow()
		},
	},
}
</script>
