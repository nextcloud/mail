import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { convertAxiosError } from '../errors/convert.js'

export const summarizeThread = async (threadId) => {
	const url = generateUrl('/apps/mail/api/thread/{threadId}/summary', {
		threadId,
	})

	try {
		const resp = await axios.get(url)
		if (resp.status === 204) throw convertAxiosError()
		return resp.data.data
	} catch (e) {
		throw convertAxiosError(e)
	}
}
