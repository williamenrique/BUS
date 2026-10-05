<?php
require_once("system/core/Config/config.system.php");
require_once("system/core/Helpers/Helpers.php");
//si la variable esxiste cargarla con la url
//si no existe se le asigna home
//obtenemos ya un controlador
$url = !empty($_GET['url']) ? $_GET['url'] : "home/home";
//convertir la variable en un array
$arr_url = explode("/",$url);
//crear variable desglosando la variable
$controller = $arr_url[0];
$method = $arr_url[0];
$params = "";

//validar si llega un valor vacio en el metodo
if (!empty($arr_url[1])) {
	if ($arr_url[1] != "") {
		$method =$arr_url[1];
	}
}

if (!empty($arr_url[2])) {
	if ($arr_url[2] != "") {
		for ($i=2; $i < count($arr_url); $i++) { 
			$params .= $arr_url[$i].',';
		}
		$params = trim($params, ',');
		//echo $params;
	}
}
// require_once FOOTER;
require_once('system/core/Libraries/autoLoad.php');
require_once('system/core/Libraries/Load.php');