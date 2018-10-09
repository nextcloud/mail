<template>
	<span class="live-relative-timestamp"
		  :data-timestamp="timestamp"
		  :title="title">{{ formatted }}</span>
</template>

<script>
	import { getLocale } from 'nextcloud-server/dist/l10n'
	import moment from 'moment';

	if (typeof OC !== 'undefined') {
		moment.locale(getLocale());
	}

	export default {
		name: "Moment",
		props: [
			'timestamp',
			'format'
		],
		computed: {
			title () {
				return moment.unix(this.timestamp / 1000).format(this.format || 'LLL');
			},
			formatted () {
				return moment.unix(this.timestamp / 1000).fromNow();
			}
		}
	}
</script>
