import Vue from 'vue'
import Nextcloud from './mixins/Nextcloud'
import HtmlBlockQuote from './components/HtmlBlockQuote'

const vueOfBlockQuote = () => {
	// very hardcode logic
	const blockquote = document.querySelector('blockquote')
	if (blockquote !== null) {
		const quote = blockquote.previousElementSibling
		// remove first quote in body, if exists
		quote.remove()

		if (quote === null) {
			return false
		}
		Vue.mixin(Nextcloud)
		return new Vue({
			el: document.querySelector('blockquote'),
			render: (h) => h(HtmlBlockQuote, {
				props: {
					quote,
					blockquote,
				},
			}),
		})
	}
	return false
}

export {
	vueOfBlockQuote,
}
