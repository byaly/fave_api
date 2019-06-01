<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 16:21
 */

namespace Fave;

use Medoo\Medoo;

class Base
{
    public $medoo;

    public function __construct()
    {
        require_once 'lib/Medoo.php';
        $link = array(
            'database_type' => 'mysql',
            'database_name' => 'fave',
            'server' => 'localhost',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8'
        );
        $this->medoo = new medoo($link);
        $this->check();
    }

    public function check()
    {

    }

    /**接收方法
     * @param string $getStr
     * @return string
     */
    public function get($getStr = '')
    {
        $lists = null;
        if (empty($getStr)) {
            $lists = $_GET;
            if (count($lists) == 0) return false;
            $action = '';
            $arr = [];
            if (array_key_exists('ac',$lists)){
                $action = $lists['ac'];
                unset($lists['ac']);
            }
            foreach ($lists AS $k => $v){
                $k = filter_var(stripslashes(trim($k)), FILTER_SANITIZE_STRING);
                $v = filter_var(stripslashes(trim($v)), FILTER_SANITIZE_STRING);
                $arr[$k] = $v;
            }
            if ($action){
                $res[$action] = $arr;
            }else{
                $res = $arr;
            }
        } else {
            if (isset($_GET[$getStr])) $res = stripslashes($_GET[$getStr]);
        }
        return $res;
    }

    /**JSON返回格式
     * @param int $status
     * @param string $msg
     * @param string $data
     */
    public function jsonReturn($status = 0, $msg = '', $data = '')
    {
        header('Content-Type:application/json');
        if (is_array($status)) exit(json_encode($status));
        if (empty($data)) $data = '';
        $info['status'] = $status;
        $info['msg'] = $msg;
        $info['result'] = $data;
        exit(json_encode($info));
    }

    /**客户端ip
     * @return array|false|string
     */
    public function getClientIp()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR')) {
            $cip = getenv('REMOTE_ADDR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $cip = getenv('HTTP_CLIENT_IP');
        } else {
            $cip = 'unknown';
        }
        return $cip;
    }

    /**判断http or https
     * @return bool
     */
    function isHttps()
    {
        if (defined('HTTPS') && HTTPS) return true;
        if (!isset($_SERVER)) return FALSE;
        if (!isset($_SERVER['HTTPS'])) return FALSE;
        if ($_SERVER['HTTPS'] === 1) {  //Apache
            return TRUE;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return TRUE;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return TRUE;
        }
        return FALSE;
    }

    /**CURL获取数据
     * @param $url
     * @param int $type
     * @return bool|int|string
     */
    public function curl($url, $type = 1)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, 5000);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($type == 1) curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curl);
        if ($res) {
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            curl_close($curl);
            return $error;
        }
    }

    /**获取类方法名
     * @param string $class_name
     * @param string $modifier
     * @return array
     * @throws \ReflectionException
     */
    public function getClassMethod($class_name = '', $modifier = 'all')
    {
        $array1 = get_class_methods($class_name);
        if ($parent_class = get_parent_class($class_name)) {
            $array2 = get_class_methods($parent_class);
            $array3 = array_diff($array1, $array2);
        } else {
            $array3 = $array1;
        }
        foreach ($array3 as $k => $v) {
            $foo = new \ReflectionMethod($class_name, $v);
            $ctr = \Reflection::getModifierNames($foo->getModifiers());
            $foo = $foo->getParameters();
            $obj = array();
            if (count($foo) > 0) {
                foreach ($foo as $k2 => $v2) {
                    $vars = get_object_vars($v2);
                    $vars = array_values($vars);
                    $obj[] = $vars[0];
                }
            }
            if ($modifier == 'all'){
                $fooArr[$v] = $obj;
            }elseif ($modifier != 'all' && in_array($modifier,$ctr)){
                $fooArr[$v] = $obj;
            }
        }
        return $fooArr;
    }

    public function cache(){
        $redis_host = '127.0.0.1';
        $redis_port = 6379;
        $user_pwd = '123456';
        $redis = new \Redis();
        $redis->connect($redis_host, $redis_port);
        echo "Server is running: " . $redis->ping();
        if ($redis == false) {
            die($redis->getLastError());
        }
        if ($redis->auth($user_pwd) == false) {
            die($redis->getLastError());
        }
        if ($redis->set("welcome", "Hello, DCS for Redis!") == false) {
            die($redis->getLastError());
        }
        $value = $redis->get("welcome");
        echo $value;
        $redis->quit();
    }

}