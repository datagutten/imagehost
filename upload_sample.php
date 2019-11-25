<?Php
require 'vendor/autoload.php';
use datagutten\image_host\imgur;
$host=new imgur;
var_dump($host->upload($argv[1]));