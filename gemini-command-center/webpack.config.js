const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      'gemini-cc-app': './src/index.js',
    },
    output: {
      path: path.resolve(__dirname, 'assets/js'),
      filename: '[name].js',
      clean: true,
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
            },
          },
        },
        {
          test: /\.css$/,
          use: [
            isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
            'css-loader',
          ],
        },
        {
          test: /\.(png|svg|jpg|jpeg|gif)$/i,
          type: 'asset/resource',
        },
      ],
    },
    plugins: [
      ...(isProduction
        ? [
            new MiniCssExtractPlugin({
              filename: '../css/gemini-cc-app.css',
            }),
          ]
        : []),
    ],
    resolve: {
      extensions: ['.js', '.jsx'],
    },
    externals: {
      react: 'React',
      'react-dom': 'ReactDOM',
      '@wordpress/api-fetch': 'wp.apiFetch',
      '@wordpress/i18n': 'wp.i18n',
    },
    devtool: isProduction ? false : 'source-map',
  };
};