<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 16:20
 */

namespace Fave;

use http\Url;

require_once 'Base.php';

class Controller extends Base
{
    public $_imgDir = './public/image/';

    public function __construct()
    {
        parent::__construct();
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
        foreach ($this->config['noPublic'] as $bboom => $oobbm) if (array_key_exists($oobbm, $function)) unset($function[$oobbm]);
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
        $type == 'json' ? $this->jsonReturn(1, '获取成功', $res) : false;
        is_file($id) ? $this->showImg($id) : $this->jsonReturn(-6, 'Request file does not exist');
    }

    /**PPX Video parsing
     * @param $url
     */
    public function ppx($url)
    {
        $this->saveView(true);
        if (empty($url)) $this->jsonReturn(-1, 'Value cannot be empty');
        $res = $this->getPPX($url);
        $res['status_code'] != 11001 ? $this->jsonReturn(1, '获取成功', $res) : $this->jsonReturn(-1, $res['message'], '');
    }

    /** Random Recording
     * @return array
     */
    private function getImgRandomJson()
    {
        $random_1 = 'SELECT * FROM `fave_img` AS t1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `fave_img`)-(SELECT MIN(id) FROM `fave_img`))+(SELECT MIN(id) FROM `fave_img`)) AS id) AS t2 WHERE t1.id >= t2.id ORDER BY t1.id LIMIT 1;';
        $res = $this->medoo->query($random_1)->fetchAll();
        $data['imagesurl'] = $this->splicing($res[0]['imagesurl'], 2);
        $data['copyright'] = $res[0]['copyright'];
        $data['savepath'] = substr($res[0]['savepath'], 1);
        $data['saveid'] = $res[0]['saveid'];
        return $data;
    }

    /** save data info
     * @return array
     */
    public function saveDataInfo()
    {
        $img = $this->getImgInfo();
        if ($img && !isset($iciba['collection'])) {
            $res = $this->saveImg($img['imagesurl']);
            if (is_array($res) && $res['status'] > 0) {
                $isExit = $this->medoo->get('fave_img', 'id', ['saveid' => $res['file_id']]);
                if ($isExit === false) {
                    $img = array_merge($img, array('savepath' => $res['save_path'], 'saveid' => $res['file_id']));
                    $this->medoo->insert('fave_img', $img);
                    if ($this->medoo->id() > 0) $arr[] = array('status' => 1, 'msg' => 'Img', 'result' => $this->medoo->id());
                } else {
                    $arr[] = array('status' => -4, 'msg' => 'Img Data exists');
                }
            } else {
                $arr[] = $res;
            }
        }
        $iciba = $this->getIcibaInfo();
        if ($iciba && !isset($iciba['collection'])) {
            $this->medoo->insert('fave_sentence', $iciba);
            if ($this->medoo->id() > 0) $arr[] = array('status' => 1, 'msg' => 'Iciba', 'result' => $this->medoo->id());
        } else {
            $arr[] = array('status' => -5, 'msg' => 'Iciba Data exists');
        }
        return $arr;
    }

    /** Save pictures
     * @param $url
     * @return array|bool
     */
    private function saveImg($url)
    {
        if (trim($url) == '') $this->jsonReturn(-1, 'The URL address is empty');
        $url_exp = explode('.', $url);
        $url = 'https://cn.bing.com' . $url . '_1920x1080.jpg';
        $filename = $url_exp[1] . '_1920x1080.jpg';
        $save_dir = $this->_imgDir;
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) return array('status' => -2, 'msg' => 'Failed to create image directory');
        $save_dir .= $filename;
        if (file_exists($save_dir)) return array('status' => -3, 'msg' => 'Image file exists');
        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();
        $fp2 = @fopen($save_dir, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        return array('file_id' => $url_exp[1], 'file_name' => $filename, 'save_path' => $save_dir);
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

    /** Download File
     * @param $url
     * @param $path
     */
    private function downFile($url, $path)
    {
        $arr = parse_url($url);
        $fileName = basename($arr['path']);
        $file = file_get_contents($url);
        file_put_contents($path . $fileName, $file);
    }

    /** pick up information
     * @param string $lang
     * @return array
     */
    private function getImgInfo($lang = 'zh-CN')
    {
        if ($this->config['collection']['bing'] === false) return array('status' => -1, 'collection' => 'bing');
        $url = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=1&n=1&mkt=' . $lang;
        if (function_exists('curl_init')) {
            $res = $this->curl($url);
        } else {
            $opts = array('https' => array('method' => 'GET', 'timeout' => 3));
            $context = stream_context_create($opts);
            $res = file_get_contents($url, false, $context);
        }
        $res = json_decode($res, true);
        $byimg_enddate = $res['images'][0]['enddate'];
        $byimg_urlbase = $res['images'][0]['urlbase'];
        $byimg_copyright = $res['images'][0]['copyright'];
        return array('enddate' => $byimg_enddate, 'imagesurl' => $byimg_urlbase, 'copyright' => $byimg_copyright);
    }

    /** pick up information
     * @return bool
     */
    private function getIcibaInfo()
    {
        if ($this->config['collection']['iciba'] === false) return array('status' => -1, 'collection' => 'iciba');
        $nowtime = date('Y-m-d');
        $isExit = $this->medoo->get('fave_sentence', 'id', ['title' => $nowtime]);
        if ($isExit === false) {
            $url = 'http://sentence.iciba.com/index.php?c=dailysentence&m=getdetail&title=' . $nowtime . '&_=' . time();
            $json_string = $this->httpGet($url);//curl 自定义函数访问api
            $data = json_decode($json_string, true);//解析json 转为php
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
        $json_string = $this->httpGet($url, true);//curl 自定义函数访问api
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

//    /** Network Request Source
//     * @return mixed
//     */
//    private function referer()
//    {
//        if (isset($_SERVER['HTTP_ORIGIN']) && isset($_SERVER['HTTP_REFERER'])) {
//            $url = parse_url($_SERVER['HTTP_REFERER']);
//            return $url['host'];
//        } else {
//            return $_SERVER['HTTP_HOST'];
//        }
//    }

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
                $str = $str = 'https://cn.bing.com' . $str . '_1920x1080.jpg';
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

