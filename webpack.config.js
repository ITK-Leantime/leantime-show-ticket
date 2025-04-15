const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

module.exports = {
    entry: './assets/show-ticket.js',
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'show-ticket.js',
    },
    plugins: [
        new CopyWebpackPlugin({
          patterns: [
            {
              from: path.resolve(__dirname, 'assets/show-ticket.css'),
              to: path.resolve(__dirname, 'dist'),
            },
          ],
        }),
      ],
    mode: 'development',
};
