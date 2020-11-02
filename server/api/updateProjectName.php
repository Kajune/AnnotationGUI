<?php
	session_start();

	$name = $_POST['name'];
	$new_name = $_POST['new_name'];

	rename('../projects/'.$name, '../projects/'.$new_name);

	# Returning 0 to fire done in ajax
	echo 0;
?>
