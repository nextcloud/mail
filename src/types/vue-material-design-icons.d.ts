/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare module 'vue-material-design-icons/*.vue' {
	import type { DefineComponent } from 'vue'

	const component: DefineComponent<{
		fillColor?: string
		size?: number
		title?: string
	}>
	export default component
}
