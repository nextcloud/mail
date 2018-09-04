<template>
	<div class="avatar"
		 :data-user="label"
		 data-size="32"
		 v-placeholder="label">{{ label | firstCharacter }}
	</div>
</template>

<script>
	export default {
		name: "Avatar",
		props: [
			'label'
		],
		directives: {
			placeholder: {
				inserted: function (el, binding) {
					$(el).imageplaceholder(binding.value, binding.value);
				}
			}
		},
		filters: {
			firstCharacter (val) {
				return val.charAt(0);
			}
		}
	}
</script>
