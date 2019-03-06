module.exports = {
	root: true,
	env: {
		node: true,
		amd: true,
		jquery: true,
		mocha: true,
	},
	extends: [
		'plugin:vue/recommended',
		'plugin:prettier/recommended',
		'prettier/vue',
		'eslint:recommended',
	],
	rules: {
		'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
		'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
		'no-unused-vars': 'off',
		'vue/no-v-html': 'off',
		'no-case-declarations': 'off',
	},
	parserOptions: {
		parser: 'babel-eslint',
	},
	globals: {
		expect: true,
		OC: true,
		OCA: true,
		t: true,
		__webpack_public_path__: true,
		__webpack_nonce__: true,
		_: true,
	}
}
