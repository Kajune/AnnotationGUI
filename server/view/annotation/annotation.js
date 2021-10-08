var annotation = null;
var memento = [];

//
// Utility
//
function loadAnnotation() {
	$.ajaxSetup({ async: false });
	// attach timestamp to prevent read cache
	$.getJSON(project_url + 'annotation.json' + '?timestamp=' + Date.now(), (data) => {
		annotation = data;
	});
	$.ajaxSetup({ async: true });

	if (annotation === null || annotation.annotations === undefined || annotation.images === undefined) {
		return false;
	}

	// get next id and color
	if (annotation.annotations.length > 0) {
		annotation.annotations.forEach(annot => {
			next_box_id = Math.max(next_box_id, annot.id+1);
			next_tracklet_id = Math.max(next_tracklet_id, annot.tracklet_id+1);
			tracklet_colors[annot.tracklet_id] = randColor();
		});
	}

	annotation.categories.forEach(cat => {
		next_category_id = Math.max(next_category_id, cat.id+1);
	});

	memento.push(JSON.parse(JSON.stringify(annotation.annotations)));

	return true;
}

function saveAnnotation() {
	var savedata = { 'name': project_name, 
		'annotation': JSON.stringify(annotation, null, '  '),
	};

	$.ajax({
		type: "POST",
		url: './uploadAnnotation.php',
		dataType: 'json',
		data: savedata,
	}).fail(function (data){
		alert('Server connection failed.')
	});
}

function updateAnnotation() {
	memento.push(JSON.stringify(annotation.annotations));
	saveAnnotation();
}

function undoAnnotation() {
	if (memento.length <= 1){ 
		return;
	}
	memento.pop();
	annotation.annotations = JSON.parse(memento.slice(-1)[0]);
	updateDrawCanvas();
	saveAnnotation();
}

