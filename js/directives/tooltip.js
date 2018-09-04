export default function tooltip (el, binding) {
	// TODO: get rid of global dependencies
	$(el).tooltip(binding.value);
};
