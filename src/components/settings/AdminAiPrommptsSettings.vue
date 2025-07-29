<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div id="ai-prompts-settings" class="section">
		<h2>
			{{ t('mail', 'Groupware llm prompts') }}
		</h2>
		<div id="assistant-content">
			<div class="mail">
				<h2>
					{{ t('mail', 'Mail prompts') }}
				</h2>
				<div class="line">
					<label for="event_data">
						{{ t('mail', 'Instructions for event data generation') }}
					</label>
				</div>
				<NcNoteCard type="info">
					<p>{{ t('mail', 'This used for event generation from an email.') }}</p>
				</NcNoteCard>
				<NcRichContenteditable id="event_data"
					v-model="event_data"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'Instructions for event data generation')"
					:aria-label="t('mail', 'Instructions for event data generation')"
					dir="auto"
					@update:model-value="delayedValueUpdate(event_data, 'event_data_prompt_preamble')"
					@submit="delayedValueUpdate(event_data, 'event_data_prompt_preamble')" />
				<div class="line">
					<label for="message_summary">
						{{ t('mail', 'Instructions for single message summary') }}
					</label>
				</div>
				<NcNoteCard type="info">
					<p>{{ t('mail', 'This used for email summary in the message preview') }}</p>
					<p>{{ t('mail', 'The message body will be concatinated to the end of the prompt') }}</p>
				</NcNoteCard>
				<NcRichContenteditable id="message_summary"
					v-model="message_summary"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'summarize_email_prompt')"
					:aria-label="t('mail', 'summarize_email_prompt')"
					dir="auto"
					@update:model-value="delayedValueUpdate(message_summary, 'message_summary')"
					@submit="delayedValueUpdate(message_summary, 'message_summary')" />
				<div class="line">
					<label for="smart_replies">
						{{ t('mail', 'Instructions for smart replies') }}
					</label>
				</div>
				<NcNoteCard type="info">
					<p>{{ t('mail', 'The message body will inserted between the preamble and postamble') }}</p>
				</NcNoteCard>
				<NcRichContenteditable id="smart_replies"
					v-model="smart_replies_preamble"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'Preamble for smart replies prompt')"
					:aria-label="t('mail', 'Preamble for smart replies prompt')"
					dir="auto"
					@update:model-value="delayedValueUpdate(smart_replies_preamble, 'smart_reply_prompt_preamble')"
					@submit="delayedValueUpdate(smart_replies_preamble, 'smart_reply_prompt_preamble')" />
				<NcRichContenteditable id="smart_replies_postamble"
					v-model="smart_replies_postamble"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'Postamble for smart replies prompt')"
					:aria-label="t('mail', 'Postamble for smart replies prompt')"
					dir="auto"
					@update:model-value="delayedValueUpdate(smart_replies_postamble, 'smart_reply_prompt_postamble')"
					@submit="delayedValueUpdate(smart_replies_postamble, 'smart_reply_prompt_postamble')" />
				<div class="line">
					<label for="follow_up">
						{{ t('mail', 'Instructions for deciding wether an email requires a followup') }}
					</label>
				</div>
				<NcNoteCard type="info">
					<p>{{ t('mail', 'The message body will inserted between the preamble and postamble') }}</p>
				</NcNoteCard>
				<NcRichContenteditable id="follow_up"
					v-model="follow_up_preamble"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'Preamble for smart replies prompt')"
					:aria-label="t('mail', 'Preamble for smart replies prompt')"
					dir="auto"
					@update:model-value="delayedValueUpdate(follow_up_preamble, 'requires_followup_prompt_preamble')"
					@submit="delayedValueUpdate(follow_up_preamble, 'equires_followup_prompt_preamble')" />
				<NcRichContenteditable id="follow_up_postamble"
					v-model="follow_up_postamble"
					class="text-field"
					:auto-complete="() => {}"
					:link-auto-complete="false"
					:placeholder="t('mail', 'Postamble for follow up prompt')"
					:aria-label="t('mail', 'Postamble for follow up prompt')"
					dir="auto"
					@update:model-value="delayedValueUpdate(follow_up_postamble, 'requires_followup_prompt_postamble')"
					@submit="delayedValueUpdate(follow_up_postamble, 'requires_followup_prompt_postamble')" />
			</div>
		</div>
	</div>
</template>

<script>

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcRichContenteditable from '@nextcloud/vue/components/NcRichContenteditable'

import debounce from 'lodash/fp/debounce.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { getPromptes, savePromptValue } from '../../service/AiPromptsService.js'

export default {
	name: 'AdminAiPrommptsSettings',

	components: {
		NcNoteCard,
		NcRichContenteditable,
	},

	data() {
		return {
			follow_up_postamble: '',
			follow_up_preamble: '',
			event_data: '',
			message_summary: '',
			smart_replies_preamble: '',
			smart_replies_postamble: '',
		}
	},
	mounted() {
		this.loadPrompts()
	},

	methods: {
		loadPrompts() {
			const initialValues = getPromptes()
			if (initialValues) {
				this.event_data = initialValues.event_data || ''
				this.message_summary = initialValues.message_summary || ''
				this.smart_replies_preamble = initialValues.smart_replies_preamble || ''
				this.smart_replies_postamble = initialValues.aiPrompts.smart_replies_postamble || ''
				this.follow_up_preamble = initialValues.aiPrompts.follow_up_preamble || ''
				this.follow_up_postamble = initialValues.aiPrompts.follow_up_postamble || ''
			}
		},
		delayedValueUpdate(newValue, key) {
			debounce(() => {
				this.saveValue(newValue, key)
			}, 5 * 1000)()
		},
		async saveValue(value, key) {
			try {
				await savePromptValue(value, key)
				showSuccess(t('admin', 'Prompt saved successfully'))
			} catch (error) {
				console.error('Failed to save value', error)
				showError(
					this.t('mail', 'Failed to save prompt'),
				)
			}
		},
	},
}
</script>

<style scoped lang="scss">
#ai-prompts-settings {
	.line {
		display: flex;
		align-items: center;
		margin-top: 12px;
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
		.text-field {
			margin-left: 8px;
			width: 300px;
		}
	}

	h2 {
		justify-content: start;
		display: flex;
		align-items: center;
		gap: 8px;
		margin-top: 8px;
	}

	.mail {
		display: flex;
		flex-direction: column;
	}
}
</style>
