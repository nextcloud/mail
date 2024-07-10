/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

<template>
	<div ref="contactDetails" />
</template>

<script>
import logger from '../logger.js'

export default {
	props: {
		email: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			vm: null,
		}
	},
	async mounted() {
		const mountContactDetails = window.OCA?.Contacts?.mountContactDetails
		if (mountContactDetails) {
			try {
				this.vm = await mountContactDetails(this.$refs.contactDetails, this.email)
			} catch (error) {
				logger.error(`Failed to mount contact details: ${error}`)
			}
		}
	},
	async beforeDestroy() {
		if (this.vm) {
			this.vm.$destroy()
		}
	},
}
</script>
