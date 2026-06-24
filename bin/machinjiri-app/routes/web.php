<?php
use Mlangeni\Machinjiri\Core\Routing\Router;
/**
 * Web Routes
 * Define your web routes here.
 * You can create additional route files as needed.
 * Remember to keep your routes organized and manageable.
 */

/* Welcome Route */
Router::get('/', 'HomeController@index', 'welcome');




/* Dispatch the router to handle the incoming request */
Router::dispatch();
