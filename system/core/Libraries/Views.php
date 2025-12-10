<?php
class Views {
	//recibe 2 variables el controlador y la vista
	public function getViews($controller , $view,$data=""){
		//recibe la clase que pasamos por parametro
		$controller = get_class($controller);
		//validamos que sea home y validamos buscamos la vista
		if ($controller == "Home") {
			$view = VIEWS.$view.".php";
		}else{
			$view = VIEWS.$controller."/".$view.".php";
		//	$view = VIEWS.$view.".php";
		}
		require_once($view);
	}
}