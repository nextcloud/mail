<template>
	<span class="live-relative-timestamp"
		  :data-timestamp="timestamp"
		  :title="title">{{ formatted }}</span>
</template>

<script>
	import moment from 'moment';

	if (typeof OC !== 'undefined') {
		moment.locale(OC.getLocale());
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
