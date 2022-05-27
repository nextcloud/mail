<template functional>
	<span :aria-hidden="props.decorative"
		:aria-label="props.title"
		:class="[data.class, data.staticClass]"
		class="material-design-icon important-icon"
		role="img"
		v-bind="data.attrs"
		v-on="listeners">
		<svg :fill="props.fillColor"
			class="material-design-icon__svg"
			:width="20"
			:height="20"
			viewBox="0 0 16 16">
			<path d="M0 0h24v24H0V0z" fill="none" />
			<path d="M2 13h7.8a1.4 1.4 0 001.1-.6L14 8l-3.1-4.4A1.4 1.4 0 009.8 3H2l3.4 5z" />
		</svg>
	</span>
</template>

<script>
export default {
	name: 'ImportantIcon',
	props: {
		title: {
			type: String,
			default: 'Important icon',
		},
		decorative: {
			type: Boolean,
			default: false,
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
	},
}
</script>
