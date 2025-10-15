<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcGuestContent>
		<h2>{{ t('mail', 'Account connected') }}</h2>
		<span>{{ t('mail', 'You can close this window') }}</span>
	</NcGuestContent>
</template>

<script>
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'

export default {
	name: 'OauthDone',
	components: {
		NcGuestContent,
	},
}
</script>
