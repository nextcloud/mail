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
					<RecipientDetails :contact="recipientsVCards[recipients[0].email]" :reload-bus="reloadBus" />
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
						<IconArrowUp v-if="isExpanded(index)" size="16" />
						<IconArrowDown v-else size="16" />
					</div>
				</div>
				<div class="recipient-list">
					<RecipientDetails v-if="isExpanded(index)" :contact="recipientsVCards[recipient.email]" :reload-bus="reloadBus" />
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import RecipientDetails from '../nextcloud-contacts/RecipientDetails.vue'
import { mapGetters } from 'vuex'
import { namespaces as NS } from '@nextcloud/cdav-library'
import mitt from 'mitt'
import Contact from '../nextcloud-contacts/contact.js'
import '../css/RecipientProperties.scss'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import Avatar from './Avatar.vue'

export default {
	components: {
		Avatar,
		RecipientDetails,
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
			recipientsVCards: {},
			loadingParticipants: {},
			reloadBus: mitt(),
		}
	},
	computed: {
		...mapGetters(['getAddressBooks', 'composerMessage']),
		recipients() {
			return this.composerMessage.data.to
		},
	},
	watch: {
		recipients: {
			immediate: true,
			handler() {
				this.expandedRecipients = this.recipients.map(() => false)
				this.loadRecipientInfo()
			},
		},
	},
	methods: {
		async loadRecipientInfo() {
			const newEmails = this.recipients.map(r => r.email)
			await Promise.all(newEmails.map(email => this.fetchRecipientInfo(email)))
		},
		async fetchRecipientInfo(email) {
			if (this.loadingParticipants[email]) return

			this.$set(this.loadingParticipants, email, true)
			const result = await Promise.all(this.getAddressBooks.map(async (addressBook) => [
				addressBook,
				await addressBook.addressbookQuery([{
					name: [NS.IETF_CARDDAV, 'prop-filter'],
					attributes: [['name', 'EMAIL']],
					children: [{
						name: [NS.IETF_CALDAV, 'text-match'],
						value: email,
					}],
				}]),
			]))
			const contacts = result.flatMap(([addressBook, vcards]) =>
				vcards.map((vcard) => new Contact(vcard.data, addressBook)),
			)
			const contact = contacts.find(contact => contact.email === email)
			if (contact) {
				this.$set(this.recipientsVCards, email, contact)
			}
			this.$delete(this.loadingParticipants, email)
		},
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
	flex-direction: column;
	padding: 10px;
}

.recipient-single,
.recipient-item {
	display: flex;
	flex-direction: column;
	margin-bottom: 10px;
}

.recipient-multiple {
	margin-top: 10px;
}

.recipient-item {
	margin-bottom: 10px;
}

.recipient-header {
	display: flex;
	align-items: center;
	gap: 1rem;
	margin-bottom: 1rem;
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
	align-items: flex-start;
	gap: 1rem;
	margin-bottom: 1rem;
}

.recipient-avatar {
	flex-shrink: 0;
}

.recipient-details {
	display: flex;
	flex-direction: column;
	justify-content: center;
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
