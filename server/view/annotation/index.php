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
<body oncontextmenu="return false;">

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
			<figure class="figure d-none d-sm-block">
				<div class="insideWrapper">
					<img src="" class="overedImage" id="thumb-image" style="max-width: 100%; height: auto;">
					<canvas class="coveringCanvas" id="canvas-thumb"></canvas>
				</div>
			</figure>
			<div style="padding-top: 5%;">
				<button class="btn btn-secondary btn-block">Delete track at current frame</button>
				<button class="btn btn-secondary btn-block">Delete tracks in subsequent frames</button>
				<button class="btn btn-secondary btn-block">Delete whole tracklet</button>
				<button class="btn btn-secondary btn-block">Link tracklets</button>
				<button class="btn btn-secondary btn-block">Cut tracklet at current frame</button>
			</div>

			<div id="test"></div>
		</div>

		<!-- Right Pane -->
		<div style="width:80%; height: 100%;">
			<!-- Image Region -->
			<div class="insideWrapper" style="width: 100%; height: 95%;">
				<canvas style="width: 100%; height: 100%; position:absolute; top:0px; left:0px; background-color: #000000;" id="canvas-main"></canvas>
				<canvas style="width: 100%; height: 100%; position:absolute; top:0px; left:0px" id="canvas-draw"></canvas>
			</div>
	
			<!-- Seek bar -->
			<form class="range-field form-inline" style="width: 100%; height: 5%;">
				<div class="form-group" style="width: 15%;">
					<input type="number" min="1" max="100" value="1" class="form-control" id="current-frame-index" style="width: 50%;" oninput="updateFrameIndex(event.target.value);">
					<label style="width: 50%;" for="current-frame-index" id="max-frame-index">/100</label>
				</div>
				<input type="range" min="1" max="100" value="1" class="slider" id="seekbar"style="width: 85%;" oninput="updateFrameIndex(event.target.value)"/>
			</form>
		</div>
	</div>
</div>

