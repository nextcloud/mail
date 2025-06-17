<template>
	<div class="quotes">
		<a class="btn" @click="showAllQuotes()">
			<CommentQuote :size="18" />
			Toggle {{ total }} quotes
		</a>
		<HtmlBlockQuoteItem :quote="quotes" @toggleQuote="toggleQuote(quotes)" />
	</div>
</template>

<script>

import HtmlBlockQuoteItem from './HtmlBlockQuoteItem'
import CommentQuote from 'vue-material-design-icons/CommentQuote'

const REGEXP_EMAIL = /([!#-'*+/-9=?A-Z^-~-]+(\.[!#-'*+/-9=?A-Z^-~-]+)*|(\[\]!#-[^-~ \t]|(\\[\t -~]))+")@([!#-'*+/-9=?A-Z^-~-]+(\.[!#-'*+/-9=?A-Z^-~-]+)*|\[[\t -Z^-~]*])/
const REGEXP_LABEL = /"(.*)"/

export default {
	name: 'HtmlBlockQuote',
	components: {
		HtmlBlockQuoteItem,
		CommentQuote,
	},
	props: {
		quote: {
			type: HTMLElement,
			required: true,
		},
		blockquote: {
			type: HTMLElement,
			required: true,
		},
	},
	data() {
		return {
			quotes: {},
			total: null,
		}
	},
	mounted() {
		this.quotes = this.findAndToggleBlockquotesRecursive(this.quote, this.blockquote)
	},
	methods: {
		findAndToggleBlockquotesRecursive(quote, blockquote) {
			if (quote === null && blockquote !== null) {
				quote = blockquote
			}

			if (quote !== null && blockquote !== null) {
				const _quote = this.getQuoteHeaderData(quote)

				if (blockquote.nodeName === 'BLOCKQUOTE') {
					const subBlockquote = blockquote.querySelector('blockquote')
					const subQuote = subBlockquote !== null ? subBlockquote.previousElementSibling : null
					if (subBlockquote !== null) {
						subBlockquote.remove()
						if (subQuote !== null) {
							 subQuote.remove()
						}
					}

					this.total++

					return {
						id: this.quotes.length,
						text: _quote.text,
						email: _quote.email,
						label: _quote.label,
						date: _quote.date,
						hide: true,
						blockquoteText: blockquote.innerHTML,
						childs: this.findAndToggleBlockquotesRecursive(subQuote, subBlockquote),
					}
				}
			}
		},

		getQuoteHeaderData(quote) {
			const text = quote.innerText.trim()
			const email = REGEXP_EMAIL.exec(text)
			const label = REGEXP_LABEL.exec(text)
			const date = text.split(' – ')

			const data = {
				text: null,
				label: label !== null ? label[1] : text,
				email: email !== null ? email[0] : null,
				date: date !== null && date.length > 1 ? date[1].trim() : null,
			}
			data.text = data.email === null && data.label === null ? null : text
			return data
		},

		showAllQuotes(quote) {
			const hide = this.quotes.hide
			const _quote = !quote ? this.quotes : quote
			if (typeof (_quote.childs) !== 'undefined') {
				this.showAllQuotes(_quote.childs)
			}
			_quote.hide = !hide
		},

		toggleQuote(quote) {
			quote.hide = !quote.hide
		},
	},
}
</script>

<style lang="scss" scoped>

	a.btn {
		display:inline-flex;
		margin: 20px 0 20px -26px;
		color:#666;
		text-decoration: none;
		cursor: pointer;

		& > span {
			margin-right: 5px;
		}
	}

	.show-all-quotes {
		display:inline-flex;
		align-items: center;
		margin-bottom: 5px;
		padding: 4px;
		background: #ddd;
		border-radius: 20px;
		cursor: pointer;
	}

	.quotes {
		margin-top: 16px;
		margin-left: 50px;

		.quote {
			margin-left: 0;
		}
	}
</style>
