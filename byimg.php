<?php
	@header('Content-type: text/html;charset=UTF-8');
	//o开1关
	$tool = array(
	"sql" => "0",
	"saveimg" => "1",
    "txt" => "0",
    'path'=>'txt',
	);
	$mysql_host=trim('localhost');
	$mysql_user=trim('root');
	$mysql_pwd=trim('root');
	$mysql_name=trim('fave');
	$mysql_port=trim('3306');
	$mysql_conn = @mysqli_connect($mysql_host,$mysql_user,$mysql_pwd,$mysql_name,$mysql_port) or die('数据库连接失败，请检查连接');
	if (!$mysql_conn) {
		mysqli_connect_error();
		exit();
	}

txt_read($tool);
carry($mysql_conn,$tool);

//执行
function carry($mysql_conn,$tool){
	select_table($mysql_conn);
	if ($tool['sql']==0) {
		if (select_table_ids($mysql_conn)==true) {
			return '写入成功';
		}else{
			return "数据已经存在";
		}
		mysqli_close($mysql_conn);
	}
	if ($tool['txt']==0) {
		
	}
}
//构造连接
function conn(){
	$mysql_host=trim('localhost');
	$mysql_user=trim('root');
	$mysql_pwd=trim('root');
	$mysql_name=trim('fave');
	$mysql_port=trim('3306');
	$mysql_conn = mysqli_connect($mysql_host,$mysql_user,$mysql_pwd,$mysql_name,$mysql_port);
	if (!$mysql_conn) {
		mysqli_connect_error();
	}
}
//查询数据表是否存在
function select_table($mysql_conn){ 
	  	$str = mysqlstr($sqlstr=5);
	  	mysqli_query($mysql_conn,$str);
	  	$reslut = mysqli_affected_rows($mysql_conn);
	  	if ($reslut==0) {
	  		create_table($mysql_conn);
	  		mysqli_close($mysql_conn);
	  		return true ; 
	  	}
	  	
}
//查询数据字段 为空则插入
function select_table_ids($mysql_conn){
	$str = mysqlstr($sqlstr=6);
	  	mysqli_query($mysql_conn,$str);
	  	$reslut = mysqli_affected_rows($mysql_conn);
	  	if ($reslut==0) {
	  		insert_table_data($mysql_conn);
	  		return true;
	  	}
}
//插入数据字段
function insert_table_data($mysql_conn) {
	mysqli_query($mysql_conn,mysqlstr($sqlstr=3));
	mysqli_close($mysql_conn);
	return true;
	
}
//文本数据方法
function txt_write_data($tool){
	$file='data.txt';
	$handle = fopen($tool['path'].'/'.$file, "a") or die("Unable to open file!");
	fwrite($handle, json_encode($tempArr) ."\r\n"); 
	fclose($handle);
}
function txt_write_ids($tool){
	$str = mysqlstr($sqlstr=7);
	$file='data.txt';
	$handle = fopen($tool['path'].'/'.$file, "w") or die("Unable to open file!");
	fwrite($handle, $str); 
	fclose($handle);
}
function txt_read_ids($tool){
	$file='date.txt';
	$myfile = fopen($tool['path'].'/'.$file, "r") or die("Unable to open file!");
	$str = fgets($myfile);
	fclose($myfile);
	return $str;
}

//字符串处理
function mysqlstr($sqlstr){
	$str = byimg();
	$into_date = $str['images'][0]['startdate'];
    $into_ids = $str['images'][0]['fullstartdate'];
	$into_imgurl = 'http://cn.bing.com'.$str['images'][0]['url'];
	$into_copyright = $str['images'][0]['copyright'];
	$into_copyrightlink = $str['images'][0]['copyrightlink'];
	if ($sqlstr==1) {
		return $mysqlselectdataone = "select *from fave_byimg order by rand() limit 1";//随机取出一条数据
	}
	if ($sqlstr==2) {
		return $mysqlselectdataids = "SELECT * from fave_byimg WHERE ids=$into_ids";//查询数据是否存在某一条数据
	}
	if ($sqlstr==3) {
		return $into_table="INSERT INTO fave_byimg (date, ids, imgurl, copyright, copyrightlink) VALUES ('".$into_date."','".$into_ids."','".$into_imgurl."','" .$into_copyright."','". $into_copyrightlink."')";//数据插入语句
	}
	if ($sqlstr==4) {
		return $tempArr = array("startdate"=>$into_date,"ids"=>$into_ids,"imgurl"=>$into_imgurl,"copyright"=>$into_copyright, "copyrightlink"=>$into_copyrightlink);// 以JSON输出txt数据
	}
	if ($sqlstr==5) {
		return $str = "show tables like 'fave_byimg'";//查询表是否存在
	}
	if ($sqlstr==6) {
		return $str="SELECT * FROM fave_byimg WHERE ids='".$into_ids."'";//查询字段是否存在
	}
	if ($sqlstr==7) {
		return $into_ids;//ids 供写入查询，减少对数据库连接
	}
}


//必应图片api接口处理
function byimg(){ 
	$str = file_get_contents('http://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1'); 
	//$str = '{"images":[{"startdate":"20180308","fullstartdate":"201803080801","url":"/az/hprichbg/rb/FearlessGirl_EN-US8770808173_1920x1080.jpg","copyright":"‘Fearless Girl,’ by Kristen Visbal, New York City (? Jeenah Moon/Bloomberg via Getty Images)","copyrightlink":"http://www.bing.com/search?q=fearless+girl&form=hpcapt&filters=HpDate:%2220180308_0800%22"}]}';
    $str = json_decode($str,true);
    return $str;
}
//创建数据表
function create_table($mysql_conn){ 
	$create_data_table="CREATE TABLE `fave_byimg` (
	  `id` int(10) NOT NULL AUTO_INCREMENT,
	  `ids` char(12) DEFAULT NULL,
	  `date` char(8) DEFAULT NULL,
	  `imgurl` varchar(255) DEFAULT NULL,
	  `copyright` varchar(255) DEFAULT NULL,
	  `copyrightlink` varchar(255) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `ids` (`ids`)
	) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8";
    mysqli_query($mysql_conn,$create_data_table);
}
?>
