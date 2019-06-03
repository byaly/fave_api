<?php
/**
 * Created by PhpStorm.
 * User: nermif
 * Date: 2019/2/28
 * Time: 16:20
 */

namespace Fave;
require_once 'Base.php';

class Controller extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->index();
//        define('IS_POST',REQUEST_METHOD =='POST' ? true : false);
    }

    private function index()
    {
        $getArr = $this->get();
        $class_name  = get_class();
        $function = $this->getClassMethod($class_name,'public');
        if (is_array($getArr) && count($getArr) >0 ){
            $result = array('status'=>404);
            foreach ($getArr AS $k=>$v){
                if (isset($function[$k]) && method_exists($class_name, $k)){
                    $args = [];
                    if (count($v) > 0){
                        foreach ($function[$k] as $kk=>$vv){
                            $args[$kk] = '';
                            if (isset($v[$vv])) $args[$kk] = $v[$vv];
                        }
                    }
                    $result = call_user_func_array(array($class_name,$k), $args);
                }
            }
            if (!empty($result) || is_array($result)) $this->jsonReturn($result);
        }else{
            echo 'What do you want to do?';
            $excludeArr = array('index');
            foreach ($function as $k =>$v){
                $v = implode(',',$v);
                if (!in_array($k,$excludeArr)) echo PHP_EOL."$k($v);";
            }
        }
    }

    /**随机一张图片
     * @param null $n
     */
    public function getImg($id = '',$type = ''){
        if ($id){
            $id = self::reduction($id,1);
        }else{
            $res = $this->getImgRandomJson();
            $id = $res['result']['savepath'];
            if ($type == 'json'){
                $id = self::reduction($id,0);
                $this->jsonReturn(1,'获取成功',array('id'=>$id));
            }
        }
        $this->showImg($id);
    }

    /**获取系统信息
     * @return array
     */
//    public function systemInfo(){
//        $sys_info['os']             = PHP_OS;
//        $sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
//        $sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off
//        $sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
//        $sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';
//        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
//        $sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
//        $sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
//        $sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
//        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
//        $sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
//        $sys_info['memory_limit']   = ini_get('memory_limit');
//        $sys_info['system_version'] = VERSION;
//        $mysqlinfo                  = $this->medoo->query("SELECT VERSION() as version")->fetchAll();
//        $sys_info['mysql_version']  = $mysqlinfo[0]['version'];
//        $sys_info['phpv']           = phpversion();
//        return array('status'=>1,'msg'=>'获取成功','result'=>$sys_info);
//    }



    /**获取随机的一条记录
     * @return array
     */
    private function getImgRandomJson()
    {
//        self::referer();
        $random_1 = 'SELECT * FROM `fave_img` AS t1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `fave_img`)-(SELECT MIN(id) FROM `fave_img`))+(SELECT MIN(id) FROM `fave_img`)) AS id) AS t2 WHERE t1.id >= t2.id ORDER BY t1.id LIMIT 1;';
        $res = $this->medoo->query($random_1)->fetchAll();
        $data['imagesurl'] = $res[0]['imagesurl'];
        $data['copyright'] = $res[0]['copyright'];
        $data['savepath'] = $res[0]['savepath'];
        $str = 'https://cn.bing.com';
        if (!strstr($data['imagesurl'], $str)) $data['imagesurl'] = $str . $data['imagesurl'] . '_1920x1080.jpg';
//        if (!empty($data['savepath']))
//            $data['savepath'] = self::reduction($data['savepath']);
        return array('status'=>1,'msg'=>'获取成功','result'=>$data);
    }

    /**
     *保存信息
     * @return array
     */
    private function saveDataInfo()
    {
        $img = $this->getImgInfo();
        $res = $this->saveImg($img['imagesurl']);
        if (is_array($res)) {
            $img = array_merge($img, array('savepath' => $res['save_path']));
            $this->saveTxt($img);
            $this->medoo->insert('fave_img', $img);
            $data_id = $this->medoo->id();
            if ($data_id > 0) return array('status'=>1,'msg'=>'插入数据成功','result'=>$data_id);
        }
    }

    /**保存文本
     * @param $byimg_info
     */
    private function saveTxt($byimg_info)
    {
        $myfile = fopen('public/data/data.txt', 'a+');
        fwrite($myfile, json_encode($byimg_info) . PHP_EOL);
        fclose($myfile);
    }

    /**保存图像
     * @param $url
     * @return array|bool
     */
    private function saveImg($url)
    {
        if (trim($url) == '') $this->jsonReturn(-1, 'URL地址为空');
        $url_exp = explode('.', $url);
        $url = 'https://cn.bing.com' . $url . '_1920x1080.jpg';
        $filename = $url_exp[1] . '_1920x1080.jpg';
        $save_dir = 'public/image/';
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) $this->jsonReturn(-2, '创建图像目录失败');
        $save_dir .= $filename;
        if (file_exists($save_dir)) $this->jsonReturn(-3, '图像文件已存在');
        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();
        $fp2 = @fopen($save_dir, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        return array('file_name' => $filename, 'save_path' => $save_dir);
    }

    /**下载文件
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

    /**获取必应数据
     * @param string $lang
     * @return array
     */
    private function getImgInfo($lang = 'zh-CN')
    {
        $url = 'https://cn.bing.com/HPImageArchive.aspx?format=js&idx=1&n=1&mkt=' . $lang;
        if (function_exists('curl_init')) {
            $res = $this->curl($url);
        } else {
            $opts = array('https' => array('method' => 'GET', 'timeout' => 3));
            $context = stream_context_create($opts);
            $res = file_get_contents($url, false, $context); //读取必应api，获得相应数据
        }
        $res = json_decode($res, true);
        $byimg_enddate = $res['images'][0]['enddate'];//图片结束日期
        $byimg_urlbase = $res['images'][0]['urlbase'];
        $byimg_copyright = $res['images'][0]['copyright'];
        return array('enddate' => $byimg_enddate, 'imagesurl' => $byimg_urlbase, 'copyright' => $byimg_copyright);
    }
    private function showImg($img){
        $info = getimagesize($img);
        $imgExt = image_type_to_extension($info[2], false);
        $fun = "imagecreatefrom{$imgExt}";
        $imgInfo = $fun($img);
        $mime = mime_content_type($img);
        header('Content-Type:'.$mime);
        $quality = 100;
        if($imgExt == 'png') $quality = 9;
        $getImgInfo = "image{$imgExt}";
        $getImgInfo($imgInfo, null, $quality);
        imagedestroy($imgInfo);
    }
    private function referer(){
        $fromurl = $_SERVER['HTTP_REFERER'].substr($_SERVER['PHP_SELF'],1);
        if( SITE_URL.$_SERVER['PHP_SELF'] != $fromurl )
        {
            header('Location:'.SITE_URL);
            exit;
        }
    }

    private function reduction($id ,$action = 0){
        if ($action == 0){
            return str_replace('_1920x1080.jpg','',str_replace('public/image/','',$id));
        }else{
            //供图片链接
            $id = './public/image/'.$id.'_1920x1080.jpg';
            return $id;
        }
    }
}

