import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert'

export async function deleteThreads(ids) {
	const url = generateUrl('/apps/mail/api/thread')

	try {
		return (await axios.delete(url, { data: { ids } })).data
	} catch (e) {
		throw convertAxiosError(e)
	}
}

export async function moveThreads(ids, destMailboxId) {
	const url = generateUrl('/apps/mail/api/thread')

	try {
		return await axios.post(url, { ids, destMailboxId })
	} catch (e) {
		throw convertAxiosError(e)
	}
}
