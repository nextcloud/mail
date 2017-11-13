// Karma configuration
// Generated on Tue Sep 01 2015 13:54:51 GMT+0200 (CEST)

var webpackConfig = require('./js/webpack.dev.config.js');

webpackConfig.entry = './js/tests/noop_init.js';
webpackConfig.module.rules.push({
	test: /\.js$/,
	exclude: /init\.js/
});

module.exports = function (config) {
	config.set({
		// frameworks to use
		// available frameworks: https://npmjs.org/browse/keyword/karma-adapter
		frameworks: ['jasmine-ajax', 'jasmine', 'sinon'],

		files: [
			{pattern: 'node_modules/jquery/dist/jquery.js', included: true},
			{pattern: 'node_modules/underscore/underscore.js', included: true},
			{pattern: 'js/tests/test-main.js', included: true},
			// all files ending in "_test"
			{pattern: 'js/tests/*_spec.js', watched: false},
			{pattern: 'js/tests/**/*_spec.js', watched: false},
			{pattern: 'js/build/build.js', included: false}
		],

		// list of files to exclude
		exclude: [
			'js/webpack.*.js',
			'js/init.js'
		],
		// preprocess matching files before serving them to the browser
		// available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
		preprocessors: {
			'js/**[!vendor]/*[!spec].js': ['coverage'],
			// add webpack as preprocessor
			'js/tests/*_spec.js': ['webpack'],
			'js/tests/**/*_spec.js': ['webpack']
		},

		webpackMiddleware: {
			// webpack-dev-middleware configuration
			// i. e.
			stats: 'errors-only'
		},

		// test results reporter to use
		// possible values: 'dots', 'progress'
		// available reporters: https://npmjs.org/browse/keyword/karma-reporter
		reporters: ['progress', 'coverage'],
		coverageReporter: {
			type: 'lcov',
			dir: 'coverage/'
		},
		// web server port
		port: 9876,
		// enable / disable colors in the output (reporters and logs)
		colors: true,
		// level of logging
		// possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
		logLevel: config.LOG_INFO,
		// enable / disable watching file and executing tests whenever any file changes
		autoWatch: true,
		// start these browsers
		// available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
		browsers: ['PhantomJS'],
		// Continuous Integration mode
		// if true, Karma captures browsers, runs the tests and exits
		singleRun: false,
		webpack: webpackConfig
	});
};
