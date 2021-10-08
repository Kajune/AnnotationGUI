<?php
	header("Content-Type: application/json", true);
	file_put_contents($_POST['project_url'].'tmp.txt', json_encode($_POST));
	exec('python3 ../../api/predict_next_frame_'.$_POST['algorithm'].'.py '.$_POST['project_url'].'tmp.txt', $output, $return_var);
	echo json_encode($output);
	exit;
?>
