<?php
/**
 * SPT software - Demo application
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: How we setup a website
 * 
 */

define( 'APP_PATH', __DIR__ . '/');

require APP_PATH.'../../src/bootstrap.php';
require SPT_PATH.'router.php';
require SPT_PATH.'app.php';

/**
 * Application Bootstrap
 */
require 'application.php';

Config::init(
    [
        'siteSubpath' => 'examples/theme'
    ]
);

/**
 * Routing
 */
$router = Router::_(
    [
        'test-json' => ['fnc'=>'home.testJson', 'format'=>'json'],
        'test-ajax' => ['fnc'=>'home.test', 'format'=>'ajax'],
        'test' => 'home.test', 
        'debug' => 'home.debug', 
        '' => ['fnc'=>'home.display', 'format'=> 'html'],
    ]
);

/**
 * Language
 * We can save this in session
 */
$lange = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$availableLanguages = Config::get('availableLanguages', ['en', 'fr']);
if(!in_array($lange, $availableLanguages)){
    $lange = 'en';
}
require_once APP_PATH.'languages/'.$lange. '.php';

/**
 * Theme
 * 
 */
$theme = application::session('theme', 'b4');
if( isset($_GET['theme']) && 
    $_GET['theme'] != $theme &&
    $_GET['theme'] != 'widget' &&
    file_exists( APP_PATH. 'themes/'. $_GET['theme']. '/')
){
    $theme = $_GET['theme'];
    application::session('theme', $theme, true);
}
Theme::init($theme);

/**
 * Running application
 */
application::execute($router->pathFinding('home.default'));