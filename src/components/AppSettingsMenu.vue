<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="app-settings">
		<NcAppSettingsDialog
			id="app-settings-dialog"
			:name="t('mail', 'Mail settings')"
			:show-navigation="true"
			:additional-trap-elements="trapElements"
			:legacy="false"
			:open.sync="showSettings">
			<NcAppSettingsSection id="general" :name="t('mail', 'General')">
				<NcButton
					variant="secondary"
					:aria-label="t('mail', 'Set as default mail app')"
					wide
					@click="registerProtocolHandler">
					{{ t('mail', 'Set as default mail app') }}
				</NcButton>

				<NcFormGroup :label="t('mail', 'Account settings')">
					<NcFormBox>
						<NcFormBoxButton
							v-for="account in accountsWithEmail"
							:key="account.id"
							:aria-label="t('mail', 'Account settings')"
							@click="openAccountSettings(account.id)">
							<template #icon>
								<IconArrow :size="20" />
							</template>
							{{ account.emailAddress }}
						</NcFormBoxButton>
						<NcButton
							v-if="allowNewMailAccounts"
							variant="secondary"
							to="/setup"
							:aria-label="t('mail', 'Add mail account')"
							wide>
							<template #icon>
								<IconAdd :size="20" />
							</template>
							{{ t('mail', 'Add mail account') }}
						</NcButton>
					</NcFormBox>
				</NcFormGroup>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="appearance" :name="t('mail', 'Appearance')">
				<NcFormBox>
					<NcFormBoxSwitch
						v-model="layoutMessageView"
						:label="t('mail', 'Show all messages in thread')"
						:description="t('mail', 'When off, only the selected message will be shown')" />
				</NcFormBox>
				<NcFormBox>
					<NcFormBoxSwitch
						v-model="sortFavorites"
						:label="t('mail', 'Sort favorites up')"
						:disabled="loadingSortFavorites"
						:description="t('mail', 'When on, favorite messages will be sorted to the top of folders')" />
				</NcFormBox>
				<NcRadioGroup v-model="layoutMode" :label="t('mail', 'Layout')">
					<NcRadioGroupButton :label="t('mail', 'Vertical split')" value="vertical-split">
						<template #icon>
							<VerticalSplit :size="20" />
						</template>
					</NcRadioGroupButton>
					<NcRadioGroupButton :label="t('mail', 'Horizontal split')" value="horizontal-split">
						<template #icon>
							<HorizontalSplit :size="20" />
						</template>
					</NcRadioGroupButton>
					<NcRadioGroupButton :label="t('mail', 'List')" value="no-split">
						<template #icon>
							<CompactMode :size="20" />
						</template>
					</NcRadioGroupButton>
				</NcRadioGroup>

				<NcRadioGroup :model-value="sortOrder" :label="t('mail', 'Sorting')" @update:modelValue="onSortByDate">
					<NcRadioGroupButton :label="t('mail', 'Newest first')" value="newest" />
					<NcRadioGroupButton :label="t('mail', 'Oldest first')" value="oldest" />
				</NcRadioGroup>

				<NcAppSettingsSection id="messages" name="Messages">
					<NcFormBox>
						<NcFormBoxSwitch
							v-model="useExternalAvatars"
							:disabled="loadingAvatarSettings"
							@update:modelValue="onToggleExternalAvatars">
							{{ t('mail', 'Avatars from Gravatar and favicons') }}
						</NcFormBoxSwitch>

						<NcFormBoxSwitch
							v-model="searchPriorityBody"
							:disabled="loadingPrioritySettings"
							@update:modelValue="onToggleSearchPriorityBody">
							{{ prioritySettingsText }}
						</NcFormBoxSwitch>
					</NcFormBox>

					<NcRadioGroup :model-value="useBottomReplies" :label="t('mail', 'Reply position')" @update:modelValue="onToggleButtonReplies">
						<NcRadioGroupButton :label="t('mail', 'Top')" :value="false" />
						<NcRadioGroupButton :label="t('mail', 'Bottom')" :value="true" />
					</NcRadioGroup>

					<NcFormGroup
						:label="t('mail', 'Text blocks')"
						:description="t('mail', 'Reusable pieces of text that can be inserted in messages')">
						<List
							:text-blocks="getMyTextBlocks()"
							@show-toolbar="handleShowToolbar" />
						<NcButton variant="secondary" wide @click="() => textBlockDialogOpen = true">
							<template #icon>
								<IconAdd :size="20" />
							</template>
							{{ t('mail', 'New text block') }}
						</NcButton>
						<template v-if="getSharedTextBlocks().length > 0">
							<h6>{{ t('mail', 'Shared with me') }}</h6>
							<List
								:text-blocks="getSharedTextBlocks()"
								:shared="true"
								@show-toolbar="handleShowToolbar" />
						</template>
					</NcFormGroup>
				</NcAppSettingsSection>

				<NcAppSettingsSection id="privacy" :name="t('mail', 'Privacy')">
					<NcFormBoxSwitch
						v-model="useDataCollection"
						:label="t('mail', 'Data collection')"
						:description="t('mail', 'Allow the app to collect and process data locally to adapt to your preferences')"
						@update:modelValue="onToggleCollectData" />

					<NcFormGroup :label="t('mail', 'Always show images from')">
						<TrustedSenders />
					</NcFormGroup>
				</NcAppSettingsSection>
				<NcAppSettingsSection id="security" :name="t('mail', 'Security')">
					<NcFormBoxSwitch
						v-model="useInternalAddresses"
						:disabled="loadingInternalAddresses"
						:label="internalAddressText"
						:description="t('mail', 'Manage your internal addresses and domains to ensure recognized contacts stay unmarked')"
						@update:modelValue="onToggleInternalAddress" />
					<InternalAddress />

					<NcFormGroup :label="t('mail', 'S/MIME')">
						<NcButton
							class="app-settings-button"
							variant="secondary"
							:aria-label="t('mail', 'Manage certificates')"
							wide
							@click.prevent.stop="displaySmimeCertificateModal = true">
							<template #icon>
								<IconMedal :size="20" />
							</template>
							{{ t('mail', 'Manage certificates') }}
						</NcButton>
						<SmimeCertificateModal
							v-if="displaySmimeCertificateModal"
							@close="displaySmimeCertificateModal = false" />
					</NcFormGroup>

					<NcFormGroup :label="t('mail', 'Mailvelope')">
						<NcNoteCard v-if="mailvelopeIsAvailable" type="success">
							{{ t('mail', 'Mailvelope is enabled for the current domain.') }}
						</NcNoteCard>

						<NcFormBox v-else>
							<NcFormBoxButton
								href="https://www.mailvelope.com/"
								target="_blank"
								:label="t('mail', 'Step 1')"
								:description="t('mail', 'Install the browser extension')"
								inverted-accent />
							<NcFormBoxButton
								:label="t('mail', 'Step 2')"
								:description="t('mail', 'Enable for the current domain')"
								inverted-accent
								@click="mailvelopeAuthorizeDomain">
								<template #icon>
									<IconDomain :size="20" />
								</template>
							</NcFormBoxButton>
						</NcFormBox>
					</NcFormGroup>
				</NcAppSettingsSection>

				<NcAppSettingsSection v-if="followUpFeatureAvailable" id="autotagging-settings" :name="t('mail', 'Assistance features')">
					<NcFormBox>
						<NcFormBoxSwitch
							:checked="useFollowUpReminders"
							:disabled="loadingFollowUpReminders"
							@update:modelValue="onToggleFollowUpReminders">
							{{ followUpReminderText }}
						</NcFormBoxSwitch>
					</NcFormBox>
				</NcAppSettingsSection>

				<NcAppSettingsShortcutsSection>
					<NcHotkeyList>
						<NcHotkey :label="t('mail', 'Compose new message')" hotkey="C" />
						<NcHotkey :label="t('mail', 'Newer message')" hotkey="ArrowLeft" />
						<NcHotkey :label="t('mail', 'Older message')" hotkey="ArrowRight" />
						<NcHotkey :label="t('mail', 'Toggle star')" hotkey="S" />
						<NcHotkey :label="t('mail', 'Toggle unread')" hotkey="U" />
						<NcHotkey :label="t('mail', 'Archive')" hotkey="A" />
						<NcHotkey :label="t('mail', 'Delete')" hotkey="Delete" />
						<NcHotkey :label="t('mail', 'Search')" hotkey="Control F" />
						<NcHotkey :label="t('mail', 'Send')" hotkey="Control Enter" />
						<NcHotkey :label="t('mail', 'Refresh')" hotkey="R" />
					</NcHotkeyList>
				</NcAppSettingsShortcutsSection>

				<NcAppSettingsSection id="about-settings" :name="t('mail', 'About')">
					<NcFormGroup
						:label="t('mail', 'Acknowledgements')"
						:description="t('mail', 'This application includes CKEditor, an open-source editor. Copyright © CKEditor contributors. Licensed under GPLv2.')" />
				</NcAppSettingsSection>

				<NcDialog
					:open.sync="textBlockDialogOpen"
					:name="t('mail', 'New text block')"
					:is-form="true"
					size="normal">
					<NcInputField :value.sync="localTextBlock.title" :label="t('mail', 'Title of the text block')" />
					<TextEditor
						v-model="localTextBlock.content"
						:is-bordered="true"
						:html="true"
						:placeholder="t('mail', 'Content of the text block')"
						:bus="bus"
						:show-toolbar="handleShowToolbar" />
					<div class="text-block-buttons">
						<NcButton
							variant="tertiary"
							class="text-block-buttons__button"
							@click="closeTextBlockDialog">
							<template #icon>
								<IconClose :size="20" />
							</template>
							{{ t('mail', 'Cancel') }}
						</NcButton>
						<NcButton
							variant="primary"
							class="text-block-buttons__button"
							:disabled="!localTextBlock.title || !localTextBlock.content"
							@click="newTextBlock">
							<template #icon>
								<IconCheck :size="20" />
							</template>
							{{ t('mail', 'Ok') }}
						</NcButton>
					</div>
				</NcDialog>
			</NcAppSettingsSection>
		</NcAppSettingsDialog>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import {
	NcAppSettingsDialog,
	NcAppSettingsSection,
	NcAppSettingsShortcutsSection,
	NcButton,
	NcDialog,
	NcFormBox,
	NcFormBoxButton,
	NcFormBoxSwitch,
	NcFormGroup,
	NcHotkey,
	NcHotkeyList,
	NcInputField,
	NcNoteCard,
	NcRadioGroup,
	NcRadioGroupButton,
} from '@nextcloud/vue'
import mitt from 'mitt'
import { mapState, mapStores } from 'pinia'
import IconArrow from 'vue-material-design-icons/ArrowRight.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import HorizontalSplit from 'vue-material-design-icons/DockBottom.vue'
import VerticalSplit from 'vue-material-design-icons/DockLeft.vue'
import IconDomain from 'vue-material-design-icons/Domain.vue'
import CompactMode from 'vue-material-design-icons/ListBoxOutline.vue'
import IconMedal from 'vue-material-design-icons/MedalOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import InternalAddress from './InternalAddress.vue'
import SmimeCertificateModal from './smime/SmimeCertificateModal.vue'
import List from './textBlocks/List.vue'
import TextEditor from './TextEditor.vue'
import TrustedSenders from './TrustedSenders.vue'
import Logger from '../logger.js'
import useMainStore from '../store/mainStore.js'

