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
		let template = document.getElementById('alert-success');
		let clone = template.content.cloneNode(true);
		document.getElementById('container').appendChild(clone);
	}

	function failAlert(msg) {
		let template = document.getElementById('alert-fail');
		let clone = template.content.cloneNode(true);
		clone.querySelector('.fail-msg').innerHTML = msg;
		document.getElementById('container').appendChild(clone);
	}
</script>

<?php
	$project_dir = '/var/www/html/projects/';
	$enames = scandir($project_dir);

	$min_fps = 0.01;
	$max_fps = 100;

	if($_SERVER['REQUEST_METHOD'] === 'POST'){
		$project_name = htmlspecialchars($_POST['project-name']);
		$project_type = htmlspecialchars($_POST['project-type']);

		$bad_flag = false;

		foreach ($enames as $ename) {
			if (strcmp($project_name, $ename) == 0) {
				echo '<script type="text/javascript">failAlert("Specified project name already exists.")</script>';
				$bad_flag = true;
				break;
			}
		}

		if (!$bad_flag) {
			if ($project_type == 'video'){
				if (isset($_FILES)
					&& isset($_FILES['video-file']) && is_uploaded_file($_FILES['video-file']['tmp_name'])
					&& isset($_FILES['label-specification']) && is_uploaded_file($_FILES['label-specification']['tmp_name'])) {
					$annotation_fps = $_POST['annotation-fps'];

					exec('python3 ../../api/create_project_video.py "'.$project_dir.'" "'.$project_name.'" '.$annotation_fps.' "'.
						$_FILES['video-file']['name'].'" "'.$_FILES['video-file']['tmp_name'].'" "'.
						$_FILES['label-specification']['name'].'" "'.$_FILES['label-specification']['tmp_name'].'"', $output, $return_let);

					// Sequential image generation is slow, so only image generation process is done in background.
					// Note that, above process cannot be done in background because tmp file will be gone soon after executing this php block.
					exec('python3 ../../api/create_images.py "'.$project_dir.'" "'.$project_name.'" '.$annotation_fps.' "'.
						$_FILES['video-file']['name'].'" > /dev/null &', $output, $return_let);

					echo '<script type="text/javascript">successAlert()</script>';
				} else {
					echo '<script type="text/javascript">failAlert("File upload failed.")</script>';
				}
			} else if ($project_type == 'image') {
				if (isset($_FILES)
					&& isset($_FILES['image-file'])
					&& isset($_FILES['label-specification']) && is_uploaded_file($_FILES['label-specification']['tmp_name'])) {

					for ($i=0; $i < count($_FILES['image-file']['tmp_name']); $i++) { 
						if (!is_uploaded_file($_FILES['image-file']['tmp_name'][$i])) {
							echo '<script type="text/javascript">failAlert("File upload partially failed: '.$_FILES['image-file']['name'][$i].'")</script>';
						}
					}

					exec('python3 ../../api/create_project_image.py "'.$project_dir.'" "'.$project_name.'" "'.
						base64_encode(json_encode($_FILES['image-file'])).'" "'.
						$_FILES['label-specification']['name'].'" "'.$_FILES['label-specification']['tmp_name'].'"', $output, $return_let);

					echo '<script type="text/javascript">successAlert()</script>';
				} else {
					echo '<script type="text/javascript">failAlert("File upload failed.")</script>';
				}
			} else {
				echo '<script type="text/javascript">failAlert("Unknown project type '.$project_type.' specified.")</script>';
			}
		}
	}
