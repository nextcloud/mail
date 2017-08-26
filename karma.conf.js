// Karma configuration
// Generated on Tue Sep 01 2015 13:54:51 GMT+0200 (CEST)

var webpackConfig = require('./js/webpack.config.js');
webpackConfig.entry = {};

module.exports = function(config) {
	config.set({
		// frameworks to use
		// available frameworks: https://npmjs.org/browse/keyword/karma-adapter
		frameworks: ['jasmine-ajax', 'jasmine', 'sinon'],

		// list of files / patterns to load in the browser
		files: [
			'js/vendor/jquery/dist/jquery.js',
			{pattern: 'js/**/*.js', included: false},
			{pattern: 'js/*.js', included: false},
			{pattern: 'js/templates/*.html', included: false},
			{pattern: 'js/vendor/**/*.js', included: false},
			{pattern: 'js/tests/*.js', included: false},
			'js/build/build.js',
			'js/tests/test-main.js'
		],

		// list of files to exclude
		exclude: [
			'js/webpack.config.js',
			'js/init.js'
		],
		// preprocess matching files before serving them to the browser
		// available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
		preprocessors: {
  			'js/build/build.js': ['webpack'],
			'js/**[!vendor]/*[!spec].js': ['coverage']
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
	});
};
