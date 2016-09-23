<?php

namespace Taco;
use \FrontendLoader\FrontendLoader;
require __DIR__.'/SubPost.php';

class AddManyLoader
{

    public static function init()
    {
        add_action('admin_head', '\Taco\AddMany::init');
        add_action('wp_ajax_AJAXSubmit', '\Taco\AddMany::AJAXSubmit');
        add_action('save_post', '\Taco\AddMany::saveAll');
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
