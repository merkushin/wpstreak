const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const config = {
  // Entry points. The keys replace the [name] portion of the output config below.
  entry: {
    admin: [
      './assets/javascript/admin/index.js',
      './assets/styles/admin/index.css'
    ]
  },

  // Output files: one for each of our entry points.
  output: {
    // [name] allows for the entry object keys to be used as file names.
    filename: './dist/javascript/[name].js',
    // The path to the JS files.
    path: path.resolve(__dirname, '../assets')
  },

  // Setup a loader to transpile down the latest and great JavaScript so older browsers can understand it.
  module: {
    rules: [
      {
        // Look for any .js files.
        test: /\.js$/,
        // Exclude the node_modules folder.
        exclude: /node_modules/,
        // Use babel loader to transpile the JS files.
        loader: 'babel-loader'
      },
      {
        test: /\.css$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader'
        ]
      }
    ]
  },

  plugins: [
    new MiniCssExtractPlugin({
      filename: './dist/styles/[name].css'
    })
  ]
}

module.exports = config;

