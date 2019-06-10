<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 17:50
 */

use Fave\Controller;
if(version_compare(PHP_VERSION,'5.6.0','<'))  die('require PHP > 5.6.0 !');
header('Access-Control-Allow-Origin:*');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define ( 'ROOT', dirname ( __FILE__ ) . '/' );
require_once 'bin/Controller.php';
new Controller();
