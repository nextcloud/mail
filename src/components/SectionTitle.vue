<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="priority">
		<div class="app-content-list-item">
			<h2>{{ name }}</h2>
		</div>
	</div>
</template>
<script>
export default {
	name: 'SectionTitle',
	props: {
		name: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped>
#priority {
	display: inline-block;
}

.app-content-list-item {
	opacity: .8;
}

.app-content-list-item:hover {
	background-color: transparent;
	opacity: 0.8;
}

h2 {
	font-weight: normal;
	font-size: 17px;
	margin-bottom: 2px;
}
</style>
