<?php
header('Access-Control-Allow-Origin: *');
class Errors extends Controllers{
	public function __construct(){
		//invocar para que se ejecute el metodo de la herencia
		parent::__construct();
	}
	public function notFound(){
			$data['page_name'] = "home";
		$this->views->getViews($this, "error",$data);
	}
}
$notFound = new Errors();
$notFound->notFound();