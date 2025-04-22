const path = require("path");
const CopyWebpackPlugin = require("copy-webpack-plugin");

module.exports = {
    entry: "./assets/show-ticket.js",
    output: {
        path: path.resolve(__dirname, "dist"),
        filename: "show-ticket.js",
    },
    module: {
        rules: [
        {
            test: /\.css$/i,
            use: ["style-loader", "css-loader"],
        },
        {
            test: /\.js$/,
            exclude: /node_modules/,
            use: "babel-loader",
        },
        {
            test: /\.(woff|woff2|eot|ttf|otf|svg)$/,
            type: "asset/resource",
        },
        ],
    },
    plugins: [
    new CopyWebpackPlugin({
        patterns: [
        {
            from: path.resolve(__dirname, "assets/show-ticket.css"),
            to: path.resolve(__dirname, "dist"),
        },
        ],
    }),
  ],
resolve: {
    alias: {
        tinymce: path.resolve(__dirname, "node_modules", "tinymce"),
    },
    },
    mode: "development",
};
