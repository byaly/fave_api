<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/6/19
 * Time: 16:20
 */
return array(
//    Setting of acquisition parameters
    'collection' => array(
        'bing' => true,
        'iciba' => true,
    ),
//    Database Settings
    'database' => array(
        'database_type' => 'mysql',
        'database_name' => 'fave',
        'server' => 'localhost',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8'
    ),
//    An undisclosed approach
    'noPublic'=>array('saveDataInfo'),
);