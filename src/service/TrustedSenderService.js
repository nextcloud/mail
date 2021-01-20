import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export async function trustSender(email, trustFlag) {
	const url = generateUrl('/apps/mail/api/trustedsenders/{email}', {
		email,
	})

	if (trustFlag) {
		await axios.put(url)
	} else {
		await axios.delete(url)
	}
}
export async function fetchTrustedSenders() {
	const url = generateUrl('/apps/mail/api/trustedsenders')
	const response = await axios.get(url)
	return response.data.data
}