<!-- Dialogue -->
<div class="modal fade" id="label-dialog" tabindex="-1" role="dialog" aria-labelledby="label-dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="label-dialog">Category and Attribution</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="selecting_category=false;">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body row">
				<div class="col-6">
					<h6>Category</h6>
					<select class="custom-select" size="10" id="category-selection">
					</select>
					<hr>
					<button class="btn btn-sm btn-primary">Add New</button>
				</div>
				<div class="col-6">
					<h6>Attribution</h6>
					<div id="attribution-selection">
						<template id="attribution-template">
							<div class="custom-control custom-switch">
								<input type="checkbox" class="custom-control-input attr-checkbox" id="">
								<label class="custom-control-label attr-label" for=""></label>
							</div>
						</template>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="selecting_category=false;">Cancel</button>
				<button type="button" class="btn btn-primary" onclick="if(selecting_category){addTracklet()}else{assignLabel()}" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var annotation = null;
	var frame_index = 1;

	const anti_alias = 1;
	const canvas_main = $('#canvas-main')[0];
	const canvas_draw = $('#canvas-draw')[0];
	const canvas_thumb = $('#canvas-thumb')[0];
	const ctx_main = canvas_main.getContext('2d');
	const ctx_draw = canvas_draw.getContext('2d');
	const ctx_thumb = canvas_thumb.getContext('2d');

	canvas_main.width = window.innerWidth * anti_alias;
	canvas_main.height = window.innerHeight * anti_alias;
	canvas_draw.width = window.innerWidth * anti_alias;
	canvas_draw.height = window.innerHeight * anti_alias;

	canvas_draw.addEventListener('mousewheel', onMouseWheel, false);
	canvas_draw.addEventListener('wheel', onMouseWheel, false);
	canvas_draw.addEventListener('mousedown', onMouseDown, false);
	document.addEventListener('mouseup', onMouseUp, false);
	document.addEventListener('mousemove', onMouseMove, false);

	$(window).resize(function () { 
		canvas_main.width = window.innerWidth * anti_alias;
		canvas_main.height = window.innerHeight * anti_alias;
		canvas_draw.width = window.innerWidth * anti_alias;
		canvas_draw.height = window.innerHeight * anti_alias;

		updateScreen(); 
	});

	var img_x = 0;
	var img_y = 0;
	var img_scale = 1;

	var moving_image = false;
	var making_box = false;
	var selecting_category = false;
	var moving_box = false;
	var resizing_box = false;

	var mx = 0, my = 0;
	var sx = 0, sy = 0;
	var x1, y1, x2, y2;

	var next_box_id = 0;
	var next_tracklet_id = 0;
	var tracklet_colors = {};
	var selected_tracklet = null;

	const current_image = new Image();
	current_image.onload = () => { updateImageCanvas(); updateDrawCanvas(); };

	//
	// Utility
	//

	// random color generation function
	function randColor() {
		return "rgb(" + (~~(256 * Math.random())) + ", " + (~~(256 * Math.random())) + ", " + (~~(256 * Math.random())) + ")";
	}

	function loadAnnotation() {
		$.ajaxSetup({ async: false });
		$.getJSON(project_url + 'annotation.json', (data) => {
			annotation = data;
		});
		$.ajaxSetup({ async: true });

		// get next id and color
		if (annotation.annotations.length > 0) {
			annotation.annotations.forEach(annot => {
				next_box_id = Math.max(next_box_id, annot['id']+1);
				next_tracklet_id = Math.max(next_tracklet_id, annot['tracklet_id']+1);
				tracklet_colors[annot['tracklet_id']] = randColor();
			});
		}
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

	function addTracklet() {
		for (var i = frame_index - 1; i < annotation.images.length; i++) {
			newTracklet = {
				image_id: i,
				bbox: [x1, y1, x2-x1, y2-y1],
				category_id: 3,
				tracklet_id: next_tracklet_id,
				id: next_box_id,
				attribution: [],
				manual: i == frame_index - 1,
			};
			next_box_id++;
			annotation.annotations.push(newTracklet);
		}

		tracklet_colors[next_tracklet_id] = randColor();
		selected_tracklet = next_tracklet_id;
		next_tracklet_id++;
		
		assignLabel();
		selecting_category = false;
	}

	function assignLabel() {
		if (selected_tracklet === null) {
			return;
		}

		for (var i = 0; i < annotation.annotations.length; i++) {
			if (annotation.annotations[i].tracklet_id === selected_tracklet) {
				annotation.annotations[i].category_id = Number($('#category-selection').val());

				for (var j = 0; j < annotation.attributes.length; j++) {
					if ($('#attr-' + annotation.attributes[j].id).prop('checked')) {
						annotation.annotations[i].attribution.push(annotation.attributes[j].id);
					}
				}
			}
		}

		updateDrawCanvas();
	}

	//
	// Coordinate computation
	//
	function real_scale() {
		return Math.min(canvas_main.width / current_image.width, canvas_main.height / current_image.height) * img_scale;
	}

	function canvasToImage(x, y) {
		var real_scale_ = real_scale()
		var canvas_offsetX = (canvas_main.width / real_scale_ - current_image.width) / 2
		var canvas_offsetY = (canvas_main.height / real_scale_ - current_image.height) / 2

		var x_ = ((-img_x / 2 + x) * canvas_main.width) / real_scale_- canvas_offsetX;
		var y_ = ((img_y / 2 + y) * canvas_main.height) / real_scale_ - canvas_offsetY;

		return [x_, y_];
	}

	function imageToCanvas(x, y) {
		var real_scale_ = real_scale()
		var canvas_offsetX = (canvas_main.width / real_scale_ - current_image.width) / 2
		var canvas_offsetY = (canvas_main.height / real_scale_ - current_image.height) / 2

		var x_ = (x + canvas_offsetX) * real_scale_ / canvas_main.width + img_x / 2;
		var y_ = (y + canvas_offsetY) * real_scale_ / canvas_main.height - img_y / 2;

		return [x_, y_];
	}

	//
	// Drawing
	//
	function updateFrameIndex(new_index) {
		if (($("#label-dialog").data('bs.modal') || {})._isShown || making_box || selecting_category) {
			return;
		}
		frame_index = Math.max(Math.min(new_index, annotation.images.length), 1);

		$('#current-frame-index').val(frame_index);
		$('#seekbar').val(frame_index);

		updateScreen();
	}

	function updateScreen() {
		current_image.src = project_url + 'images/' + annotation.images[frame_index - 1].file_name;

		$('#thumb-image').attr('src', current_image.src);
	}

	function drawMainImage() {
		var real_scale_ = real_scale()
		ctx_main.clearRect(0, 0, canvas_main.width, canvas_main.height);
		ctx_main.scale(real_scale_, real_scale_);
		ctx_main.translate((canvas_main.width / real_scale_ - current_image.width) / 2, (canvas_main.height / real_scale_ - current_image.height) / 2);
		ctx_main.translate(img_x * 0.5 * canvas_main.width / real_scale_, -img_y * 0.5 * canvas_main.height / real_scale_);
		ctx_main.drawImage(current_image, 0, 0);
		ctx_main.resetTransform();
		ctx_main.setTransform(1, 0, 0, 1, 0, 0);
	}

	function drawSubImage() {
		var real_scale_ = real_scale()
		var left = Math.min(current_image.width, Math.max(0, current_image.width / 2 - ((img_x + 1) / 2) * canvas_main.width / real_scale_));
		var right = Math.min(current_image.width, Math.max(0, current_image.width / 2 + ((1 - img_x) / 2) * canvas_main.width / real_scale_));
		var top = Math.min(current_image.height, Math.max(0, current_image.height / 2 - ((1 - img_y) / 2) * canvas_main.height / real_scale_));
		var bottom = Math.min(current_image.height, Math.max(0, current_image.height / 2 + ((1 + img_y) / 2) * canvas_main.height / real_scale_));

		var thumb_scale_x = canvas_thumb.width / current_image.width;
		var thumb_scale_y = canvas_thumb.height / current_image.height;

		ctx_thumb.lineWidth = 2;
		ctx_thumb.strokeStyle = 'red';
		ctx_thumb.clearRect(0, 0, canvas_thumb.width, canvas_thumb.height);
		ctx_thumb.strokeRect(left * thumb_scale_x, top * thumb_scale_y, (right - left) * thumb_scale_x, (bottom - top) * thumb_scale_y);
		ctx_thumb.resetTransform();
	}

	function drawGrid() {
		ctx_draw.strokeStyle = "black";
		ctx_draw.lineWidth = 2;
		ctx_draw.beginPath();
		ctx_draw.setLineDash([15, 5]);
		ctx_draw.moveTo(0, my * canvas_draw.height);
		ctx_draw.lineTo(canvas_draw.width, my * canvas_draw.height);
		ctx_draw.stroke();

		ctx_draw.moveTo(mx * canvas_draw.width, 0);
		ctx_draw.lineTo(mx * canvas_draw.width, canvas_draw.height);
		ctx_draw.stroke();
		ctx_draw.closePath();
	}

	function drawMakingBox() {
		if (making_box) {
			ctx_draw.strokeStyle = "navy";
			ctx_draw.lineWidth = 3;
			ctx_draw.setLineDash([]);

			var [x1, y1] = canvasToImage(sx, sy);
			var [x2, y2] = canvasToImage(mx, my);

			x1 = Math.max(Math.min(x1, current_image.width), 0);
			x2 = Math.max(Math.min(x2, current_image.width), 0);
			y1 = Math.max(Math.min(y1, current_image.height), 0);
			y2 = Math.max(Math.min(y2, current_image.height), 0);

			[x1, y1] = imageToCanvas(x1, y1);
			[x2, y2] = imageToCanvas(x2, y2);

			ctx_draw.strokeRect(x1 * canvas_draw.width, y1 * canvas_draw.height, (x2 - x1) * canvas_draw.width, (y2 - y1) * canvas_draw.height);			
		}
	}

	function drawTracklets() {
		if (!annotation || annotation.annotations.length <= 0) {
			return;
		}

		ctx_draw.lineWidth = 2;
		ctx_draw.setLineDash([]);

		var text_width = canvas_draw.width * 0.08;
		var text_height = canvas_draw.height * 0.025;

		ctx_draw.font = 'bold ' + text_height + 'px sans-serif';
		ctx_draw.textBaseline = 'bottom';

		annotation.annotations.forEach(annot => {
			if (annot['image_id'] == frame_index - 1) {
				ctx_draw.strokeStyle = tracklet_colors[annot['tracklet_id']];

				var [x1, y1] = imageToCanvas(annot['bbox'][0], annot['bbox'][1]);
				var [x2, y2] = imageToCanvas(annot['bbox'][2] + annot['bbox'][0], annot['bbox'][3] + annot['bbox'][1]);

				// Omit drawing if out of canvas
				if ((x1 < 0 && x2 < 0) || (y1 < 0 && y2 < 0) || (1 < x1 && 1 < x2) || (1 < y1 && 1 < y2)) {
					return;
				}

				// Draw box
				ctx_draw.strokeRect(x1 * canvas_draw.width, y1 * canvas_draw.height, (x2 - x1) * canvas_draw.width, (y2 - y1) * canvas_draw.height);
				if (annot['tracklet_id'] == selected_tracklet) {
					ctx_draw.strokeRect(x1 * canvas_draw.width - 3, y1 * canvas_draw.height - 3, 
						(x2 - x1) * canvas_draw.width + 6, (y2 - y1) * canvas_draw.height + 6);
				}

				// Draw text area
				ctx_draw.strokeRect(x1 * canvas_draw.width, y1 * canvas_draw.height, text_width, -text_height);
				ctx_draw.fillStyle = 'white';
				ctx_draw.fillRect(x1 * canvas_draw.width, y1 * canvas_draw.height, text_width, -text_height);
				ctx_draw.fillStyle = 'black';
				for (var i = 0; i < annotation.categories.length; i++) {
					if (annotation.categories[i]['id'] === annot['category_id']) {
						ctx_draw.fillText(annotation.categories[i]['name'], x1 * canvas_draw.width + 3, y1 * canvas_draw.height, text_width - 6);
						break;
					}
				}
			}
		});
	}

	function updateImageCanvas() {
		img_x = Math.min(img_scale, img_x);
		img_y = Math.min(img_scale, img_y);
		img_x = Math.max(-img_scale, img_x);
		img_y = Math.max(-img_scale, img_y);

		drawMainImage();
		drawSubImage();
	}

	function updateDrawCanvas() {
		ctx_draw.clearRect(0, 0, canvas_draw.width, canvas_draw.height);
		drawTracklets();
		drawGrid();
		drawMakingBox();
	}

	//
	// Events
	//
	function onMouseDown(event) {
		if (event.button === 2) {
			moving_image = true;
			$('#canvas-main').css('cursor', 'grab');
			event.preventDefault();
		} else if (event.button === 0) {
			making_box = true;
			sx = mx;
			sy = my;
		}
	}

	function onMouseUp(event) {
		if (event.button === 2) {
			moving_image = false;
			$('#canvas-main').css('cursor', 'auto');
			event.preventDefault();
		} else if (event.button === 0) {
			if (making_box) {
				[x1, y1] = canvasToImage(sx, sy);
				[x2, y2] = canvasToImage(mx, my);

				x1 = Math.max(Math.min(x1, current_image.width), 0);
				x2 = Math.max(Math.min(x2, current_image.width), 0);
				y1 = Math.max(Math.min(y1, current_image.height), 0);
				y2 = Math.max(Math.min(y2, current_image.height), 0);

				if (Math.abs(x1 - x2) < 1 || Math.abs(y1 - y2) < 1) {
					return;
				}

				if (x1 > x2) {
					[x1, x2] = [x2, x1];
				}
				if (y1 > y2) {
					[y1, y2] = [y2, y1];
				}

				making_box = false;
				selecting_category = true;
				$('#label-dialog').modal();
			}
		}
	}

	function onMouseMove(event) {
		var rect = canvas_main.getBoundingClientRect();
		var canvas_width = rect.right - rect.left;
		var canvas_height = rect.bottom - rect.top;

		var mx_last = mx;
		var my_last = my;
		mx = (event.x - rect.left) / canvas_width;
		my = (event.y - rect.top) / canvas_height;

		if (moving_image) {
			img_x += (mx - mx_last) * 2;
			img_y -= (my - my_last) * 2;
			updateImageCanvas();
		}

		updateDrawCanvas();
		event.preventDefault();
	}

	function onMouseWheel(event) {
		var rect = canvas_main.getBoundingClientRect();
		var x = (event.x - rect.left) / (rect.right - rect.left);
		var y = (event.y - rect.bottom) / (rect.top - rect.bottom);
		x = x * 2 - 1
		y = y * 2 - 1

		// x, y ~ (-1, 1)

		var delta = (typeof event.wheelDeltaY !== 'undefined') ? event.wheelDeltaY : event.deltaY;

		var scale_change = 0.8;

		if (delta > 0) {
			scale_change = 1 / scale_change;
		}

		img_scale *= scale_change;
		if (img_scale < 1) {
			scale_change /= img_scale;
			img_scale = 1;
		} else if (img_scale > 10) {
			scale_change *= 10 / img_scale;
			img_scale = 10;
		}
		img_x -= x;
		img_y -= y;
		img_x *= scale_change;
		img_y *= scale_change;
		img_x += x;
		img_y += y;

		updateImageCanvas();
		updateDrawCanvas();

		event.preventDefault();
	}

	$(document).ready(function() {
		loadAnnotation();

		console.log(annotation);

		$('#max-frame-index').text('/' + annotation.images.length);
		$('#seekbar').attr('max', annotation.images.length);

		for (var i = 0; i < annotation.categories.length; i++) {
			$('#category-selection').append(
				$('<option>')
					.val(annotation.categories[i]['id'])
					.text(annotation.categories[i]['name'])
					.prop('selected', i==0));
		}

		var template = $('#attribution-template').contents();
		for (var i = 0; i < annotation.attributes.length; i++) {
			var clone = template.clone();
			clone.find('.attr-checkbox').attr('id', 'attr-' + annotation.attributes[i]['id']);
			clone.find('.attr-label').attr('for', 'attr-' + annotation.attributes[i]['id'])
				.text(annotation.attributes[i]['name']);
			$('#attribution-selection').append(clone);
		}

		updateFrameIndex(annotation.annotations.length + 1);

		document.addEventListener('keydown', (event) => {
		if (event.key == 'ArrowLeft' || event.key == 'a') {
			updateFrameIndex(frame_index - 1);
		} else if (event.key == 'ArrowRight' || event.key == 'd') {
			updateFrameIndex(frame_index + 1);			
		}
	});

	});
</script>
</body>
</html>