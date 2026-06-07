<?php
session_start();

if(!isset($_SESSION['captcha_val'])||!isset($_SESSION['captcha_time'])){echo "FAIL";exit;}
if(time()-$_SESSION['captcha_time']>120){unset($_SESSION['captcha_ok']);echo "FAIL";exit;}

$input=isset($_POST['v'])?$_POST['v']:'';
if($input!=='' && $input===$_SESSION['captcha_val']){
    $_SESSION['captcha_ok']=1;
    echo "OK";
}else{
    echo "FAIL";
}
?>
