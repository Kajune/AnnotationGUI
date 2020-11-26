<?php
	session_start();
	$project_dir = '/var/www/html/projects/';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['delete-project']) and $_POST['delete-project'] != '') {
			exec('python3 api/delete_project.py '.$project_dir.' '.$_POST['delete-project']);
			exit();
		}
	}

	$project_dir = '/var/www/html/projects/';
	$project_names = preg_grep('/^([^.])/', scandir($project_dir));

	$project_data = array();
	$project_update = array();
	foreach ($project_names as $name) {
		$json = file_get_contents($project_dir.$name.'/annotation.json');
		if (!$json) {
			continue;
		}
		$project_data[$name] = json_decode($json);
		$project_update[$name] = date("Y/m/d H:i:s.", filemtime($project_dir.$name.'/annotation.json'));
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="content-type" charset="utf-8">

	<title>Super Sophisticated Annotation GUI</title>

	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script type="text/javascript" src="js/jquery-3.5.1.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.bundle.min.js"></script>
	<link rel="stylesheet" type="text/css" href="css/style.css">

	<style type="text/css">
		.card-img-top {
			width: 100%;
			height: 15vw;
			object-fit: scale-down;
		}
	</style>
</head>

<body>
<div class="container header">
	<h1>Super Sophisticated Annotation GUI</h1>
	<br>
	<a type="button" class="btn-lg btn-primary" href="view/createProject">Create New Project</a>
	<br><br>

	<hr>
		<form class="row">
			<div class="form-group col-9">
				<input type="text" class="form-control" id="filter" placeholder="Filter" oninput="updateItems();">
			</div>

			<div class="form-group col-3 form-inline">
				<span style="margin-right: 2%;">Sort by</span>
				<select class="form-control" onchange="resort(event);">
					<option selected>Project Name</option>
					<option>Video Name</option>
					<option>Last Update</option>
				</select>
			</div>
		</form>
	<hr>

	<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4" id="project-cardlist">
	</div>

	<template id="card-template">
		<div class="col mb-4">
			<div class="card h-100">
				<!--
				<video src="" class="card-img-top project-thumbnail text-center" style="max-width: 30vw; height: auto; margin-left: auto; margin-right: auto;" controls></video>
				-->
				<img src="" class="card-img-top project-thumbnail text-center" style="max-width: 30vw; height: auto; margin-left: auto; margin-right: auto;" controls></img>
				<div class="card-body">
					<h5 class="card-title">
						<span class="project-name" onclick="startEditProjectName(event);">Project Name</span>
						<div class="input-group project-name-input" hidden>
							<input type="text" class="form-control" oninput="checkProjectName(event);">
							<div class="input-group-append">
								<button class="btn btn-primary" onclick="endEditProjectName(event);">OK</button>
							</div>
						</div>
						<small style="color: red;" class="duplicate_error" hidden>Name already exists.</small>
					</h5>
					<div class="row">
						<a href="" type="button" class="btn btn-block btn-primary go-annotation" style="width:100%;">Go Annotation</a>
						<a href="" type="button" class="btn btn-block btn-secondary download-annotation" style="width:60%;" download="">Download</a>
						<button class="btn btn-block btn-dark delete-project" style="width:40%;" data-toggle="modal" data-target="#delete-project-dialog" data-name="">Delete</button>
					</div>
					<p class="card-text">
						<span class="video-filename">Video filename</span><br>
						Progress: <span class="annotation-progress"></span>
					</p>
					<p class="card-text"><small class="text-muted project-last-update">2020/10/23</small></p>
				</div>
			</div>
		</div>
	</template>

</div>

<!-- Dialogue -->
<div class="modal fade" id="delete-project-dialog" tabindex="-1" role="dialog" aria-labelledby="label_delete_project" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="label_delete_project">Delete Project</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				Are you sure want to delete project: <b><span id='delete-project-name'></span></b> ?
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
				<button type="button" class="btn btn-warning" id="delete-confirm" onclick="" data-dismiss="modal">Yes</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var project_data = <?php echo json_encode($project_data); ?>;
	var project_update = <?php echo json_encode($project_update); ?>;
	var project_names = [];
	for (let pname in project_data) {
		project_names.push(pname);
	}

	$('#delete-project-dialog').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var name = button.data('name');
		var modal = $(this);
		modal.find('#delete-project-name').text(name);
		$('#delete-confirm').on('click', function (e) {
			$.post("index.php", {'delete-project': name});
			location.reload(true);
		});
	});

	function checkProjectNameValidity(name, target) {
		for (let pname in project_data) {
			if (pname === name) {
				return false;
			}
		}

		return true;
	}

	function startEditProjectName(e) {
		var editTarget = $(e.target).parent();
		editTarget.find('.project-name').attr('hidden', true);
		editTarget.find('.project-name-input').attr('hidden', false);
		editTarget.find('.project-name-input').find('input').val(editTarget.find('.project-name').text());
	}

	function checkProjectName(e) {
		var editTarget = $(e.target).parent().parent();
		var new_pname = editTarget.find('input').val();
		var isAvailableName = checkProjectNameValidity(new_pname) || new_pname === editTarget.find('.project-name').text();
		editTarget.find('.duplicate_error').attr('hidden', isAvailableName);
		editTarget.find('button').attr('disabled', !isAvailableName);
	}

	function endEditProjectName(e) {
		var editTarget = $(e.target).parent().parent().parent();
		editTarget.find('.project-name').attr('hidden', false);
		editTarget.find('.project-name-input').attr('hidden', true);
		editTarget.find('.duplicate_error').attr('hidden', true);

		var new_pname = editTarget.find('input').val();
		if (new_pname !== editTarget.find('.project-name').text() && checkProjectNameValidity(new_pname)) {	
			var data = { 
				'name': editTarget.find('.project-name').text(), 
				'new_name': new_pname,
			};

			$.ajax({
				type: "POST",
				url: './api/updateProjectName.php',
				dataType: 'json',
				data: data,
			}).done(function(data){
				location.reload(true);		
			});
		}
	}

	function strcmp(str1, str2) {
		return ((str1 == str2) ? 0 : ((str1 > str2) ? 1 : -1));
	}

	function resort(event) {
		let order_fn = null;
		if (event.target.value === 'Project Name') {
			order_fn = function (a, b) {
				return strcmp(a, b);
			}
		} else if (event.target.value === 'Video Name') {
			order_fn = function (a, b) {
				return strcmp(project_data[a].info.video, project_data[b].info.video);
			}
		} else if (event.target.value === 'Last Update') {
			order_fn = function (a, b) {
				return strcmp(project_update[b], project_update[a]);
			}
		}

		if (order_fn) {
			project_names.sort(order_fn);
			updateItems();
		}
	}

	function updateItems() {
		var template = $('#card-template');

		$('#project-cardlist').children().remove();

		for (let pname of project_names) {
			if ($('#filter').val() != '' && !pname.match($('#filter').val())) {
				continue;
			}
			var clone = template.clone().contents();

			// if annotation json is not initialized, this file will be created.
			var thumb_path = pname + '/images/000000.jpg';
			if (project_data[pname].images !== undefined) {
				thumb_path = project_data[pname].images[0].coco_url;
			}
			clone.find('.project-thumbnail').attr('src', 'projects/' + thumb_path);
			clone.find('.project-name').text(pname);
			clone.find('.project-name-input').val(pname);

			clone.find('.go-annotation').attr('href', "./view/annotation?name=" + pname);

			clone.find('.download-annotation').attr('href', 'projects/' + pname + '/annotation.json');
			clone.find('.download-annotation').attr('download', pname + '_annotation.json');

			clone.find('.delete-project').attr('data-name', pname);

			clone.find('.video-filename').text(project_data[pname].info.video);
			if (project_data[pname].annotations !== undefined && project_data[pname].images !== undefined) {
				var progress = 0;
				project_data[pname].annotations.forEach(function(annot) {
					if (annot.manual) {
						progress = Math.max(progress, annot.image_id+1);
					}
				});
				clone.find('.annotation-progress').text(progress + '/' + project_data[pname].images.length);
			} else {
				clone.find('.annotation-progress').text('Preparing');
			}

			clone.find('.project-last-update').text("Last update: " + project_update[pname]);
			
			$('#project-cardlist').append(clone);
		}
	}

	$(document).ready(function() {
		updateItems();
	});
</script>
</body>
</html>