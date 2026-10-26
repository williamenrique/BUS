<?php
echo session_save_path() . "<br>";
var_dump(is_writable(session_save_path()));
session_start();
$_SESSION['test'] = 'ok';
echo "Session funciona correctamente";
?>