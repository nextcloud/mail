<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcNoteCard type="info">
		<!-- TRANSLATORS: Shown inside a settings section whose feature is unavailable; "this feature" is the one the section configures -->
		<p>{{ t('mail', 'This feature needs Sieve, a scripting language supported by many email providers. If you\'re unsure whether yours does, check with your provider. If Sieve is available, click the button to go to the settings and enable it.') }}</p>
		<NcButton variant="secondary" :aria-label="t('mail', 'Go to Sieve settings')" href="#sieve-form">
			{{ t('mail', 'Go to Sieve settings') }}
		</NcButton>
	</NcNoteCard>
</template>

<script>
import { NcButton, NcNoteCard } from '@nextcloud/vue'

export default {
	name: 'SieveDisabledHint',

	components: {
		NcButton,
		NcNoteCard,
	},
}
</script>
