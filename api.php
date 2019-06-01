<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 17:50
 */
$start = microtime(true);
use Fave\Controller;
if(version_compare(PHP_VERSION,'5.6.0','<'))  die('require PHP > 5.6.0 !');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define ( 'ROOT', dirname ( __FILE__ ) . '/' );
define ( 'VERSION', '1.2' );//根目录
require_once 'bin/Controller.php';
new Controller();
$end = microtime(true);
$time=$end-$start;
echo PHP_EOL.number_format($time, 10, '.', '').'seconds';
