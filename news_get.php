<?php
header("Content-type: text/html; charset=utf-8");
require_once('C2P.php');
set_time_limit(0);

define('HOST', '127.0.0.1');
define('USERNAME', 'root');
define('PASSWORD', '');
$GLOBALS['con'] = mysqli_connect(HOST, USERNAME, PASSWORD); //连接数据库
mysqli_query($con, 'set names utf8'); //设置字符集
mysqli_select_db($con, 'news');

$pageStart = 1;
$pageEnd = 1;

echo "<center>";

function getNews(&$con,&$num){
	if ($num==1) {
		$getlink_url="http://www.tjcu.edu.cn/zpxx/"; //文章列表网址
	}else{
		$getlink_url="http://www.tjcu.edu.cn/zpxx/index"."_".$num.".html"; //文章列表网址
	}
	$ch = curl_init($getlink_url); //初始化
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //禁止输出
	$callback = curl_exec($ch); //执行并获取返回值
	$callback = iconv("gbk","UTF-8//ignore",$callback);
	curl_close($ch); //关闭
	preg_match_all('/<li style="font-size:14px; margin-top:8px;">(.*?)<\/li>/', $callback, $linkArray); //获取文章链接
	preg_match_all('/<span>(.*?)<\/span>/', $callback, $timeArray); //获取文章链接

	//var_dump($linkArray);
	ob_start();
	ob_end_clean(); //在循环输出前，要关闭输出缓冲区
	echo str_pad('',1024); //浏览器在接受输出一定长度内容之前不会显示缓冲输出，这个长度值 IE是256，火狐是1024
    $py = new PinYin();
	for ($i=0; $i < sizeof($linkArray[1]); $i++) {
		preg_match_all('/href="(.*?)" title/', $linkArray[1][$i], $link[$i]); //获取文章链接
		$getinfo_url="http://www0.tjcu.edu.cn".$link[$i][1][0]; //获取新建文章Id的网址
		$ch = curl_init($getinfo_url); //初始化
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //禁止输出
		$callback = curl_exec($ch); //执行并获取返回值
		$callback = iconv("gbk","UTF-8",$callback); //编码转换
		curl_close($ch); //关闭

		$page = preg_replace('/\/d\/file(.*?)/','http://www0.tjcu.edu.cn/d/file',$callback); //替换图片链接
		$page = preg_replace('/\/e\/public(.*?)/','http://www0.tjcu.edu.cn/e/public',$page); //替换下载链接
        $page = preg_replace('/<td><img style="VERTICAL-ALIGN: baseline"(.*?)><\/td>/','<td>下载：<\/td>',$page); //替换下载图标链接


		preg_match_all('/<div style="font-size:14px; margin-top:8px; text-align:center;">(.*?)<\/div>/', $callback, $aboutArray); //获取文章发布信息
		if ($aboutArray[0]) {
			$about = $aboutArray[1][0];
			//var_dump($aboutArray);
			preg_match_all('/：(.*?)&nbsp;&nbsp;/', $about, $about);
			//var_dump($about);
            $source = strtoupper($py->getFirstPY($about[1][0])); //文章来源
			$sourceZN = $about[1][0]; //文章来源
			$time = $about[1][1]; //文章发布时间


			preg_match_all('/<div style="font-size:16px; margin-top:8px; text-align:center; font-weight:bold;">(.*?)<\/div>/', $callback, $titleArray);//获取文章标题
			$title = addslashes($titleArray[1][0]); //文章标题
			//var_dump($titleArray);

			preg_match_all('/<div class="cont">(.*?)<\/div>/', $page, $contArray); //获取文章主体
			$cont = addslashes($contArray[1][0]); //文章主体
            $cont = str_replace('&nbsp;&nbsp;&nbsp;','&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',$cont);
			//var_dump($contArray);

			$sql= "INSERT INTO `news`.`news` (`id`, `title`, `source`, `sourceZN`, `time`, `cont`, `state`) VALUES (NULL, '$title', '$source', '$sourceZN', '$time', '$cont', '0')";
			$result = mysqli_query($con, $sql);
			if ($result) {
				echo "<span style='color:red'>".$num."-".($i+1)."</span>".$title."<span style='color:green'>[获取完毕]</span><br />";
			}else {
				echo "<span style='color:red'>".$num."-".($i+1)."写入数据库失败</span><br />";
			}

		}else {
			$time=$timeArray[1][$i];
            $url=$link[$i][1][0];
            preg_match_all('/>(.*?)<\/a>/', $linkArray[1][$i], $title);
            $title=$title[1][0];
			$sql= "INSERT INTO `news`.`news` (`id`, `title`, `source`, `sourceZN`, `time`, `cont`, `state`) VALUES (NULL, '$title', 'LJTZ', '链接跳转', '$time', '$url', '2')";
			$result = mysqli_query($con, $sql);
			if ($result) {
				echo "<span style='color:blue'>".$num."-".($i+1).$title."[获取异常，该文章可能指向另一网页，已设置state值为2]</span><br />";
			}else {
				echo "<span style='color:red'>".$num."-".($i+1)."写入数据库失败</span><br />";
			}
		}
		flush();
        ob_flush();
	}
}

for ($j=$pageStart; $j <= $pageEnd; $j++) {
	getNews($con,$j);
}
echo "新闻获取完毕";
echo "</center>";
?>
