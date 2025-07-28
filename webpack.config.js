const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: {
            index: './assets/js/index.js'
        },
        output: {
            path: path.resolve(__dirname, 'assets/build'),
            filename: '[name].js',
            clean: true
        },
        module: {
            rules: [
                {
                    test: /\.(js|jsx)$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                '@babel/preset-env',
                                ['@babel/preset-react', { runtime: 'automatic' }]
                            ]
                        }
                    }
                },
                {
                    test: /\.(css|scss|sass)$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        'sass-loader'
                    ]
                }
            ]
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: 'style.css'
            })
        ],
        resolve: {
            extensions: ['.js', '.jsx'],
            alias: {
                '@': path.resolve(__dirname, 'assets/js')
            }
        },
        externals: {
            'react': 'React',
            'react-dom': 'ReactDOM',
            '@wordpress/element': 'wp.element',
            '@wordpress/components': 'wp.components',
            '@wordpress/api-fetch': 'wp.apiFetch',
            '@wordpress/i18n': 'wp.i18n'
        },
        devtool: isProduction ? false : 'source-map',
        optimization: {
            minimize: isProduction
        }
    };
};