<template>
	<div class="recipient-info">
		<!-- <div v-if="recipient.length === 1" class="recipient-single">
			<Avatar :user="recipient.uid"
				:display-name="recipient.displayName"
				:email="recipient.email"
				:size="128"
				:disable-tooltip="true"
				:url="photoUrl" />
			<div class="recipient-details">
				<p>{{ recipient.displayName }}</p>
				<p class="recipient-email">
					{{ recipient.email }}
				</p>
			</div>
		</div> -->
		<div class="recipient-multiple">
			<div class="recipient-list">
				<div v-for="(vcard, index) in recipientsVCards"
					:key="index"
					class="recipient-item">
					<RecipientDetails :contacts="vcard.data" :reload-bus="reloadBus" />
					<!--					<Avatar :user="recipient.uid"
						:display-name="recipient.displayName"
						:email="recipient.email"
						:size="64"
						:disable-tooltip="true"
						:disable-menu="true" />
					<div class="recipient-item-details">
						<p>{{ recipient.displayName }}</p>
						<p class="recipient-email">
							{{ recipient.email }}
							<span class="expand-toggle" @click="toggleExpand(index)">
								<IconArrowUp v-if="expandedIndex === index" size="16" />
								<IconArrowDown v-else size="16" />
							</span>
						</p>
						<div v-if="expandedIndex === index" class="expanded-recipient-details">
							<p>{{ recipient.displayName }}</p>
							<p class="recipient-email">
								{{ recipient.email }}
							</p>
						</div>
					</div>-->
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

export default {
	components: {
		RecipientDetails,
	},
	props: {
		recipient: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			expandedIndex: null,
			photoUrl: undefined,
			recipientsVCards: {},
			loadingParticipants: {},
			reloadBus: mitt(),
		}
	},
	computed: {
		...mapGetters(['getAddressBooks', 'composerMessage']),
		/**
		 * @return {{ label: string, email: string }[]}
		 */
		recipients() {
			return this.composerMessage.data.to
		},

		recipientsVCardsList() {
			return Object.values(this.recipientsVCards).filter(Boolean)
		},
	},
	watch: {
		async recipients() {
			// New 'to' list
			const newEmails = this.recipients.map(recipient => recipient.email)
			// Emails we had
			const oldEmails = Object.keys(this.recipientsVCards)
			// Emails that were added
			const addedEmails = newEmails.filter(email => !oldEmails.includes(email))
			// Emails that were removed
			const removedEmails = oldEmails.filter(email => !newEmails.includes(email))

			// Delete removed recipients
			for (const email of removedEmails) {
				this.$delete(this.recipientsVCards, email)
			}

			await Promise.all(addedEmails.map(email => this.fetchRecipientInfo(email)))
		},
	},
	methods: {
		async fetchRecipientInfo(email) {
			if (this.loadingParticipants[email]) {
				// is already loading
				return
			}

			// loading
			this.$set(this.loadingParticipants, email, true)

			// Fetch the cards from all the address books
			const result = await Promise.all(this.getAddressBooks.map(async addressBook => {
				return await addressBook.addressbookQuery([{
					name: [NS.IETF_CARDDAV, 'comp-filter'],
					attributes: [['name', 'VCARD']],
					children: [{
						name: [NS.IETF_CARDDAV, 'prop-filter'],
						attributes: [['name', 'EMAIL']],
						children: [{
							name: [NS.IETF_CALDAV, 'text-match'],
							value: email,
						}],
					}],
				}])
			}))

			const vcards = result.flat()

			// Let's assume we have no more than 1 card for a recipient
			const vcard = vcards[0]

			if (vcard) {
				// Save the card
				this.$set(this.recipientsVCards, email, vcard)
			}

			// Loading is finished
			this.$delete(this.loadingParticipants, email)

			console.info(this.recipientsVCards)
		},
		toggleExpand(index) {
			this.expandedIndex = this.expandedIndex === index ? null : index
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

.recipient-single {
	display: flex;
	align-items: center;
}

.recipient-multiple {
	margin-top: 10px;
}

.recipient-list {
	display: flex;
	flex-direction: column;
}

.recipient-item {
	display: flex;
	align-items: center;
	margin-right: 20px;
	margin-bottom: 10px;
}

.recipient-item-details {
	margin-left: 10px;
}

.expand-toggle {
	cursor: pointer;
	margin-top: 10px;
}

</style>
