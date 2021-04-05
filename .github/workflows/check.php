<?php 
require_once './vendor/autoload.php'; 
$pack = new Max_WP_Package('package.zip');
echo $pack->get_metadata()['version'];