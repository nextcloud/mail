<template>
	<div class="recipient-info">
		<!-- For a single recipient -->
		<div v-if="recipients.length === 1" class="recipient-single">
			<div class="recipient-header">
				<div class="recipient-avatar">
					<Avatar :display-name="recipients[0].label"
						:email="recipients[0].email"
						:size="64"
						:disable-tooltip="true"
						:disable-menu="true"
						:url="recipients[0].photoUrl" />
				</div>
				<div class="recipient-details">
					<DisplayContactDetails :email="recipients[0].email" />
				</div>
			</div>
		</div>

		<!-- For multiple recipients -->
		<div v-else class="recipient-multiple">
			<div v-for="(recipient, index) in recipients" :key="index" class="recipient-item">
				<div class="recipient-header">
					<Avatar :display-name="recipient.label"
						:email="recipient.email"
						:size="64"
						:disable-tooltip="true"
						:disable-menu="true" />
					<div v-if="!expandedRecipients[index]" class="recipient-name">
						<h6>{{ recipient.email }}</h6>
					</div>
					<div class="expand-toggle" @click="toggleExpand(index)">
						<IconArrowUp v-if="isExpanded(index)" :size="16" />
						<IconArrowDown v-else :size="16" />
					</div>
				</div>
				<div v-show="expandedRecipients[index]" class="recipient-list">
					<DisplayContactDetails :email="recipient.email" />
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
import DisplayContactDetails from './DisplayContactDetails.vue'

export default {
	components: {
		Avatar,
		IconArrowDown,
		IconArrowUp,
		DisplayContactDetails,
	},
	data() {
		return {
			expandedRecipients: [],
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
}

.recipient-avatar {
	margin-top: 10px;
}

.recipient-details {
	max-width: 100%;
}

.recipient-multiple {
	margin-top: 10px;
	display: flex;
	flex-direction: column;
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
	flex-direction: column;
	align-items: center;
}

span {
	color: #666;
	font-size: 0.9rem;
	margin-bottom: 1rem;
	display: block;
}

</style>
