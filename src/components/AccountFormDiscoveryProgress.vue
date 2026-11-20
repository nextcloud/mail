<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { DiscoveryProgress, DiscoveryProtocol, DiscoverySource } from '../models/discovery.ts'

import { t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClose from 'vue-material-design-icons/Close.vue'

const props = withDefaults(defineProps<{
	choices?: DiscoveryProtocol[]
	email?: string
	message?: string
	progress: DiscoveryProgress[]
}>(), {
	choices: () => [],
	email: '',
	message: '',
})

const emit = defineEmits<{
	choose: [protocol: DiscoveryProtocol]
}>()

const selected = ref<DiscoveryProtocol | null>(null)

watch(() => props.choices, (choices) => {
	selected.value = choices[0] ?? null
}, { immediate: true })

const ICON_SIZE = 24

const protocolNames: Record<DiscoveryProtocol, string> = {
	imap: 'IMAP',
	jmap: 'JMAP',
}

const protocolHints: Record<DiscoveryProtocol, string> = {
	imap: t('mail', 'Widely supported, sends mail through SMTP'),
	jmap: t('mail', 'Newer protocol with faster synchronization'),
}

const sourceNames: Record<DiscoverySource, string> = {
	autoconfig: t('mail', 'provider configuration'),
	guess: t('mail', 'mail server ports'),
	srv: t('mail', 'DNS records'),
	'well-known': t('mail', 'well-known address'),
}

function entryState(entry: DiscoveryProgress): 'checking' | 'found' | 'missing' {
	if (!entry.complete) {
		return 'checking'
	}
	return entry.found ? 'found' : 'missing'
}

function isChoice(entry: DiscoveryProgress): boolean {
	return props.choices.includes(entry.protocol)
}

function onContinue(): void {
	if (selected.value !== null) {
		emit('choose', selected.value)
	}
}

function statusText(entry: DiscoveryProgress): string {
	if (entry.complete) {
		if (!entry.found) {
			return t('mail', 'Not found')
		}
		return entry.source
			? t('mail', 'Found via {source}', { source: sourceNames[entry.source] })
			: t('mail', 'Found')
	}
	return entry.source
		? t('mail', 'Checking {source}', { source: sourceNames[entry.source] })
		: t('mail', 'Waiting')
}
</script>

<template>
	<section class="account-form-discovery-progress" aria-live="polite">
		<h3 class="account-form-discovery-progress__title">
			{{ t('mail', 'Setting up your account') }}
		</h3>
		<p v-if="email" class="account-form-discovery-progress__email">
			{{ email }}
		</p>
		<ul class="account-form-discovery-progress__entries" :aria-label="t('mail', 'Configuration discovery progress')">
			<li
				v-for="entry in progress"
				:key="entry.protocol"
				class="account-form-discovery-progress__entry"
				:class="{
					[`account-form-discovery-progress__entry--${entryState(entry)}`]: true,
					'account-form-discovery-progress__entry--selectable': isChoice(entry),
					'account-form-discovery-progress__entry--selected': isChoice(entry) && selected === entry.protocol,
				}">
				<component :is="isChoice(entry) ? 'label' : 'div'" class="account-form-discovery-progress__card">
					<input
						v-if="isChoice(entry)"
						v-model="selected"
						class="hidden-visually"
						type="radio"
						name="account-form-discovery-protocol"
						:value="entry.protocol">
					<span class="account-form-discovery-progress__icon">
						<IconCheck v-if="entryState(entry) === 'found'" :size="ICON_SIZE" />
						<IconClose v-else-if="entryState(entry) === 'missing'" :size="ICON_SIZE" />
						<NcLoadingIcon v-else :size="ICON_SIZE" />
					</span>
					<span class="account-form-discovery-progress__details">
						<span class="account-form-discovery-progress__protocol">
							{{ protocolNames[entry.protocol] }}
							<span v-if="isChoice(entry) && entry.protocol === choices[0]" class="account-form-discovery-progress__badge">
								{{ t('mail', 'Recommended') }}
							</span>
						</span>
						<span class="account-form-discovery-progress__status">{{ statusText(entry) }}</span>
						<span v-if="isChoice(entry)" class="account-form-discovery-progress__hint">{{ protocolHints[entry.protocol] }}</span>
					</span>
				</component>
			</li>
		</ul>
		<p v-if="message" class="account-form-discovery-progress__message">
			{{ message }}
		</p>
		<NcButton
			v-if="choices.length > 0"
			class="account-form-discovery-progress__continue"
			variant="primary"
			:disabled="selected === null"
			@click="onContinue">
			{{ t('mail', 'Continue') }}
		</NcButton>
	</section>
</template>

<style lang="scss" scoped>
.account-form-discovery-progress {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: calc(var(--default-grid-baseline) * 4) 0;

	&__title {
		margin: 0;
		font-size: 20px;
		font-weight: bold;
		text-align: center;
	}

	&__email {
		margin-top: var(--default-grid-baseline);
		color: var(--color-text-maxcontrast);
		text-align: center;
		overflow-wrap: anywhere;
	}

	&__entries {
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 2);
		width: 100%;
		margin-top: calc(var(--default-grid-baseline) * 6);
	}

	&__card {
		display: flex;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 3);
		padding: calc(var(--default-grid-baseline) * 3);
		border: 2px solid transparent;
		border-radius: var(--border-radius-large);
		background-color: var(--color-background-hover);
		transition: border-color var(--animation-quick), opacity var(--animation-quick);
	}

	&__entry {
		&--checking .account-form-discovery-progress__card,
		&--selected .account-form-discovery-progress__card {
			border-color: var(--color-primary-element);
		}

		&--selectable .account-form-discovery-progress__card {
			cursor: pointer;

			&:hover {
				border-color: var(--color-border-maxcontrast);
			}

			&:focus-within {
				outline: 2px solid var(--color-main-text);
				outline-offset: 2px;
			}
		}

		&--selected .account-form-discovery-progress__card:hover {
			border-color: var(--color-primary-element);
		}

		&--found .account-form-discovery-progress__icon {
			color: var(--color-success-text);
			border-color: var(--color-success-text);
		}

		&--missing {
			opacity: .7;
		}
	}

	&__icon {
		display: flex;
		flex-shrink: 0;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		border: 2px solid var(--color-border-maxcontrast);
		border-radius: 50%;
		color: var(--color-text-maxcontrast);
	}

	&__details {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	&__protocol {
		font-size: 16px;
		font-weight: bold;
	}

	&__status,
	&__hint {
		color: var(--color-text-maxcontrast);
	}

	&__badge {
		margin-inline-start: var(--default-grid-baseline);
		padding: 0 calc(var(--default-grid-baseline) * 2);
		border-radius: var(--border-radius-pill);
		background-color: var(--color-primary-element-light);
		color: var(--color-primary-element-light-text);
		font-size: 12px;
		font-weight: normal;
	}

	&__continue {
		margin-top: calc(var(--default-grid-baseline) * 4);
	}

	&__message {
		margin-top: calc(var(--default-grid-baseline) * 6);
		color: var(--color-text-maxcontrast);
		text-align: center;
	}
}
</style>
