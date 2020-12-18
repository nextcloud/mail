module.exports = {
	plugins: [
		'@babel/plugin-syntax-dynamic-import'
	],
	presets: [
		[
			'@babel/preset-env',
			{
				useBuiltIns: 'entry',
				corejs: 3
			}
		]
	]
};
