<?Php
require 'loader.php';
$host=new imgur;
var_dump($host->upload($argv[1]));