<?php
function getFile($url,$save_dir='',$filename='',$type=0){
  if(trim($url)==''){
   return false;
  }
  if(trim($save_dir)==''){
   $save_dir='./';
  }
  if(0!==strrpos($save_dir,'/')){
   $save_dir.='/';
  }
  if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
   return false;
  }//创建保存目录

  if($type){
    $ch=curl_init();
    $timeout=5;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1); //获取重定向后的网址
    $content=curl_exec($ch);
    curl_close($ch);
  }else{
      ob_start();
      readfile($url);
      $content=ob_get_contents();
      ob_end_clean();
  }
  $size=strlen($content); //文件大小
  $fp2=@fopen($save_dir.$filename,'a');
  fwrite($fp2,$content);
  fclose($fp2);
  unset($content,$url);
  return array('file_name'=>$filename,'save_path'=>$save_dir.$filename);
}

getFile("http://www0.tjcu.edu.cn/e/public/DownFile?fileid=11996","tempFiles","55".".doc",1); //调用