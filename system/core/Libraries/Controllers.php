	<?php 
class Controllers {
	public function __construct(){
		//instanciamos la clase de Viewa
		$this->views = new Views();
		$this->loadModel();
	}
	public function loadModel(){
		//obtenemos el nombre de la clase para concatenarla y obtenerla
		$model = get_class($this)."Model";
		$routClass ="system/app/Models/".$model.".php";
	//	echo $routClass;
		if (file_exists($routClass)) {
			require_once($routClass);
			$this->model = new $model();
		}else{
			//echo "<br>file no encotrado<br>";
		}
	}
}