<?php
	session_start();

	$name = basename($_POST['name']);
	$annotation = $_POST['annotation'];

	file_put_contents('../../projects/'.$name.'/annotation.json', $annotation);
	echo json_encode($_POST);
?>
