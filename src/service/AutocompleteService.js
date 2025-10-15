/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const findRecipient = (term) => {
	const url = generateUrl('/apps/mail/api/autoComplete?term={term}', {
		term,
	})

	return Axios.get(url).then((resp) => resp.data)
}
