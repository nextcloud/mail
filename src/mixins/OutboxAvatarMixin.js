/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {
	computed: {
		recipients() {
			const to = this.message.to ?? []
			const cc = this.message.cc ?? []
			return [...to, ...cc]
		},
		avatarDisplayName() {
			return this.recipients[0]?.label ?? this.recipients[0]?.email ?? '?'
		},
		avatarEmail() {
			return this.recipients[0]?.email ?? ''
		},
	},
}
