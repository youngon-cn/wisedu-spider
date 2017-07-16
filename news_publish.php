<?php
header("Content-type: text/html; charset=utf-8");
set_time_limit(0);
define('HOST', '127.0.0.1');
define('USERNAME', 'root');
define('PASSWORD', '');
$GLOBALS['con'] = mysqli_connect(HOST, USERNAME, PASSWORD); //连接数据库
mysqli_query($con, 'set names utf8'); //设置字符集
mysqli_select_db($con, 'news');

$where = "where state = 0 or state = 2";
echo "<center>";

if (isset($_GET['act'])) {
	$cookie_file = tempnam('./temp','cookie'); //定义cookie储存文件
	if ($_GET['act']=='publish') {
		$login_url="http://ecms.tjcu.edu.cn/console/login.do"; //登录网址
		$loginName=; //用户名
		$password=; //密码
		$post_file = "loginName=$loginName&password=$password&validationCode=".$_POST['validationCode']; //POST内容
		$ch=curl_init($login_url); //初始化一个CURL对象
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); //返回值不直接输出在浏览器上
		curl_setopt($ch,CURLOPT_POST,1); //设置POST为普通的 application/x-www-from-urlencoded 类型
		curl_setopt($ch,CURLOPT_POSTFIELDS,$post_file);  //传递一个作为HTTP "POST"操作的所有数据的字符串。
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);  //把返回来的cookie信息保存在$cookie_jar文件中
		curl_exec($ch); //执行
		curl_close($ch); //关闭

		$catalogName = $_POST['column']; //栏目名
		$catalogPath = $_POST['id']; //栏目Id
		ob_start();
		ob_end_clean(); //在循环输出前，要关闭输出缓冲区
		echo str_pad('',1024); //浏览器在接受输出一定长度内容之前不会显示缓冲输出，这个长度值 IE是256，火狐是1024

        $sql = "SELECT count(*) FROM `news` ".$where;
        $result = mysqli_query($con, $sql,MYSQLI_USE_RESULT);
        while($row = mysqli_fetch_array($result)){
				$num=$row['count(*)'];
			}
		for ($i=0; $i < $num ; $i++) {
			$getid_url="http://ecms.tjcu.edu.cn/doc/saveDocument.do"; //获取新建文章Id的网址
			$ch = curl_init($getid_url); //初始化
			curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file); //设置cookie
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //禁止输出
			$callback = curl_exec($ch); //执行并获取返回值
			curl_close($ch); //关闭
			if (json_decode($callback)->status==1) {
				$pkId=json_decode($callback)->pkId; //获取新建文章Id
			} else {
				echo "获取文章Id出错";
				exit();
			}

			$save_url="http://ecms.tjcu.edu.cn/doc/saveDocument.do?saveType=save"; //保存文章网址
			$sql = "SELECT * FROM `news` ".$where." ORDER BY `state` DESC  limit 1";
			$result = mysqli_query($con, $sql,MYSQLI_USE_RESULT);
			while($row = mysqli_fetch_array($result)){
				$title=urlencode(stripslashes($row['title'])); //文章标题
				$source=$row['source']; //来源
				$time=$row['time']."+05:17:16"; //发布时间
				$cont=urlencode(stripslashes($row['cont'])); //文章内容
				$id=$row['id'];
                $state=$row['state'];
			}
            $ch = curl_init($save_url); //初始化
            if ($state=='0'){
                $post_file = "pkId={$pkId}&saveTypeAgain=edit&verifyTitle={$title}&userLink=1&publisherName=柴茂源&referName_val={$source}&content={$cont}&remoteImage=1&catalogPath={$catalogPath}&published={$time}&workFlowid=0"; //POST内容
            }elseif($state=='2'){
                $post_file = "pkId={$pkId}&saveTypeAgain=edit&verifyTitle={$title}&userLink=1&publisherName=柴茂源&redirectUrl={$cont}&remoteImage=1&catalogPath={$catalogPath}&published={$time}&workFlowid=0"; //POST内容
            }
            curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file); //设置cookie
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //禁止输出
            curl_setopt($ch,CURLOPT_POSTFIELDS,$post_file); //传递一个作为HTTP "POST"操作的所有数据的字符串
            $callback = curl_exec($ch); //执行
            //var_dump($callback);
            curl_close($ch); //关闭
			echo "<span style='color:red'>第".($i+1)."条</span> ";
			echo "<span style='color:blue'>".urldecode($title)."</span>";
			if (json_decode($callback)->status==1) {
				$sql = "UPDATE `news`.`news` SET `state` = '1' WHERE `news`.`id` = ".$id;
				mysqli_query($con, $sql,MYSQLI_USE_RESULT);
				echo " <span style='color:green'>[保存成功]</span>";
			} else{
				$sql = "UPDATE `news`.`news` SET `state` = '-1' WHERE `news`.`id` = ".$id;
				mysqli_query($con, $sql,MYSQLI_USE_RESULT);
				echo " <span style='color:red'>[保存失败]</span>";
			}
			echo "<br />";

			flush();
            ob_flush();
		}
		echo "文章保存完毕";
        echo "</center>";
	}
} else {
echo <<<EOF
	<html>
		<head>
			<meta charset="UTF-8"/>
			<title></title>
		</head>
		<body>
			<form action="?act=publish" method="post">
				<p>
					<img src="http://ecms.tjcu.edu.cn/valcode.jpg"/>
				</p>
				<p>
					<label for="column">栏目名：</label>
					<input type="text" name="column" />
				</p>
				<p>
					<label for="id">栏目Id：</label>
					<input type="text" name="id" />
				</p>
				<p>
					<label for="validationCode">验证码：</label>
					<input type="text" name="validationCode" />
				</p>
				<p>
					<input type="submit" value="登录"/>
				</p>
			</form>
		</body>
	</html>
EOF;
}
?>
