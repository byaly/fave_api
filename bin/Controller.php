<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 16:20
 */

namespace Fave;

use http\Url;
use Medoo\Medoo;

require_once 'Base.php';

class Controller extends Base
{
    public $_imgDir = './public/image/';

    public function __construct()
    {
        parent::__construct(get_class());
        $this->index();
    }

    /** Main
     * @throws \ReflectionException
     */
    private function index()
    {
        $getArr = $this->get();
        $class_name = get_class();
        $function = $this->getClassMethod($class_name, 'public');
        foreach ($this->config['noPublic'] as $bboom => $oobbm){
            if (array_key_exists($bboom, $function)){
                if (empty($oobbm))unset($function[$oobbm]);
            }
        }
        if (is_array($getArr) && count($getArr) > 0) {
            $result = array('status' => 404);
            foreach ($getArr AS $k => $v) {
                if (isset($function[$k]) && method_exists($class_name, $k)) {
                    $args = [];
                    if (count($v) > 0) {
                        foreach ($function[$k] as $kk => $vv) {
                            $args[$kk] = '';
                            if (isset($v[$vv])) $args[$kk] = $v[$vv];
                        }
                    }
                    try {
                        $result = call_user_func_array(array($class_name, $k), $args);
                    } catch (\Exception $exception) {
                        echo "{$exception->getLine()}-" . $exception->getMessage();
                        die;
                    } catch (\Error $error) {
                        echo "{$error->getLine()}-" . $error->getMessage();
                        die;
                    }
                }
            }
            if (!empty($result) || is_array($result)) $this->jsonReturn($result);
        } else {
            echo 'What do you want to do?';
            $excludeArr = array('index');
            foreach ($function as $k => $v) {
                $v = implode(',', $v);
                if (!in_array($k, $excludeArr)) echo PHP_EOL . "$k($v);";
            }
        }
    }

    /** Get picture data
     * @param null $n
     */
    public function getImg($id = '', $type = '')
    {
        if (!$type) $type = 'show';
        $ref = $this->referer();
        if (empty($id)) {
            $res = $this->getImgRandomJson();
            $id = $res['savepath'];
            $res['referer'] = $ref;
            $a = $this->splicing($id);
        } else {
            $a = $id = $this->splicing($id, 1);
        }
        $this->saveView(true, $a);
        $res['imagesurl'] = SITE_URL . substr($res['savepath'],'1');
        unset($res['savepath']);
        $type == 'json' ? $this->jsonReturn(1, '获取成功', $res) : false;
        is_file($id) ? $this->showImg($id) : $this->jsonReturn(-6, 'Request file does not exist');
    }

    /**PPX Video parsing
     * @param $url
     */
    public function ppx($url)
    {
        if (empty($url)) $this->jsonReturn(-1, 'Value cannot be empty');
        $this->saveView(true);
        $res = $this->getPPX($url);
        $res['status_code'] != 11001 ? $this->jsonReturn(1, '获取成功', $res) : $this->jsonReturn(-1, $res['message'], '');
    }

    /** Random Recording
     * @return array
     */
    private function getImgRandomJson()
    {
        $res = $this->medoo->rand('fave_img',['copyright','savepath','date'],['LIMIT'=>1]);
        if (count($res) === 1 ) $res = $res[0];
        return $res;
    }

    /** save data info
     * @return array
     */
    public function saveDataInfo()
    {
        $img = $this->getImgInfo();
        if ($img && is_array($img)) {
            $img['imagesurl'] = $this->splicing($img['imagesurl'],2);
            $saveName = $img['bid'] . '_1920x1080.jpg';
            $res = $this->curlDownFile( $img['imagesurl'], $this->_imgDir, $saveName);
            $isExit = $this->medoo->get('fave_img', 'id', ['bid' => $img['bid']]);
            $img = array_merge($img, array('savepath' => $this->_imgDir . $saveName));
            if ($res === true){
                if (!$isExit || $res === false){
                    $this->medoo->insert('fave_img', $img);
                    if ($this->medoo->id() > 0) $arr[] = array('status' => 1, 'msg' => 'Img', 'result' => $this->medoo->id());
                }
            }elseif ($res == 200){
                //文件存在
                $this->medoo->update('fave_img',$img,['id' => $img['bid']]);// 更新数据
                $res = array('status' => -3, 'msg' => 'File exists');
                $arr[] = $res;
            }
        }
        $iciba = $this->getIcibaInfo();
        if ($iciba && is_array($iciba)) {
            $this->medoo->insert('fave_sentence', $iciba);
            if ($this->medoo->id() > 0) $arr[] = array('status' => 1, 'msg' => 'Iciba', 'result' => $this->medoo->id());
        } else {
            $arr[] = array('status' => -4, 'msg' => 'Data exists');
        }
        return $arr;
    }

    /** data statistics
     * @param $saveid
     */
    private function saveView($action = false, $saveid = '')
    {
        if ($action && $saveid) $this->medoo->update('fave_img', ['view[+]' => 1], ['saveid' => $saveid]);
        $server = $this->getServerFunction();
        $server['referer'] ? $w = ['referer' => $server['referer'], 'service' => $server['service']] : $w = ['ip' => $server['ip'], 'service' => $server['service']];
        $isExit = $this->medoo->get('fave_referer', 'id', $w);
        $isExit ? $data = ['id' => $isExit] : $data = $server;
        $isExit ? $this->medoo->update('fave_referer', ['count[+]' => 1, 'time' => time()], $data) : $this->medoo->insert('fave_referer', $data);
    }

