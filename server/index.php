<?php
	session_start();

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
		<div class="form-group">
			<input type="text" class="form-control" id="filter" placeholder="Filter" onchange="updateItems();">
		</div>
	<hr>

	<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4" id="project-cardlist">
	</div>

	<template id="card-template">
		<div class="col mb-4">
			<div class="card h-100">
				<img src="" class="card-img-top project-thumbnail">
				<div class="card-body">
					<h5 class="card-title project-name">Project Name</h5>
					<div class="row">
						<a href="" type="button" class="btn btn-block btn-primary go-annotation" style="width:100%;">Go Annotation</a>
						<button class="btn btn-block btn-secondary" style="width:50%;" onclick="downloadAnnotation(event);">Download</button>
						<button class="btn btn-block btn-dark" style="width:50%;" onclick="deleteProject(event);">Delete</button>
					</div>
					<p class="card-text video-filename">
						<span class="video-filename">Video filename</span>: 
						<span class="annotation-progress">114/514</span></p>
					<p class="card-text"><small class="text-muted project-last-update">2020/10/23</small></p>
				</div>
			</div>
		</div>
	</template>

</div>

<script type="text/javascript">
	function downloadAnnotation(event) {
		alert('download');
	}

	function deleteProject(event) {
		alert('delete')
	}

	function updateItems() {
		var data = <?php echo json_encode($project_array); ?>;
		var template = document.getElementById('card-template');

		$('#project-cardlist').children().remove();

		for (var i = 0; i < data.length; i++) {
			var clone = template.content.cloneNode(true);

			clone.querySelector('.project-thumbnail').src = 'img/project/' + data[i].img;
			clone.querySelector('.project-name').textContent = data[i].name;
			clone.querySelector('.project-comment').textContent = data[i].comment;
			clone.querySelector('.go-manage').href = "./manage/?id=" + data[i].id;
			clone.querySelector('.project-last-update').textContent = "Last update: " + data[i].last_update;
			
			$('#project-cardlist').append(clone);
		}
	}

	$(document).ready(function() {
		updateItems();
	});
</script>
</body>
</html>