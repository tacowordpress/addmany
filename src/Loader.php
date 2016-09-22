<?php

namespace JasandPereza;
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
            dirname(__FILE__),
            'addons/addmany',
            'Frontend'
          );
          $front_end_loader->fileServe($query);
          return $query;
        });
        return true;
    }
}
