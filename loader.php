<?php
spl_autoload_register(function ($class_name) {
   	require sprintf(__DIR__.'/sites/%1$s/%1$s.php',$class_name);
});
