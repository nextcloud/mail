const path = require('path');
var webpack = require('webpack');

module.exports = {
	entry: './js/init.js',
	output: {
		filename: 'build.js',
		path: path.resolve(__dirname, 'build')
	},
	resolve: {
		modules: [path.resolve(__dirname), 'node_modules'],
		alias: {
			'handlebars': 'handlebars/runtime.js'
		}
	},


plugins: [

    new webpack.DefinePlugin({ // <-- key to reducing React's size
      'process.env': {
        'NODE_ENV': JSON.stringify('production')
      }
    }),
    new webpack.optimize.UglifyJsPlugin(), //minify everything
    new webpack.optimize.AggressiveMergingPlugin()//Merge chunks 
    ],
	devtool: 'inline-source-map',
	module: {
		rules: [
			{test: /davclient/, use: 'exports-loader?dav'},
			{test: /\.html$/, loader: "handlebars-loader", query: {
					extensions: '.html',
					helperDirs: __dirname + '/templatehelpers'
				}}
		],
		loaders: [
			{test: /ical/, loader: 'exports-loader?ICAL'}
		]
	}
};
