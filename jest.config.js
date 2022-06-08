module.exports = {
	preset: '@vue/cli-plugin-unit-jest/presets/no-babel',
	transformIgnorePatterns: ['/node_modules/(?!@ckeditor)/.+\\.js$'],
	setupFiles: [
		'<rootDir>/src/tests/setup.js',
	],
}
