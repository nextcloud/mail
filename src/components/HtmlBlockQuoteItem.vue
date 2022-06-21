<template>
	<div class="quote" :class="{ 'last': !quote.childs }">
		<div class="quote-header" :class="{ 'active': !quote.hide }" @click="$emit('toggleQuote')">
			<Avatar v-if="quote.label"
				:url="avatarUrl"
				:email="quote.email"
				:display-name="quote.label"
				:disable-tooltip="true"
				:disable-menu="true"
				:size="24" />
			<FormatQuoteClose v-else-if="quote.hide" :size="24" class="quoterdiv" />
			<FormatQuoteOpen v-else :size="24" class="quoterdiv" />
			<div v-if="!quote.label">
				{{ quote.text }}
			</div>
			<div v-else class="quote-header-text">
				<span>{{ quote.label }}
					<a :href="`mailto:${quote.email}`">{{ quote.email }}</a>
					<span v-if="quote.date" class="quote-date">{{ quote.date }}</span>
				</span>
			</div>
		</div>
		<div v-if="!quote.hide" class="quote-body">
			<div class="quote-content" v-html="quote.blockquoteText" />
			<HtmlBlockQuoteItem v-if="quote.childs" :quote="quote.childs" @toggleQuote="toggleQuote(quote.childs)" />
		</div>
	</div>
</template>

<script>
// import { fetchAvatarUrlMemoized } from '../service/AvatarService'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import FormatQuoteClose from 'vue-material-design-icons/FormatQuoteClose'
import FormatQuoteOpen from 'vue-material-design-icons/FormatQuoteOpen'

export default {
	name: 'HtmlBlockQuoteItem',
	components: {
		Avatar,
		FormatQuoteClose,
		FormatQuoteOpen,
	},
	props: {
		quote: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			hide: this.quote.hide,
			avatarUrl: undefined,
		}
	},
	async mounted() {
		if (this.quote.email !== '') {
			// this.avatarUrl = await fetchAvatarUrlMemoized(this.quote.email)
		}
	},
	methods: {
		toggleQuote(quote) {
			quote.hide = !quote.hide
		},
	},
}
</script>

<style lang="scss" scoped>
	.quote {
		margin-bottom: 10px;

		.quote-header {
			display: inline-flex;
			flex-wrap: wrap;
			align-items: center;
			cursor: pointer;
			transition: 0.3s;
			padding: 3px 16px 3px 3px;
			margin-left: -34px;

			.avatardiv {
				border-radius: 50%;
				color: #fff;
				margin-right: 6px;
				width: 24px;
			}

			.quoterdiv {
				border-radius: 50%;
				color: #333;
				margin-right: 6px;
			}

			.quote-header-text {
				width: calc(100% - 30px)
			}

			&:hover, &.active {
				background: #efefef;
				border-radius: 20px;
			}

			.quote-date {
				opacity: 0.5;
			}
		}

		.quote-body {
			padding: 10px 0;

			.quote-content {
				position: relative;
				color: #666;
				display: inline-block;

				&:before {
					content: "";
					position: absolute;
					width: 24px;
					top: -13px;
					bottom: -13px;
					left: -20px;
					border-left: 2px solid #efefef;
					z-index: -1;
				}
			}
		}
		&.last {
			.quote-content:before {
				display: none
			}
		}
	}

</style>
