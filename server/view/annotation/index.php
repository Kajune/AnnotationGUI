<?php
	session_start();

	if (!isset($_GET['name'])) {
		header('Location:../../');
		exit;
	}

	$project_dir = '/var/www/html/projects/';
	$project_names = scandir($project_dir);
	$project_name = $_GET['name'];

	$isOK = false;
	foreach ($project_names as $name) {
		if (strcmp($project_name, $name) == 0) {
			$isOK = true;
			break;
		}
	}

	if (!$isOK) {
		header('Location:../../');
		exit;		
	}
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

	<script type="text/javascript">
		var project_name = '<?php echo $project_name; ?>';
		var project_url = '../../projects/' + project_name + '/';
	</script>

	<style type="text/css">
		html, body{
			height:100%;
			width:100%;
			font-size : 100%;
			margin-left : auto;
			margin-right : auto;
			text-align : center;
		}

		.slider {
			-webkit-appearance: none;
			appearance: none;
			background: #d3d3d3;
			outline: none;
			opacity: 0.7;
			-webkit-transition: .2s;
			transition: opacity .2s;
			vertical-align: middle;
		}

		.slider:hover {
			opacity: 1;
		}

		.slider::-webkit-slider-thumb {
		  -webkit-appearance: none;
		  appearance: none;
		  width: 10px;
		  height: 15px;
		  background: gray;
		  cursor: pointer;
		}

		.slider::-moz-range-thumb {
		  width: 10px;
		  height: 15px;
		  background: gray;
		  cursor: pointer;
		}
	</style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark" style="height: 5%; padding-top: 0; padding-bottom: 0;">
	<a href="#" class="navbar-brand">
		Super Sophisticated Annotation GUI
	</a>

	<span class="navbar-text ml-auto" style="margin-right: 1%;">
		<small>Annotation results are automatically saved.</small>
	</span>
	<a type="button" class="btn btn-secondary btn-sm" href="../../">Back to Menu</a>
</nav>

<div class="container-fluid main" style="width: 100%; height: 95%;">
	<div class="row" style="height: 100%; padding: 1%; padding-top: 0;">
		<!-- Left Pane -->
		<div style="width:20%; padding-right: 1%; height: 100%;">
			<div class="insideWrapper" style="height: 25%;">
				<img src="" class="overedImage" style="max-width: 100%; max-height: 100%;" id="thumb-image">
				<canvas class="coveringCanvas" id="canvas-thumb"></canvas>
			</div>
			<div style="padding-top: 5%;">
				<button class="btn btn-secondary btn-block">Delete track at current frame</button>
				<button class="btn btn-secondary btn-block">Delete tracks in subsequent frames</button>
				<button class="btn btn-secondary btn-block">Delete whole tracklet</button>
				<button class="btn btn-secondary btn-block">Link tracklets</button>
				<button class="btn btn-secondary btn-block">Cut tracklet at current frame</button>
			</div>
		</div>

		<!-- Right Pane -->
		<div style="width:80%; height: 100%;">
			<!-- Image Region -->
			<canvas style="width: 100%; height: 95%; background-color: #000000" id="canvas-main"></canvas>
	
			<!-- Seek bar -->
			<form class="range-field form-inline" style="width: 100%; height: 5%;">
				<div class="form-group" style="width: 10%;">
					<input type="number" min="1" max="100" value="1" class="form-control" id="current-frame-index" style="width: 50%;" oninput="updateFrameIndex(event.target.value);">
					<label style="width: 50%;" for="current-frame-index" id="max-frame-index">/100</label>
				</div>
				<input type="range" min="1" max="100" value="1" class="slider" id="seekbar"style="width: 90%;" oninput="updateFrameIndex(event.target.value)"/>
			</form>
		</div>
	</div>
</div>

<!-- Dialogue -->
<div class="modal fade" id="delete-damage-dialog" tabindex="-1" role="dialog" aria-labelledby="label_delete_damage" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="label_delete_damage">この損傷を削除</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				本当にこの損傷を削除しますか？
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">いいえ</button>
				<button type="button" class="btn btn-warning" onclick="deleteDamage()" data-dismiss="modal">はい</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var annotation = null;
	var frame_index = 1;

	const anti_alias = 4;
	const canvas_main = $('#canvas-main')[0];
	const canvas_thumb = $('#canvas-thumb')[0];
	canvas_main.width = window.innerWidth * anti_alias;
	canvas_main.height = window.innerHeight * anti_alias;

	$(window).resize(function () { 
		canvas_main.width = window.innerWidth * anti_alias;
		canvas_main.height = window.innerHeight * anti_alias;
		updateScreen(); 
	});

	const ctx_main = canvas_main.getContext('2d');
	const ctx_thumb = canvas_thumb.getContext('2d');

	const current_image = new Image();
	current_image.onload = () => {
		var real_scale = Math.min(canvas_main.width / current_image.width, canvas_main.height / current_image.height);

		ctx_main.clearRect(0, 0, canvas_main.width, canvas_main.height);
		ctx_main.scale(real_scale, real_scale);
		ctx_main.translate((canvas_main.width / real_scale - current_image.width) / 2, (canvas_main.height / real_scale - current_image.height) / 2);
		ctx_main.drawImage(current_image, 0, 0);
		ctx_main.resetTransform();
	};

	function loadAnnotation() {
		$.ajaxSetup({ async: false });
		$.getJSON(project_url + 'annotation.json', (data) => {
			annotation = data;
		});
		$.ajaxSetup({ async: true });
	}

	function saveAnnotation() {
		var data = { 'name': project_name, 
			'annotation': JSON.stringify(annotation, null, '  '),
		};

		$.ajax({
			type: "POST",
			url: './uploadAnnotation.php',
			dataType: 'json',
			data: data,
		});
	}

	function updateFrameIndex(new_index) {
		frame_index = new_index;

		$('#current-frame-index').val(frame_index);
		$('#seekbar').val(frame_index);

		updateScreen();
	}

	function updateScreen() {
		current_image.src = project_url + 'images/' + annotation.images[frame_index - 1].file_name;

		$('#thumb-image').attr('src', current_image.src);
	}

	$(document).ready(function() {
		loadAnnotation();

		console.log(annotation);

		$('#max-frame-index').text('/' + annotation.images.length);
		$('#seekbar').attr('max', annotation.images.length);

		updateFrameIndex(annotation.annotations.length + 1);
	});
</script>
</body>
</html>