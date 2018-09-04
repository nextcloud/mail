export default function tooltip (el, binding) {
	// TODO: get rid of global dependencies
	console.info('TOOLTIP', el, binding);
	$(el).tooltip(binding.value);
};
