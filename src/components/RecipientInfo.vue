<template>
	<div class="recipient-info">
		<!-- For a single recipient -->
		<div v-if="recipients.length === 1" class="recipient-single">
			<div class="recipient-header">
				<div class="recipient-avatar">
					<Avatar :user="recipients[0].uid"
						:display-name="recipients[0].displayName"
						:email="recipients[0].email"
						:size="64"
						:disable-tooltip="true"
						:url="recipients[0].photoUrl" />
				</div>
				<div class="recipient-details">
					<h6>{{ recipients[0].displayName }}</h6>
					<div ref="contactDetails0" />
				</div>
			</div>
		</div>

		<!-- For multiple recipients -->
		<div v-else class="recipient-multiple">
			<div v-for="(recipient, index) in recipients" :key="index" class="recipient-item">
				<div class="recipient-header">
					<Avatar :user="recipient.uid"
						:display-name="recipient.displayName"
						:email="recipient.email"
						:size="64"
						:disable-tooltip="true"
						:disable-menu="true" />
					<div class="expand-toggle" @click="toggleExpand(index)">
						<IconArrowUp v-if="isExpanded(index)" :size="16" />
						<IconArrowDown v-else :size="16" />
					</div>
				</div>
				<div class="recipient-list">
					<div :ref="`contactDetails${index}`" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import Avatar from './Avatar.vue'
import logger from '../logger.js'

export default {
	components: {
		Avatar,
		IconArrowDown,
		IconArrowUp,
	},
	props: {
		recipient: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			expandedRecipients: [],
			contactDetailsVms: [],
		}
	},
	computed: {
		...mapGetters(['composerMessage']),
		recipients() {
			return this.composerMessage.data.to
		},
	},
	watch: {
		recipients: {
			immediate: true,
			handler() {
				this.expandedRecipients = this.recipients.map(() => false)
			},
		},
	},
	async mounted() {
		const mountContactDetails = window.OCA?.Contacts?.mountContactDetails
		if (mountContactDetails) {
			for (const [i, recipient] of this.recipients.entries()) {
				const el = this.$refs[`contactDetails${i}`]
				try {
					this.contactDetailsVms.push(await mountContactDetails(el, recipient.email))
				} catch (error) {
					logger.error(`Failed to mount contact details: ${error}`, {
						error,
						recipient,
					})
					throw error
				}
			}
		}
	},
	async beforeDestroy() {
		for (const vm of this.contactDetailsVms) {
			vm.$destroy()
		}
	},
	methods: {
		toggleExpand(index) {
			this.$set(this.expandedRecipients, index, !this.expandedRecipients[index])
		},
		isExpanded(index) {
			return this.expandedRecipients[index]
		},
	},
}
</script>

<style scoped lang="scss">
.recipient-info {
	display: flex;
	justify-content: center;
	align-items: center;
	width: 100%;
}

.recipient-single {
	width: 400px;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.recipient-header {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.recipient-avatar {
	margin-bottom: 10px;
}

.recipient-details {
	max-width: 100%;
}

.recipient-multiple {
	margin-top: 10px;
}

.recipient-item {
	margin-bottom: 10px;
}

.recipient-item-details {
	margin-left: 10px;
	flex-grow: 1;
}

.expand-toggle {
	cursor: pointer;
}

.recipient-email {
	margin-top: 5px;
}

.recipient-list {
	padding-top: 10px;
	margin-top: 1rem;
}
.recipient-header {
	display: flex;
	gap: 1rem;
	margin-bottom: 1rem;
}

span {
	color: #666;
	font-size: 0.9rem;
	margin-bottom: 1rem;
	display: block;
}

.expand-toggle {
	margin-left: auto;
	cursor: pointer;
}
</style>