    /** download file
     * @param $img_url
     * @param string $save_path
     * @param string $filename
     * @return bool|int
     */
    private function curlDownFile($img_url, $save_path = '', $filename = '') {
        if (trim($img_url) == '') return false;
        if (trim($save_path) == '') $save_path = './';
        if (!file_exists($save_path) && !mkdir($save_path, 0777, true)) return false;
        if (trim($filename) == '') {
            $img_ext = strrchr($img_url, '.');
            $img_exts = array('.gif', '.jpg', '.png', '.mp4','jpeg');
            if (!in_array($img_ext, $img_exts)) return false;
            $filename = time() . $img_ext;
        }
        $filename = $save_path . $filename;
        if (is_file($filename) && filesize($filename) > 1024) return 200;
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $img_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $img = curl_exec($ch);
        curl_close($ch);
        $status = false;
        $wa = file_put_contents($filename, $img);
        if ($img && $wa) $status = true;
        unset($img, $img_url,$filename);
        return $status;
    }

    /** pick up information
     * @param string $lang
     * @return array
     */
    private function getImgInfo($lang = 'zh-CN')
    {
        if ($this->config['collection']['bing'] === false) return array('status' => -1, 'collection' => 'bing');
        $url = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=' . $lang;
        if (function_exists('curl_init')) {
            $res = $this->curl($url);
        } else {
            $opts = array('https' => array('method' => 'GET', 'timeout' => 3));
            $context = stream_context_create($opts);
            $res = file_get_contents($url, false, $context);
        }
        $res = json_decode($res, true);
        $byimg_date = $res['images'][0]['enddate'];
        $byimg_urlbase = $res['images'][0]['urlbase'];
        $byimg_copyright = $res['images'][0]['copyright'];
        $bid = substr($byimg_urlbase,strpos($byimg_urlbase,'.') + 1);
        return array('date' => $byimg_date, 'imagesurl' => $byimg_urlbase, 'copyright' => $byimg_copyright,'bid' => $bid);
    }

    /** pick up information
     * @return bool
     */
    private function getIcibaInfo()
    {
        if ($this->config['collection']['iciba'] === false) return array('status' => -1, 'collection' => 'iciba');
        $nowtime = date('Y-m-d');
        $isExit = $this->medoo->get('fave_sentence', 'id', ['title' => $nowtime]);
        if ($isExit === false || !$isExit) {
            $url = 'http://sentence.iciba.com/index.php?c=dailysentence&m=getdetail&title=' . $nowtime . '&_=' . time();
            $json_string = $this->curl($url);
            $data = json_decode($json_string, true);
            $arr['content'] = $data['content'];
            $arr['note'] = $data['note'];
            $arr['title'] = $data['title'];
            if (isset($data['translation'])) {
                $text2 = str_replace('小编的话：', '', $data['translation']);
                $text2 = str_replace('词霸小编：', '', $text2);
                $arr['translation'] = $text2;
            }
            return $arr;
        } else {
            return false;
        }
    }

    private function getPPX($url)
    {
        $json_string = $this->httpGet($url, true);
        $arr = false;
        if (is_array($json_string) && isset($json_string['location'])) {
            $url = parse_url($json_string['location']);
            $ppxid = str_ireplace('/item/', '', $url['path']);
            $url = 'https://h5.pipix.com/bds/webapi/item/detail/?item_id=' . $ppxid;
            $result = $this->httpGet($url);
            $res = json_decode($result, true);
            if ($res['status_code'] == 11001) {
                $arr = $res;
            } else {
                $arr['video_title'] = $res['data']['item']['video']['text'];
                $arr['video_image'] = $res['data']['item']['video']['cover_image']['url_list'][0]['url'];
                $arr['video_url'] = $res['data']['item']['video']['video_fallback']['url_list'][0]['url'] . '#' . $_SERVER['HTTP_HOST'];
            }
        }
        return $arr;
    }

    /** display picture
     * @param $img
     */
    private function showImg($img)
    {
        header('Content-type:image/png');
        echo file_get_contents($img);
    }

    /** Character mosaic
     * @param $str
     * @param int $action
     * @return mixed|string
     */
    private function splicing($str, $action = 0)
    {
        switch ($action) {
            case 1:
                $str = './public/image/' . $str . '_1920x1080.jpg';
                break;
            case 2:
                $str = $str = 'http://bing.com' . $str . '_1920x1080.jpg';
                break;
            default :
                $str = str_replace('_1920x1080.jpg', '', str_replace('/public/image/', '', $str));
                break;
        }
        return $str;
    }

    /** Network Request
     * @param $url
     * @param bool $location
     * @param bool $nobody
     * @return array|bool|string
     */
    private function httpGet($url, $location = false, $nobody = false)
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        header('Content-type:text/html;charset=utf-8');
        $curl = curl_init();
        $httpheader[] = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3';
        $httpheader[] = 'Accept-Language:zh-CN,zh;q=0.9';
        $httpheader[] = 'Connection:close';
        $ip = mt_rand(11, 191) . '.' . mt_rand(0, 240) . '.' . mt_rand(1, 240) . '.' . mt_rand(1, 240);
        $httpheader[] = array(
            'CLIENT-IP:' . $ip,
            'X-FORWARDED-FOR:' . $ip,
        );
        if ($nobody) curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl,CURLOPT_HEADER,true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $location);
        $res = curl_exec($curl);
        $locationUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        curl_close($curl);
        if ($location) return array('location' => $locationUrl);
        return $res;
    }
}

