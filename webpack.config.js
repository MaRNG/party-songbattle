const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';

    return {
        entry: {
            main: path.resolve(__dirname, 'assets/js/main.ts'),
        },
        output: {
            path: path.resolve(__dirname, 'www/assets/build'),
            filename: '[name].js',
            publicPath: '/assets/build/',
            clean: true,
        },
        devtool: isProduction ? false : 'source-map',
        resolve: {
            extensions: ['.ts', '.js', '.vue'],
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },
        module: {
            rules: [
                {
                    test: /\.vue$/,
                    loader: 'vue-loader',
                },
                {
                    test: /\.ts$/,
                    loader: 'ts-loader',
                    options: {
                        appendTsSuffixTo: [/\.vue$/],
                        transpileOnly: true,
                    },
                },
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        'css-loader',
                        'sass-loader',
                    ],
                },
            ],
        },
        plugins: [
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({
                filename: '[name].css',
            }),
        ],
    };
};
