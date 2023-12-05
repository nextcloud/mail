module.exports = {
	testEnvironment: 'jest-environment-jsdom',
	moduleFileExtensions: [
		'js',
		'jsx',
		'json',
		// tell Jest to handle *.vue files
		'vue',
	],
	transform: {
		// process *.vue files with vue-jest
		'^.+\\.vue$': require.resolve('@vue/vue2-jest'),
		'.+\\.(css|styl|less|sass|scss|jpg|jpeg|png|svg|gif|eot|otf|webp|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga|avif)$':
			require.resolve('jest-transform-stub'),
		'^.+\\.jsx?$': require.resolve('babel-jest'),
	},
	// support the same @ -> src alias mapping in source code
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/src/$1',
	},
	// serializer for snapshots
	snapshotSerializers: [
		'jest-serializer-vue',
	],
	testMatch: [
		'**/tests/unit/**/*.spec.[jt]s?(x)',
		'**/__tests__/*.[jt]s?(x)',
	],
	// https://github.com/facebook/jest/issues/6766
	testEnvironmentOptions: {
		url: 'http://localhost/',
	},
	watchPlugins: [
		require.resolve('jest-watch-typeahead/filename'),
		require.resolve('jest-watch-typeahead/testname'),
	],
	transformIgnorePatterns: ['/node_modules/(?!@ckeditor)/.+\\.js$'],
	setupFiles: [
		'<rootDir>/src/tests/setup.js',
	],
}
