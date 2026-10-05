<?php
//funcion para convertir primera letra en mayusculas para servidores sensibles a minuscula y mayusculas
$controller = ucwords($controller);
//load requerido para cargar el controlador
$controllerFile = "system/app/Controllers/".$controller. "Controller.php";
if (file_exists($controllerFile)) {
	require_once($controllerFile );
	//instanciamos el controlador
	$controller = new $controller();
	//validar si existe el metodo
	if (method_exists($controller, $method)) {
		//enviamos el metodo al controlador y los paramaetros si existen
		$controller->{$method}($params);
	}else{
		//hacemos uso del controlador del error
		require_once("system/app/Controllers/ErrorController.php");
	}
}else{
	require_once("system/app/Controllers/ErrorController.php");
}