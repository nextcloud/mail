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
			:open.sync="showSettings">
			<NcAppSettingsSection id="account-creation" :name="t('mail', 'General')">
				<NcButton
					v-if="allowNewMailAccounts"
					variant="primary"
					to="/setup"
					:aria-label="t('mail', 'Add mail account')"
					class="app-settings-button">
					<template #icon>
						<IconAdd :size="20" />
					</template>
					{{ t('mail', 'Add mail account') }}
				</NcButton>

				<h4>{{ t('mail', 'Account settings') }}</h4>
				<p>{{ t('mail', 'Settings for:') }}</p>
				<li v-for="account in getAccounts" :key="account.id">
					<NcButton
						v-if=" account && account.emailAddress"
						class="app-settings-button"
						variant="secondary"
						:aria-label="t('mail', 'Account settings')"
						@click="openAccountSettings(account.id)">
						{{ account.emailAddress }}
					</NcButton>
				</li>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="appearance-and-accessibility" :name="t('mail', 'Appearance')">
				<NcRadioGroup v-model="layoutMode" :label="t('mail', 'Layout')">
					<NcRadioGroupButton :label="t('mail', 'List')" value="no-split">
						<template #icon>
							<CompactMode :size="20" />
						</template>
					</NcRadioGroupButton>
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
				</NcRadioGroup>

				<br>

				<NcRadioGroup :model-value="sortOrder" :label="t('mail', 'Sorting')" @update:modelValue="onSortByDate">
					<NcRadioGroupButton :label="t('mail', 'Newest first')" value="newest" />
					<NcRadioGroupButton :label="t('mail', 'Oldest first')" value="oldest" />
				</NcRadioGroup>

				<br>

				<NcRadioGroup v-model="layoutMessageView" :label="t('mail', 'Message view mode')">
					<NcRadioGroupButton :label="t('mail', 'Show all messages in thread')" value="threaded" />
					<NcRadioGroupButton :label="t('mail', 'When off, only the selected message will be shown')" value="singleton" />
				</NcRadioGroup>

				<br>

				<NcRadioGroup :model-value="useBottomReplies" :label="t('mail', 'Reply position')" @update:modelValue="onToggleButtonReplies">
					<NcRadioGroupButton :label="t('mail', 'Top')" :value="false" />
					<NcRadioGroupButton :label="t('mail', 'Bottom')" :value="true" />
				</NcRadioGroup>

				<h4>{{ t('mail', 'Search in body') }}</h4>
				<p class="app-settings">
					<NcCheckboxRadioSwitch
						id="priority-inbox-toggle"
						:checked="searchPriorityBody"
						:loading="loadingPrioritySettings"
						@update:checked="onToggleSearchPriorityBody">
						{{ prioritySettingsText }}
					</NcCheckboxRadioSwitch>
				</p>

				<h4>{{ t('mail', 'Gravatar settings') }}</h4>
				<p class="app-settings avatar-settings">
					<NcCheckboxRadioSwitch
						id="gravatar-enabled"
						:checked="useExternalAvatars"
						:loading="loadingAvatarSettings"
						@update:checked="onToggleExternalAvatars">
						{{ t('mail', 'Avatars from Gravatar and favicons') }}
					</NcCheckboxRadioSwitch>
				</p>

				<h4>{{ t('mail', 'Mailto') }}</h4>
				<p class="settings-hint">
					{{ t('mail', 'Set as default mail app') }}
				</p>
				<NcButton
					variant="secondary"
					class="app-settings-button"
					:aria-label="t('mail', 'Set as default mail app')"
					@click="registerProtocolHandler">
					<template #icon>
						<IconEmail :size="20" />
					</template>
					{{ t('mail', 'Register') }}
				</NcButton>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="text-blocks" :name="t('mail', 'Text blocks')">
				<span class="settings-hint">{{ t('mail', 'Reusable pieces of text that can be inserted in messages') }}</span>
				<List
					:text-blocks="getMyTextBlocks()"
					@show-toolbar="handleShowToolbar" />
				<NcButton variant="primary" @click="() => textBlockDialogOpen = true">
					{{ t('mail', 'New text block') }}
				</NcButton>
				<template v-if="getSharedTextBlocks().length > 0">
					<h6>{{ t('mail', 'Shared with me') }}</h6>
					<List
						:text-blocks="getSharedTextBlocks()"
						:shared="true"
						@show-toolbar="handleShowToolbar" />
				</template>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="privacy-and-security" :name="t('mail', 'Privacy and security')">
				<h4>{{ t('mail', 'Data collection') }}</h4>
				<p class="settings-hint">
					{{ t('mail', 'Allow the app to collect and process data locally to adapt to your preferences.') }}
				</p>
				<p class="app-settings">
					<NcCheckboxRadioSwitch
						id="data-collection-toggle"
						:checked="useDataCollection"
						:loading="loadingOptOutSettings"
						@update:checked="onToggleCollectData">
						{{ optOutSettingsText }}
					</NcCheckboxRadioSwitch>
				</p>

				<h4>{{ t('mail', 'Always show images from') }}</h4>
				<TrustedSenders />

				<h4>{{ t('mail', 'Security') }}</h4>
				<p class="settings-hint">
					{{ t('mail', 'Highlight external email addresses by enabling this feature, manage your internal addresses and domains to ensure recognized contacts stay unmarked.') }}
				</p>
				<p class="app-settings">
					<NcCheckboxRadioSwitch
						id="internal-address-toggle"
						:checked="useInternalAddresses"
						:loading="loadingInternalAddresses"
						@update:checked="onToggleInternalAddress">
						{{ internalAddressText }}
					</NcCheckboxRadioSwitch>
				</p>
				<InternalAddress />

				<h4>{{ t('mail', 'S/MIME') }}</h4>
				<NcButton
					class="app-settings-button"
					variant="secondary"
					:aria-label="t('mail', 'Manage certificates')"
					@click.prevent.stop="displaySmimeCertificateModal = true">
					<template #icon>
						<IconLock :size="20" />
					</template>
					{{ t('mail', 'Manage certificates') }}
				</NcButton>
				<SmimeCertificateModal
					v-if="displaySmimeCertificateModal"
					@close="displaySmimeCertificateModal = false" />

				<h4>{{ t('mail', 'Mailvelope') }}</h4>
				<p class="settings-hint">
					{{ t('mail', 'A browser extension that enables easy OpenPGP encryption and decryption of emails') }}
				</p>
				<div class="mailvelope-section">
					<div v-if="mailvelopeIsAvailable">
						{{ t('mail', 'Mailvelope is enabled for the current domain.') }}
					</div>
					<div v-else>
						<p>
							<a
								href="https://www.mailvelope.com/"
								target="_blank"
								class="button"
								rel="noopener noreferrer">
								{{ t('mail', 'Step 1') }}:
								{{ t('mail', 'Install the browser extension') }}
							</a>
						</p>
						<p>
							<a
								class="button"
								@click="mailvelopeAuthorizeDomain">
								{{ t('mail', 'Step 2') }}:
								{{ t('mail', 'Enable for the current domain') }}
							</a>
						</p>
					</div>
				</div>
			</NcAppSettingsSection>

			<NcAppSettingsSection id="autotagging-settings" :name="t('mail', 'Assistance features')">
				<p class="app-settings">
					<NcCheckboxRadioSwitch
						id="auto-tagging-toggle"
						:checked="useAutoTagging"
						:loading="toggleAutoTagging"
						@update:checked="onToggleAutoTagging">
						{{ autoTaggingText }}
					</NcCheckboxRadioSwitch>
				</p>
				<p v-if="followUpFeatureAvailable" class="app-settings">
					<NcCheckboxRadioSwitch
						id="follow-up-reminder-toggle"
						:checked="useFollowUpReminders"
						:loading="loadingFollowUpReminders"
						@update:checked="onToggleFollowUpReminders">
						{{ followUpReminderText }}
					</NcCheckboxRadioSwitch>
				</p>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="about-settings" :name="t('mail', 'About')">
				<p>{{ t('mail', 'This application includes CKEditor, an open-source editor. Copyright © CKEditor contributors. Licensed under GPLv2.') }}</p>
			</NcAppSettingsSection>
			<NcAppSettingsSection id="keyboard-shortcut-settings" :name="t('mail', 'Keyboard shortcuts')">
				<dl>
					<div>
						<dt><NcKbd symbol="C" /></dt>
						<dd>{{ t('mail', 'Compose new message') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="←" /></dt>
						<dd>{{ t('mail', 'Newer message') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="→" /></dt>
						<dd>{{ t('mail', 'Older message') }}</dd>
					</div>

					<div>
						<dt><NcKbd symbol="S" /></dt>
						<dd>{{ t('mail', 'Toggle star') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="U" /></dt>
						<dd>{{ t('mail', 'Toggle unread') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="A" /></dt>
						<dd>{{ t('mail', 'Archive') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="Del" /></dt>
						<dd>{{ t('mail', 'Delete') }}</dd>
					</div>

					<div>
						<dt><NcKbd symbol="Ctrl" /><NcKbd symbol="F" /></dt>
						<dd>{{ t('mail', 'Search') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="Ctrl" /><NcKbd symbol="Enter" /></dt>
						<dd>{{ t('mail', 'Send') }}</dd>
					</div>
					<div>
						<dt><NcKbd symbol="R" /></dt>
						<dd>{{ t('mail', 'Refresh') }}</dd>
					</div>
				</dl>
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
		</NcAppSettingsDialog>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcAppSettingsDialog, NcAppSettingsSection, NcButton, NcCheckboxRadioSwitch, NcDialog, NcInputField, NcRadioGroup, NcRadioGroupButton } from '@nextcloud/vue'
import mitt from 'mitt'
import { mapState, mapStores } from 'pinia'
import NcKbd from '@nextcloud/vue/components/NcKbd'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconEmail from 'vue-material-design-icons/EmailOutline.vue'
import VerticalSplit from 'vue-material-design-icons/FormatColumns.vue'
import IconLock from 'vue-material-design-icons/LockOutline.vue'
import IconAdd from 'vue-material-design-icons/Plus.vue'
import CompactMode from 'vue-material-design-icons/ReorderHorizontal.vue'
import HorizontalSplit from 'vue-material-design-icons/ViewSplitHorizontal.vue'
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
		IconEmail,
		IconAdd,
		IconLock,
		IconClose,
		IconCheck,
		SmimeCertificateModal,
		NcCheckboxRadioSwitch,
		NcAppSettingsDialog,
		NcAppSettingsSection,
		NcKbd,
		NcRadioGroup,
		NcRadioGroupButton,
		CompactMode,
		VerticalSplit,
		HorizontalSplit,
		List,
		NcDialog,
		NcInputField,
		TextEditor,
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

			autoTaggingText: t('mail', 'Determine importance using machine learning'),

			followUpReminderText: t('mail', 'Remind about messages that require a reply but received none'),
			internalAddressText: t('mail', 'Highlight external addresses'),
			toggleAutoTagging: false,
			loadingFollowUpReminders: false,
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
		searchPriorityBody() {
			return this.mainStore.getPreference('search-priority-body', 'false') === 'true'
		},

		useBottomReplies() {
			return this.mainStore.getPreference('reply-mode', 'top') === 'bottom'
		},

		useExternalAvatars() {
			return this.mainStore.getPreference('external-avatars', 'true') === 'true'
		},

		useDataCollection() {
			return this.mainStore.getPreference('collect-data', 'true') === 'true'
		},

		useAutoTagging() {
			return this.mainStore.getPreference('tag-classified-messages', 'true') === 'true'
		},

		useInternalAddresses() {
			return this.mainStore.getPreference('internal-addresses', 'false') === 'true'
		},

		useFollowUpReminders() {
			return this.mainStore.getPreference('follow-up-reminders', 'true') === 'true'
		},

		allowNewMailAccounts() {
			return this.mainStore.getPreference('allow-new-accounts', true)
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
				return this.mainStore.getPreference('layout-message-view')
			},

			set(value) {
				this.setLayoutMessageView(value)
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
		closeAccountSettings() {
			this.showAccountSettings = false
		},

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

		async onToggleAutoTagging(enabled) {
			this.toggleAutoTagging = true

			try {
				await this.mainStore.savePreference({
					key: 'tag-classified-messages',
					value: enabled ? 'true' : 'false',
				})
			} catch (error) {
				Logger.error('could not save preferences', { error })

				showError(t('mail', 'Could not update preference'))
			} finally {
				this.toggleAutoTagging = false
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

<style lang="scss" scoped>
p.app-settings {
	padding: calc(var(--default-grid-baseline) * 2) 0;
}

.app-settings-button {
	display: inline-flex;
	background-position: 10px center;
	text-align: start;
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.app-settings-button.button.primary.new-button {
	color: var(--color-primary-element-text);
	//this style will be removed after we migrate also the  'add mail account' to material design
	padding-inline-start: 34px;
	gap: var(--default-grid-baseline);
	width: fit-content;
}

.app-settings-link {
	text-decoration: underline;
}

:deep(.button-vue__text) {
	text-overflow: clip;
	white-space: normal;
}

:deep(.button-vue__wrapper) {
	justify-content: flex-start;
}

.mailvelope-section {
	padding-top: calc(var(--default-grid-baseline) * 4);

	a.button {
		display: flex;
		align-items: center;
		line-height: normal;
		min-height: calc(var(--default-grid-baseline) * 11);
		font-size: unset;
		color: var(--color-primary-element-light-text);
		width: fit-content;

		&:focus-visible,
		&:hover {
			box-shadow: 0 0 0 1px var(--color-primary-element);
		}
	}
}

.material-design-icon {
	&.lock-icon {
		margin-inline-end: calc(var(--default-grid-baseline) * 2);
	}

}

.section-title {
	margin-top: calc(var(--default-grid-baseline) * 5);
	margin-bottom: calc(var(--default-grid-baseline) * 2);
}

.mail-creation-button {
	width: 100%;
}

.settings-hint {
	margin-bottom: calc(var(--default-grid-baseline) * 2);
	color: var(--color-text-maxcontrast);
}

.app-settings-section {
	list-style: none;
}

.text-block-buttons {
	width: 100%;
	justify-self: end;
	display: flex;
	justify-content: flex-end;
	&__button {
		margin: var(--default-grid-baseline);
	}
}
</style>
