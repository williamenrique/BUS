<?php

class Logout{
	public function __construct(){
		session_start();
		$strHoraFin = date("g:i a");
		// endTimeline($_SESSION['strCodigo'],$strHoraFin);
		deleteSession($_SESSION['session_id']);
		session_unset();
		session_destroy();
		destroySession();
		header("Location:".base_url().'login');
	}
}