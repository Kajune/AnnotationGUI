<?php
	session_start();

	$name = $_POST['name'];
	$new_name = $_POST['new_name'];
	$project_dir = '/var/www/html/projects/';

	rename('../projects/'.$name, '../projects/'.$new_name);
	exec('python3 updateProjectName.py '.$project_dir.' '.$name.' '.$new_name);

	# Returning 0 to fire done in ajax
	echo 0;
?>
