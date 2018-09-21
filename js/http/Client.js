import axios from 'axios'

const client = axios.create({
	headers: {
		requesttoken: OC.requestToken
	}
})

export default client
