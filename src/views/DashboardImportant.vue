<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<Dashboard query="is:important" />
</template>

<script>
import Dashboard from '../components/Dashboard.vue'

export default {
	name: 'DashboardImportant',
	components: {
		Dashboard,
	},
}
</script>
