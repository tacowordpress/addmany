'use strict';

let WebpackNotifierPlugin = require('webpack-notifier');
let webpack = require('webpack');
let base_path = './';
let is_production = false;

process.argv.forEach(function(arg) {
  if (arg === '-p' || arg === '--production') {
    is_production = true;
  }
});

let config = {
  entry: {
    'app': ['babel-polyfill', base_path + 'app.js'],
  },
  output: {
      path: base_path,
      filename: '[name].bundle.js'
  },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        loader: 'babel-loader',
        exclude: /(node_modules|bower_components)/,
        query: {
          presets: ['es2015', 'react']
        }
      }
    ],
    noParse: []
  },
  plugins: [
    new WebpackNotifierPlugin(),
    // new webpack.NoErrorsPlugin(),
    new webpack.DefinePlugin({
        'process.env': {
          'NODE_ENV': (is_production === true ? '"production"' : '"development"')
        }
    }),
    // new webpack.optimize.UglifyJsPlugin({
    //   compress: {
    //     warnings: false
    //   }
    // })
  ]
};
module.exports = config;
