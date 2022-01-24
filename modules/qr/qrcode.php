<?php
include "phpqrcode.php";
if(isset($_REQUEST["url"])) QRcode::png($_REQUEST["url"]);
//QRcode::png('htp://teste.com/?a=1&b=2');
?>


