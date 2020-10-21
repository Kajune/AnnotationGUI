<?php
	session_start();
	header('Expires:-1');
	header('Cache-Control:');
	header('Pragma:');
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="content-type" charset="utf-8">

	<title>Super Sophisticated Annotation GUI</title>

	<link rel="stylesheet" type="text/css" href="../../css/bootstrap.min.css">
	<script type="text/javascript" src="../../js/jquery-3.5.1.min.js"></script>
	<script type="text/javascript" src="../../js/bootstrap.bundle.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../../css/style.css">
</head>
<body>

<div class="container header" id="container">
	<h1>Create New Project</h1>
	<br>

	<template id="alert-success">
		<div class="alert alert-primary alert-dismissible fade show" role="alert">
			Project Successfully Created.
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</template>

	<template id="alert-fail">
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			<div class="fail-msg"></div>
			<button type="button" class="close" data-dismiss="alert" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</template>

<script type="text/javascript">
	function successAlert() {
		var template = document.getElementById('alert-success');
		var clone = template.content.cloneNode(true);
		document.getElementById('container').appendChild(clone);
	}

	function failAlert(msg) {
		var template = document.getElementById('alert-fail');
		var clone = template.content.cloneNode(true);
		clone.querySelector('.fail-msg').innerHTML = msg;
		document.getElementById('container').appendChild(clone);
	}
</script>

<?php
	$enames = scandir('../../projects');

	$min_fps = 0.01;
	$max_fps = 100;

	if($_SERVER['REQUEST_METHOD'] === 'POST'){
		$name = $_POST['artwork-name'];
		$tag = $_POST['artwork-tag'];
		$comment = $_POST['artwork-comment'];

		$bad_flag = false;
		foreach ($names as $ename) {
			if (strcmp($name, $ename) == 0) {
				echo '<script type="text/javascript">failAlert("Specified project name already exists.")</script>';
				$bad_flag = true;
				break;
			}
		}

		if (!$bad_flag) {
			if(isset($_FILES) && isset($_FILES['artwork-image']) && is_uploaded_file($_FILES['artwork-image']['tmp_name'])){
				$a = uniqid().'.jpg';
				if (move_uploaded_file($_FILES['artwork-image']['tmp_name'], '../img/artwork/'.$a)) {
					$name = htmlspecialchars($name);
					$tag = htmlspecialchars($tag);
					$comment = htmlspecialchars($comment);

					$stmt = mysqli_prepare($sql, "INSERT INTO artwork (name, tag, comment, img, last_update) VALUES (?,?,?,?, CURDATE())");
					mysqli_stmt_bind_param($stmt, "ssss", $name, $tag, $comment, $a);
					if (mysqli_stmt_execute($stmt)) {
						echo '<script type="text/javascript">successAlert()</script>';
					} else {
						echo mysqli_error($sql);
					}
				} else {
					echo '<script type="text/javascript">failAlert("ファイルのアップロードに失敗しました。")</script>';
				}
			} else {
				echo '<script type="text/javascript">failAlert("ファイルのアップロードに失敗しました。")</script>';
			}
		}
	}
?>

	<form method="POST" class="form-group row" id="form" enctype="multipart/form-data">
		<div class="col-lg-6">
			<label for="video-file">Video File</label><br>
			<video src="" id="video-preview" style="max-width: 30vw; height: auto;" controls></video>
			<input type="file" name="video-file" accept="video/*" id="video-file" required onchange="selectVideo(event)"><br>
			<small style="color: red;" id="video_error" hidden></small>
		</div>

		<div class="col-lg-6">
			<label for="project-name">Project Name</label>
			<input type="text" class="form-control" name="project-name" placeholder="Project Name" id="project-name" required onchange="checkInputs();">
			<small style="color: red;" id="duplicate_error" hidden>Name already exists.</small>
			<br>

			<div class="row">
				<div class="col-3">
					<label for="annotation-fps">Annotation FPS</label>
					<input type="number" min=<?php echo $min_fps;?> max=<?php echo $max_fps;?> step=0.01 class="form-control" name="annotation-fps" id="fps" placeholder="FPS" value=1 required onchange="checkInputs();">
					<small style="color: red;" id="fps_error" hidden><?php echo $min_fps;?> to <?php echo $max_fps;?></small>
				</div>

				<div class="col-9">
					<label for="label-specification">Label Specification or Annotation File in Progress</label><br>
					<input type="file" name="label-specification" accept="text/*" id="label-specification" required onchange="selectLabel(event);">
					<small style="color: red;" id="label_error" hidden></small>
				</div>
			</div>
			<br>
		</div>

		<div class="container">
		<div class="row">
			<div class="col text-center">
				<button type="submit" class="btn btn-lg btn-primary" id="submit">Submit</button>
				<a type="button" class="btn btn-lg btn-secondary" href="../../">Back</a>
			</div>
		</div></div>
	</form>		
</div>

<script type="text/javascript">
	var existing_names = <?php echo json_encode($enames); ?>;

	function selectVideo(e) {
		var reader = new FileReader();
		reader.onload = function (e) {
			$('#video-preview').attr('src', e.target.result);
			checkInputs();
		}
		reader.readAsDataURL(e.target.files[0]);
	}

	function selectLabel(e) {
		checkInputs();
	}

	function checkInputs() {
		var isOK = true;

		// Check project name
		for (const ename of existing_names) {
			if (ename === $('#project-name').val().trim()) {
				$('#duplicate_error').attr('hidden', false);
				isOK = false;
			}
		}

		// Check fps
		var fps = $('#fps').val();
		if (fps < <?php echo $min_fps;?> || <?php echo $max_fps;?> < fps) {
			$('#fps_error').attr('hidden', false);
			isOK = false;
		}

		// Check video file

		// Check label specification

		if (isOK) {
			$('#submit').attr('disabled', false);
			$('#duplicate_error').attr('hidden', true);
			$('#fps_error').attr('hidden', true);
			$('#label_error').attr('hidden', true);
			$('#video_error').attr('hidden', true);
		} else {
			$('#submit').attr('disabled', true);			
		}
	}
</script>

</body>
</html>