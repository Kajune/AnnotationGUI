<?php
	file_put_contents($_GET['project_url'].'tmp.txt', json_encode($_GET));
	exec('python3 ../../api/predict_next_frame.py '.$_GET['project_url'].'tmp.txt', $output, $return_var);
	echo json_encode($output);
?>
