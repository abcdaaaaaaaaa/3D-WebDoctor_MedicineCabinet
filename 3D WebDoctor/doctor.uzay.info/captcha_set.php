<?php
session_start();
if(isset($_POST['v'])){
    $_SESSION['captcha_val']=$_POST['v'];
    $_SESSION['captcha_time']=time();
    unset($_SESSION['captcha_ok']);
}
?>
