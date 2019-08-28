import {generateUrl} from 'nextcloud-server/dist/router'
import HttpClient from 'nextcloud-axios'

export const getTheme = () => {
	const url = generateUrl('/apps/mail/api/theme')

	return HttpClient.get(url).then(resp => resp.data.theme)
}
