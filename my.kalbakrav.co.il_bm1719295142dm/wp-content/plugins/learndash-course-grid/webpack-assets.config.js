const webpackWpConfig = require('@wordpress/scripts/config/webpack.config');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const RtlCssPlugin = require('rtlcss-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyPlugin = require( 'copy-webpack-plugin' );
const path = require('path');

module.exports = {
	...webpackWpConfig,
	entry: {
		// Instead of one mamin.js file, we have separate stylesheets and javascript files.
		elementor: './src/assets/js/elementor.js',
		script: './src/assets/js/script.js',
		editor: './src/assets/js/admin/gutenberg/editor.js',
		"meta-box": './src/assets/scss/meta-box.scss',
		style: './src/assets/scss/style.scss'
	},
	output: {
		// Change the output folder from build to assets
		path: path.resolve(__dirname, 'assets/js'),
		filename:
			process.env.NODE_ENV === 'production'
				? '[name].min.js'
				: '[name].js',
	},
	devtool:
		process.env.NODE_ENV === 'development-no-source-maps'
			? false
			: webpackWpConfig.devtool,
	plugins: [
		// Remove the empty JS files that webpack creates after compiling SCSS files
		new FixStyleOnlyEntriesPlugin(),
		// Override some of WP Script's settings for different Plugins.
		...webpackWpConfig.plugins.map((plugin) => {
			// Ensure that CSS files are given the appropriate .min.css suffix.
			if (plugin instanceof MiniCssExtractPlugin) {
				plugin.options.filename =
					'../css/' + ( process.env.NODE_ENV === 'production'
						? '[name].min.css'
						: '[name].css' );
				plugin.options.chunkFilename =
					'../css/' + ( process.env.NODE_ENV === 'production'
						? '[id].min.css'
						: '[id].css' );
			}

			// Ensure that Production builds don't overwrite the Development files.
			if (
				plugin.constructor &&
				plugin.constructor.name === 'CleanWebpackPlugin'
			) {
				plugin.dry = process.env.NODE_ENV === 'production';
			}

			return plugin;
		}).filter(
			(plugin) =>
				// remove the DependencyExtractionWebpackPlugin so the main.assets.php file doesn't gets generated.
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		// Copy over static files.
		new CopyPlugin( {
			patterns: [
				{ from: 'src/assets/img', to: '../img' },
				{ from: 'src/assets/lib', to: '../lib' },
			],
		} ),
		// Add RTL support.
		new RtlCssPlugin({
			filename:
				'../css/' + ( process.env.NODE_ENV === 'production'
					? '[name].min-rtl.css'
					: '[name]-rtl.css' ),
		})
	],
	optimization: {
		minimize: process.env.NODE_ENV === 'production',
	},
};
