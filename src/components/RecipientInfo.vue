<template>
	<div class="recipient-info">
		<div v-if="recipientInfo.length === 1" class="recipient-single">
			<Avatar :user="recipientInfo[0].uid"
				:display-name="recipientInfo[0].displayName"
				:email="recipientInfo[0].email"
				:size="128"
				:disable-tooltip="true"
				:disable-menu="true" />
			<div class="recipient-details">
				<p>{{ recipientInfo[0].displayName }}</p>
				<p class="recipient-email">
					{{ recipientInfo[0].email }}
				</p>
			</div>
		</div>
		<div v-else class="recipient-multiple">
			<div class="recipient-list">
				<div v-for="(recipient, index) in recipientInfo"
					:key="recipient.uid"
					class="recipient-item">
					<Avatar :user="recipient.uid"
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
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import Avatar from './Avatar.vue'
import IconArrowUp from 'vue-material-design-icons/ArrowUp.vue'
import IconArrowDown from 'vue-material-design-icons/ArrowDown.vue'

export default {
	components: {
		Avatar,
		IconArrowUp,
		IconArrowDown,
	},
	props: {
		recipientInfo: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			expandedIndex: null,
		}
	},
	methods: {
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
