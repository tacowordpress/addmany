'use strict';

let WebpackNotifierPlugin = require('webpack-notifier');
let ExtractTextPlugin     = require('extract-text-webpack-plugin');
let webpack = require('webpack');
let fs                    = require('fs');
let path                  = require('path');

let base_path = './';
let is_production = false;
let node_dir = __dirname + '/node_modules/';
let source_path = './src/js/';
let output_path = './';

// Get all top level files
let files = fs.readdirSync(source_path);



let entry_points = {};
files.forEach(function(file) {
  if(file[0] === '.') {
    return;
  }
  let stat = fs.statSync(source_path + file);

  if (stat.isFile()) {
    let base_name = path.basename(file, path.extname(file));
    entry_points[base_name] = source_path + file;
  }
});



process.argv.forEach(function(arg) {
  if (arg === '-p' || arg === '--production') {
    is_production = true;
  }
});

let config = {
  add_vendor: function (name, path) {
    this.resolve.alias[name] = path;
    this.module.noParse.push(new RegExp(path));
    this.entry[name] = [name];
  },
  entry: {
    'addmany': ['babel-polyfill', base_path + '/src/js/addmany.js'],
  },
  output: {
    path: output_path + 'dist/',
    filename: '[name]' + (is_production === true ? '.min' : '') +  '.js'
  },
  resolve: { alias: {} },
  module: {
    loaders: [
      {
        test: /\.jsx?$/,
        loader: 'babel-loader',
        exclude: /(node_modules|bower_components)/,
        query: {
          presets: ['es2015', 'react']
        }
      },
      {
        test: /\.scss$/,
        loader: ExtractTextPlugin.extract('style-loader', 'css-loader?sourceMap!sass-loader?sourceMap=map')
      },
      {
        test: /\.(jpg|png|svg|gif|eot|ttf|woff|woff2)(\?.+)?$/,
        loader: 'file-loader?name=assets/[name].[ext]'
      },
      {
        test: /\.jsx?$/,
        loader: 'babel-loader',
        query: {
          presets: ['es2015', 'react'],
        },
      }
    ],
    noParse: []
  },
  plugins: [
    new ExtractTextPlugin('[name]' + (is_production === true ? '.min' : '') +  '.css'),
    new WebpackNotifierPlugin(),
    new webpack.NoErrorsPlugin(),
    new webpack.DefinePlugin({
        'process.env': {
          'NODE_ENV': (is_production === true ? '"production"' : '"development"')
        }
    })
  ]
};
module.exports = config;