?>

	<form method="POST" class="form-group row" id="form" enctype="multipart/form-data">
		<div class="col-lg-6">
			<div>
				<label for="project-type">Project Type</label>
				<select id="project-type" name="project-type" class="custom-select" onchange="onProjectTypeChange()">
					<option value="video" selected>Video</option>
					<option value="image">Image</option>
				</select>
			</div>
			<br>

			<div class="project-type-dependent type-video row">
				<div class="col-9">
					<label for="video-file">Video File</label><br>
					<video src="" id="video-preview" style="max-width: 100%; height: auto;" controls></video>
					<input type="file" name="video-file" accept="video/*" id="video-file" required onchange="selectVideo(event)" class="project-type-dependent-input type-video-input"><br>
					<small style="color: red;" id="video_error" hidden></small>
				</div>

				<div class="col-3">
					<label for="annotation-fps">Annotation FPS</label>
					<input type="number" min=<?php echo $min_fps;?> max=<?php echo $max_fps;?> step=0.01 class="form-control" name="annotation-fps" id="fps" placeholder="FPS" value=1 required onchange="checkInputs();" class="project-type-dependent-input type-video-input">
					<small style="color: red;" id="fps_error" hidden><?php echo $min_fps;?> to <?php echo $max_fps;?></small>
				</div>
			</div>

			<div class="project-type-dependent type-image row">
				<div class="col-6">
					<label for="image-file">Image Files</label><br>
					<img src="" id="image-preview" style="max-width: 100%; height: auto; background-color: black;">
					<input type="file" name="image-file[]" accept="image/*" id="image-file" required onchange="selectImage(event)" multiple class="project-type-dependent-input type-image-input">
					<br>
				</div>
				<div class="col-6">
					<label for="image-list">Images</label><br>
					<select class="custom-select" name="image-list" id="image-list" size="5" onchange="onChangePreviewImage()">
					</select>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<label for="project-name">Project Name</label>
			<input type="text" class="form-control" name="project-name" pattern="^[0-9A-Za-z_\-]+$" placeholder="A-Z a-z 0-9 _ -" id="project-name" required onchange="checkInputs();">
			<small style="color: red;" id="duplicate_error" hidden>Name already exists.</small>
			<br>

			<div class="row">
				<div class="col-12">
					<label for="label-specification">Label Specification or Annotation File in Progress</label><br>
					<input type="file" name="label-specification" accept="application/json" id="label-specification" required onchange="selectLabel(event);"><br>
					<small style="color: red;" id="label_error" hidden></small>
					<a href="label_specification_sample.json" download>Sample</a>
				</div>
			</div>
			<br>

			<div class="row">
				<div class="col-6">
					<label for="class-list">Class</label><br>
					<textarea class="form-control" name="class-list" id="class-list" readonly rows="5"></textarea>		
				</div>
				<div class="col-6">
					<label for="attribution-list">Attribution</label><br>
					<textarea class="form-control" name="attribution-list" id="attribution-list" readonly rows="5"></textarea>
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
	sanitaize = {
		encode : function (str) {
			return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
		},

		decode : function (str) {
			return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, '\'').replace(/&amp;/g, '&');
		}
	};

	let existing_names = <?php echo json_encode($enames); ?>;

	function selectVideo(e) {
		/*
		let reader = new FileReader();
		let filename = sanitaize.encode(e.target.files[0].name.split('.').slice(0, -1).join('.'));
		reader.onload = function (e) {
			$('#video-preview').attr('src', e.target.result);
			if (!$('#project-name').val()) {
				$('#project-name').val(filename);
			}
			checkInputs();
		}
		reader.readAsDataURL(e.target.files[0]);*/

		let filename = sanitaize.encode(e.target.files[0].name.split('.').slice(0, -1).join('.'));
		$('#video-preview').attr('src', URL.createObjectURL(e.target.files[0]));
		if (!$('#project-name').val()) {
			$('#project-name').val(filename);
		}
		checkInputs();
	}

	function selectImage(e) {
		$('#image-list').empty();
		for (let i = 0; i < e.target.files.length; i++) {
			let filename = sanitaize.encode(e.target.files[i].name);
			$('#image-list').append($('<option>').val(URL.createObjectURL(e.target.files[i])).text(filename).attr('selected', i==0));
		}
		onChangePreviewImage();
		checkInputs();
	}

	let jsonData = null;
	function selectLabel(e) {
		let reader = new FileReader();
		reader.onload = function (e) {
			jsonData = e.target.result;
			checkInputs();
		}
		reader.readAsText(e.target.files[0]);
	}

	function checkInputs(isOK_=true) {
		let isOK = isOK_;

		// Check project name
		for (const ename of existing_names) {
			if (ename === $('#project-name').val().trim()) {
				$('#duplicate_error').attr('hidden', false);
				isOK = false;
			}
		}

		// Check fps
		let fps = $('#fps').val();
		if (fps < <?php echo $min_fps;?> || <?php echo $max_fps;?> < fps) {
			$('#fps_error').attr('hidden', false);
			isOK = false;
		}

		// Check video file

		// Check label specification
		if (jsonData) {
			try {
				let label = JSON.parse(jsonData);

				if (label.categories === undefined || label.attributes === undefined) {
					$('#label_error').text('categories or attributes entity not set in JSON.');
					$('#label_error').attr('hidden', false);
					$('#label-specification').val('');
					isOK = false;
				} else {
					$('#class-list').text(label.categories.map(x => x.name).join('\n'));
					$('#attribution-list').text(label.attributes.map(x => x.name).join('\n'));
				}
			} catch (error) {
				$('#label_error').text(error.message);
				$('#label_error').attr('hidden', false);
				$('#label-specification').val('');
				isOK = false;
			}
		}

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

	function onProjectTypeChange() {
		const type = $('#project-type').val();
		$('.project-type-dependent').attr('hidden', true);
		$('.type-' + type).attr('hidden', false);

		$('.project-type-dependent-input').attr('required', false);
		$('.type-' + type + '-input').attr('required', true);
	}

	function onChangePreviewImage() {
		const img = $('#image-list').val();
		$('#image-preview').attr('src', img);
	}

	$(function(){
		onProjectTypeChange();
	});
</script>

</body>
</html>