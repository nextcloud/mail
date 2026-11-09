/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import { fetchAvatarUrl } from '../../../service/AvatarService.js'

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/router')

describe('service/AvatarService', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		getBaseUrl.mockReturnValue('https://cloud.example.com/nextcloud')
		generateUrl.mockImplementation((route, { email }, options) => {
			const path = route.replace('{email}', encodeURIComponent(email))
			return options?.baseURL
				? `${options.baseURL}/index.php${path}`
				: `/nextcloud/index.php${path}`
		})
	})

	it('does not request an avatar for a null email', async () => {
		await expect(fetchAvatarUrl(null)).resolves.toBeUndefined()
		expect(Axios.get).not.toHaveBeenCalled()
	})

	it('returns undefined when no avatar is available', async () => {
		Axios.get.mockResolvedValue({ status: 204 })

		await expect(fetchAvatarUrl('someone@example.com')).resolves.toBeUndefined()
	})

	it('returns an absolute proxied avatar URL without duplicating index.php', async () => {
		Axios.get.mockResolvedValue({ status: 200, data: { isExternal: true } })

		const url = await fetchAvatarUrl('someone@example.com')

		expect(url).toBe('https://cloud.example.com/nextcloud/index.php/apps/mail/api/avatars/image/someone%40example.com')
		expect(generateUrl).toHaveBeenNthCalledWith(2, '/apps/mail/api/avatars/image/{email}', {
			email: 'someone@example.com',
		}, { baseURL: 'https://cloud.example.com/nextcloud' })
	})

	it('preserves internal avatar URLs', async () => {
		const url = 'https://cloud.example.com/nextcloud/remote.php/dav/addressbooks/users/jane/photo.jpg'
		Axios.get.mockResolvedValue({ status: 200, data: { isExternal: false, url } })

		await expect(fetchAvatarUrl('someone@example.com')).resolves.toBe(url)
		expect(getBaseUrl).not.toHaveBeenCalled()
		expect(generateUrl).toHaveBeenCalledTimes(1)
	})
})
