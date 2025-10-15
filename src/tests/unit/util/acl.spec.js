/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {mailboxHasRights} from '../../../util/acl.js'

describe('acl', () => {
	describe('mailboxHasRights', () => {
		it('allow as fallback', () => {
			const mailbox = {}

			const actual = mailboxHasRights(mailbox, 'l')

			expect(actual).toBeTruthy

		})

		it('allow when right included', () => {
			const mailbox = {
				myAcls: 'lrwstipekxa',
			}

			const actual = mailboxHasRights(mailbox, 'l')

			expect(actual).toBeTruthy
		})

		it('deny when right not included', () => {
			const mailbox = {
				myAcls: 'lrw',
			}

			const actual = mailboxHasRights(mailbox, 'iw')

			expect(actual).toBeFalsy
		})
	})
})