export default {
	name: 'AppSettingsMenu',
	components: {
		TrustedSenders,
		InternalAddress,
		NcButton,
		IconAdd,
		IconMedal,
		IconClose,
		IconCheck,
		SmimeCertificateModal,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcAppSettingsShortcutsSection,
		NcRadioGroup,
		NcRadioGroupButton,
		CompactMode,
		VerticalSplit,
		HorizontalSplit,
		List,
		NcDialog,
		NcInputField,
		TextEditor,
		NcFormBox,
		NcFormBoxButton,
		NcFormBoxSwitch,
		NcFormGroup,
		IconDomain,
		NcNoteCard,
		NcHotkeyList,
		NcHotkey,
		IconArrow,
	},

	props: {
		open: {
			required: true,
			type: Boolean,
		},
	},

	data() {
		return {
			loadingAvatarSettings: false,
			prioritySettingsText: t('mail', 'Search the body of messages in priority Inbox'),
			loadingPrioritySettings: false,

			optOutSettingsText: t('mail', 'Activate'),
			loadingOptOutSettings: false,
			loadingInternalAddresses: false,
			loadingReplySettings: false,

			followUpReminderText: t('mail', 'Remind about messages that require a reply but received none'),
			internalAddressText: t('mail', 'Highlight external addresses'),
			toggleAutoTagging: false,
			loadingFollowUpReminders: false,
			loadingSortFavorites: false,
			displaySmimeCertificateModal: false,
			sortOrder: 'newest',
			showSettings: false,
			showAccountSettings: false,
			showMailSettings: true,
			selectedAccount: null,
			mailvelopeIsAvailable: false,
			trapElements: [],
			bus: mitt(),
			textBlockDialogOpen: false,
			localTextBlock: {
				title: '',
				content: '',
			},
		}
	},

	computed: {
		...mapStores(useMainStore),
		...mapState(useMainStore, ['getAccounts', 'followUpFeatureAvailable', 'getMyTextBlocks', 'getSharedTextBlocks']),
		useBottomReplies() {
			return this.mainStore.getPreference('reply-mode', 'top') === 'bottom'
		},

		allowNewMailAccounts() {
			return this.mainStore.getPreference('allow-new-accounts', true)
		},

		mailVersion() {
			return this.mainStore.getPreference('mailVersion', '0.0.0')
		},

		accountsWithEmail() {
			return this.getAccounts.filter((account) => account && account.emailAddress)
		},

		sortFavorites: {
			get() {
				return this.mainStore.getPreference('sort-favorites', 'false') === 'true'
			},

			set(value) {
				this.onToggleSortFavorites(value)
			},
		},

		searchPriorityBody: {
			get() {
				return this.mainStore.getPreference('search-priority-body', 'false') === 'true'
			},

			set(value) {
				this.onToggleSearchPriorityBody(value)
			},
		},

		useExternalAvatars: {
			get() {
				return this.mainStore.getPreference('external-avatars', 'true') === 'true'
			},

			set(value) {
				this.onToggleExternalAvatars(value)
			},
		},

		useDataCollection: {
			get() {
				return this.mainStore.getPreference('collect-data', 'true') === 'true'
			},

			set(value) {
				this.onToggleCollectData(value)
			},
		},

		useInternalAddresses: {
			get() {
				return this.mainStore.getPreference('internal-addresses', 'false') === 'true'
			},

			set(value) {
				this.onToggleInternalAddress(value)
			},
		},

		useFollowUpReminders: {
			get() {
				return this.mainStore.getPreference('follow-up-reminders', 'true') === 'true'
			},

			set(value) {
				this.onToggleFollowUpReminders(value)
			},
		},

		layoutMode: {
			get() {
				return this.mainStore.getPreference('layout-mode', 'vertical-split')
			},

			set(value) {
				this.setLayout(value)
			},
		},

		layoutMessageView: {
			get() {
				const preference = this.mainStore.getPreference('layout-message-view')
				return preference === 'threaded' ? true : false
			},

			set(value) {
				if (value) {
					this.setLayoutMessageView('threaded')
				} else {
					this.setLayoutMessageView('singleton')
				}
			},
		},
	},

	watch: {
		showSettings(value) {
			if (!value) {
				this.$emit('update:open', value)
			}
		},

		async open(value) {
			if (value) {
				await this.onOpen()
			}
		},
	},

	mounted() {
		this.sortOrder = this.mainStore.getPreference('sort-order', 'newest')
		document.addEventListener.call(window, 'mailvelope', () => this.checkMailvelope())
		if (!this.mainStore.areTextBlocksFetched()) {
			this.mainStore.fetchMyTextBlocks()
			this.mainStore.fetchSharedTextBlocks()
		}
	},

	updated() {
		this.checkMailvelope()
	},

	methods: {
		openAccountSettings(accountId) {
			this.mainStore.showSettingsForAccountMutation(accountId)
			this.showSettings = false
		},

		checkMailvelope() {
			this.mailvelopeIsAvailable = !!window.mailvelope
		},

		async setLayout(layoutMode) {
			try {
				await this.mainStore.savePreference({
					key: 'layout-mode',
					value: layoutMode,
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
			}
		},

		async setLayoutMessageView(value) {
			try {
				await this.mainStore.savePreference({
					key: 'layout-message-view',
					value,
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
			}
		},

		async onOpen() {
			this.showSettings = true
		},

		onToggleButtonReplies(atBottom) {
			this.loadingReplySettings = true

			this.mainStore.savePreference({
				key: 'reply-mode',
				value: atBottom ? 'bottom' : 'top',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingReplySettings = false
				})
		},

		onToggleExternalAvatars(enabled) {
			this.loadingAvatarSettings = true

			this.mainStore.savePreference({
				key: 'external-avatars',
				value: enabled ? 'true' : 'false',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingAvatarSettings = false
				})
		},

		async onToggleSearchPriorityBody(enabled) {
			this.loadingPrioritySettings = true

			try {
				await this.mainStore.savePreference({
					key: 'search-priority-body',
					value: enabled ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('could not save preferences', { error })
			} finally {
				this.loadingPrioritySettings = false
			}
		},

		async onToggleSortFavorites(enabled) {
			this.loadingSortFavorites = true

			try {
				await this.mainStore.savePreference({
					key: 'sort-favorites',
					value: enabled ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('could not save preferences', { error })
			} finally {
				this.loadingSortFavorites = false
			}
		},

		onToggleCollectData(collect) {
			this.loadingOptOutSettings = true

			this.mainStore.savePreference({
				key: 'collect-data',
				value: collect ? 'true' : 'false',
			})
				.catch((error) => Logger.error('could not save preferences', { error }))
				.then(() => {
					this.loadingOptOutSettings = false
				})
		},

		async onSortByDate(value) {
			const previousValue = this.sortOrder
			try {
				this.sortOrder = value
				await this.mainStore.savePreference({
					key: 'sort-order',
					value,
				})
				this.mainStore.removeAllEnvelopesMutation()
			} catch (error) {
				Logger.error('could not save preferences', { error })
				this.sortOrder = previousValue
				showError(t('mail', 'Could not update preference'))
			}
		},

		async onToggleFollowUpReminders(enabled) {
			this.loadingFollowUpReminders = true

			try {
				await this.mainStore.savePreference({
					key: 'follow-up-reminders',
					value: enabled ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
				showError(t('mail', 'Could not update preference'))
			} finally {
				this.loadingFollowUpReminders = false
			}
		},

		async onToggleInternalAddress(enabled) {
			this.loadingInternalAddresses = true

			try {
				await this.mainStore.savePreference({
					key: 'internal-addresses',
					value: enabled ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('Could not save preferences', { error })
				showError(t('mail', 'Could not update preference'))
			} finally {
				this.loadingInternalAddresses = false
			}
		},

		registerProtocolHandler() {
			if (window.navigator.registerProtocolHandler) {
				const url
					= window.location.protocol + '//' + window.location.host + generateUrl('apps/mail/compose?uri=%s')
				try {
					window.navigator.registerProtocolHandler('mailto', url, OC.theme.name + ' Mail')
				} catch (err) {
					Logger.error('could not register protocol handler', { err })
				}
			}
		},

		mailvelopeAuthorizeDomain() {
			const iframe = document.createElement('iframe')
			iframe.style = 'display: none'
			iframe.src = 'https://api.mailvelope.com/authorize-domain/?api=true'
			document.body.append(iframe)
		},

		handleShowToolbar(element) {
			this.trapElements.push(element)
		},

		newTextBlock() {
			this.mainStore.createTextBlock({ ...this.localTextBlock })
			this.textBlockDialogOpen = false
			this.localTextBlock = {
				title: '',
				content: '',
			}
		},

		closeTextBlockDialog() {
			this.textBlockDialogOpen = false
			this.localTextBlock = {
				title: '',
				content: '',
			}
		},
	},
}
</script>
