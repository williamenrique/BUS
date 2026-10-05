<?php
spl_autoload_register(function($class){
	if(file_exists(LIBS .$class .".php")){
		require_once(LIBS .$class .".php");
	}
});