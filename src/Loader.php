<?php

namespace Taco;
use \FrontendLoader\FrontendLoader;
require __DIR__.'/SubPost.php';

class Loader
{

    public static function init()
    {
        add_action('admin_head', '\JasandPereza\AddMany::init');
        add_action('wp_ajax_AJAXSubmit', '\JasandPereza\AddMany::AJAXSubmit');
        add_action('save_post', '\JasandPereza\AddMany::saveAll');
        add_filter('parse_query', function($query) {
          $front_end_loader = new FrontendLoader(
           realpath(dirname(__FILE__).'/../'),
           'addons',
           'dist'
          );
          $front_end_loader->fileServe($query);
          return $query;
        });
        return true;
    }
}
