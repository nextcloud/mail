<template functional>
	<span :aria-hidden="!props.title"
		:aria-label="props.title"
		:class="[data.class, data.staticClass]"
		class="material-design-icon junk-icon"
		role="img"
		v-bind="data.attrs"
		v-on="listeners">
		<svg :fill="props.fillColor"
			class="material-design-icon__svg"
			:width="props.size"
			:height="props.size"
			viewBox="0 0 16 16">
			<path d="M0 0h24v24H0V0z" fill="none" />
			<path d="m5.5156 2l-3.5156 3.5156v4.9684l3.5156 3.516h4.9684l3.516-3.516v-4.9684l-3.516-3.5156h-4.9684zm1.4844 2h2v5h-2v-5zm0 6h2v2h-2v-2z" />
		</svg>
	</span>
</template>

<script>
export default {
	name: 'JunkIcon',
	props: {
		title: {
			type: String,
			default: '',
		},
		size: {
			type: Number,
			default: 20,
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
	},
}
</script>
