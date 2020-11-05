var annotation = null;
var memento = [];

var next_box_id = 0;
var next_tracklet_id = 0;
var next_category_id = 0;
var tracklet_colors = {};
var selected_box = null;
var hovered_box = null;
var hover_list = [];
var hovered_cp = null;
var linking_box = null;
var no_link_cands = [];

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
	memento.push(JSON.parse(JSON.stringify(annotation.annotations)));
	saveAnnotation();
}

function undoAnnotation() {
	if (memento.length <= 1){ 
		return;
	}
	memento.pop();
	annotation.annotations = memento.slice(-1)[0];
	updateDrawCanvas();
	saveAnnotation();
}

function findTracklet(id) {
	for (var i = 0; i < annotation.annotations.length; i++) {
		if (id === annotation.annotations[i].id) {
			return annotation.annotations[i];
		}
	}
	return null;
}

function addTracklet() {
	setSelectedBox(next_box_id);

	for (var i = frame_index - 1; i < annotation.images.length; i++) {
		newTracklet = {
			image_id: i,
			bbox: [x1, y1, x2-x1, y2-y1],
			category_id: 0,
			tracklet_id: next_tracklet_id,
			id: next_box_id,
			attribution: [],
			manual: i == frame_index - 1,
		};
		next_box_id++;
		annotation.annotations.push(newTracklet);
	}

	tracklet_colors[next_tracklet_id] = randColor();
	next_tracklet_id++;
	
	assignLabel();
	selecting_new_category = false;
	updateAnnotation();
}

function assignLabel() {
	if (selected_box === null) {
		return;
	}

	var sb = findTracklet(selected_box);
	if (sb === null) {
		return;
	}

	// As for category, whole tracklet categories are updated.
	// On the other hand, attribution is assigned image by image, except when making new tracklet.
	for (var i = 0; i < annotation.annotations.length; i++) {
		if (annotation.annotations[i].tracklet_id === sb.tracklet_id) {
			annotation.annotations[i].category_id = Number($('#category-selection').val());
		}

		if ((selecting_new_category && annotation.annotations[i].tracklet_id === sb.tracklet_id) ||
			(!selecting_new_category && annotation.annotations[i].id === selected_box)) {
			var annot = annotation.annotations[i];
			annot.attribution = [];
			for (var j = 0; j < annotation.attributes.length; j++) {
				if ($('#attr-' + annotation.attributes[j].id).prop('checked')) {
					annot.attribution.push(annotation.attributes[j].id);
				}
			}

			if (!selecting_new_category) {
				propagateTracklet(selected_box);
			}
		}
	}

	sb.manual = true;

	updateAnnotation();
	updateDrawCanvas();
}

function delete_at_current_frame() {
	if (selected_box === null) {
		return;
	}

	annotation.annotations = annotation.annotations.filter(annot => annot.id !== selected_box);
	setSelectedBox(null);
	updateAnnotation();
	updateDrawCanvas();
}

function delete_in_subsequent_frames() {
	if (selected_box === null) {
		return;
	}

	var sb = findTracklet(selected_box);

	annotation.annotations = annotation.annotations.filter(function(annot){
		return !(annot.tracklet_id === sb.tracklet_id && annot.image_id >= annotation.images[frame_index-1].id);
	});
	setSelectedBox(null);
	updateAnnotation();
	updateDrawCanvas();
}

function delete_whole() {
	if (selected_box === null) {
		return;
	}

	var sb = findTracklet(selected_box);

	annotation.annotations = annotation.annotations.filter(annot => annot.tracklet_id !== sb.tracklet_id);
	setSelectedBox(null);
	updateAnnotation();
	updateDrawCanvas();
}

function cut_tracklet() {
	if (selected_box === null) {
		return;
	}

	var sb = findTracklet(selected_box);
	var sb_tracklet_id = sb.tracklet_id;

	annotation.annotations.forEach(function(annot) {
		if (annot.tracklet_id === sb_tracklet_id && annot.image_id >= annotation.images[frame_index-1].id) {
			annot.tracklet_id = next_tracklet_id;
		}
	});

	tracklet_colors[next_tracklet_id] = randColor();
	next_tracklet_id++;

	setSelectedBox(null);
	updateAnnotation();
	updateDrawCanvas();
}

function update_no_link_cands() {
	var lb = findTracklet(linking_box);
	var images_with_sb = [];
	annotation.annotations.forEach(function(annot){
		if (annot.tracklet_id === lb.tracklet_id) {
			images_with_sb.push(annot.image_id);
		}
	});

	annotation.annotations.forEach(function(annot){
		if (images_with_sb.includes(annot.image_id) && annot.tracklet_id !== lb.tracklet_id) {
			no_link_cands.push(annot.tracklet_id);
		}
	});		
}

function begin_link_tracklet() {
	tracklet_linking = true;
	linking_box = selected_box;
	$('#end-link-tracklet').attr('hidden', false);
	update_no_link_cands();
}

function end_link_tracklet() {
	tracklet_linking = false;
	linking_box = null;
	$('#end-link-tracklet').attr('hidden', true);
	no_link_cands = [];
}

function linkBox(hovered_box) {
	if (linking_box === null) {
		return;
	}
	var sb = findTracklet(linking_box);
	var hb = findTracklet(hovered_box);
	var hb_tracklet_id = hb.tracklet_id;

	annotation.annotations.forEach(function(annot){
		if (annot.tracklet_id === hb_tracklet_id && !no_link_cands.includes(annot.tracklet_id)) {
			annot.tracklet_id = sb.tracklet_id;
		}
	});

	updateAnnotation();
	updateDrawCanvas();
	update_no_link_cands();
}

function moveBox(mx, my, mx_last, my_last) {
	for (var i = 0; i < annotation.annotations.length; i++) {
		var annot = annotation.annotations[i];
		if (annot.id === selected_box) {
			var [vx1, vy1] = canvasToImage(mx_last, my_last);
			var [vx2, vy2] = canvasToImage(mx, my);
			annot.bbox[0] += vx2 - vx1;
			annot.bbox[1] += vy2 - vy1;
			annot.bbox[0] = Math.max(Math.min(annot.bbox[0], current_image.width - annot.bbox[2]), 0);
			annot.bbox[1] = Math.max(Math.min(annot.bbox[1], current_image.height - annot.bbox[3]), 0);
			annot.manual = true;
		}
	}
	updateDrawCanvas();
}

function resizeBox(mx, my, mx_last, my_last) {
	for (var i = 0; i < annotation.annotations.length; i++) {
		var annot = annotation.annotations[i];
		if (annot.id === selected_box) {
			var [vx1, vy1] = canvasToImage(mx_last, my_last);
			var [vx2, vy2] = canvasToImage(mx, my);

			var [bx1, by1, bx2, by2] = [annot.bbox[0], annot.bbox[1], annot.bbox[0] + annot.bbox[2], annot.bbox[1] + annot.bbox[3]];
			if (hovered_cp == 0) {
				bx1 += vx2 - vx1;
				by1 += vy2 - vy1;
			} else if (hovered_cp == 1) {
				bx2 += vx2 - vx1;
				by1 += vy2 - vy1;					
			} else if (hovered_cp == 2) {
				bx1 += vx2 - vx1;
				by2 += vy2 - vy1;					
			} else if (hovered_cp == 3) {
				bx2 += vx2 - vx1;
				by2 += vy2 - vy1;
			} else if (hovered_cp == 4) {
				by1 += vy2 - vy1;					
			} else if (hovered_cp == 5) {
				bx1 += vx2 - vx1;					
			} else if (hovered_cp == 6) {
				by2 += vy2 - vy1;					
			} else if (hovered_cp == 7) {
				bx2 += vx2 - vx1;					
			}

			bx1 = Math.max(Math.min(bx1, current_image.width), 0);
			by1 = Math.max(Math.min(by1, current_image.height), 0);
			bx2 = Math.max(Math.min(bx2, current_image.width), 0);
			by2 = Math.max(Math.min(by2, current_image.height), 0);

			annot.bbox[0] = Math.min(bx1, bx2);
			annot.bbox[1] = Math.min(by1, by2);
			annot.bbox[2] = Math.abs(bx2 - bx1);
			annot.bbox[3] = Math.abs(by2 - by1);

			annot.manual = true;
		}
	}
	updateDrawCanvas();
}

// Inherit box coordinate and category in current frame to following frames until manual=True
function propagateTracklet(selected_box) {
	var sb = findTracklet(selected_box);
	if (!sb) {
		return;
	}
	for (var id = frame_index; id < annotation.images.length; id++) {
		var next_annot = null;
		for (var i = 0; i < annotation.annotations.length; i++) {
			if (annotation.annotations[i].image_id === annotation.images[id].id && annotation.annotations[i].tracklet_id === sb.tracklet_id) {
				next_annot = annotation.annotations[i];
				break;
			}
		}

		if (!next_annot) {
			break;
		}

		if (next_annot.manual) {
			break;
		}

		next_annot.bbox = JSON.parse(JSON.stringify(sb.bbox));
		next_annot.category_id = sb.category_id;
		next_annot.attribution = sb.attribution;
	}
}

function auto_predict() {
	$('#predict-next-frame').attr('disabled', $('#auto-predict').prop('checked'));
}

function predict_next_frame(current_frame_index) {
	if (annotation.images.length === current_frame_index) {
		return;
	}
	$('#predict-next-frame').attr('disabled', true);
	$('#predict-next-frame').text('Processing...');

	var next_bbox = [];
	annotation.annotations.forEach(function(annot_next){
		if (annot_next.image_id === current_frame_index && !annot_next.manual) {
			next_bbox.push(annot_next);
		}
	});

	var bbox = [];
	annotation.annotations.forEach(function(annot){
		if (annot.image_id === current_frame_index - 1) {
			// Check if the box still exists in the next frame without manually annotated
			next_bbox.forEach(function(annot_next){
				if (annot_next.tracklet_id === annot.tracklet_id) {
					bbox.push([annot.tracklet_id, annot.bbox[0], annot.bbox[1], annot.bbox[2], annot.bbox[3]]);
					return;
				}
			});
		}
	});

	// Nothing to predict in the next frame
	if (bbox.length === 0) {
		$('#predict-next-frame').attr('disabled', $('#auto-predict').prop('checked'));
		$('#predict-next-frame').text('Predict Next Frame');
		return;
	}

	data = {
		'project_url': project_url,
		'video': annotation.info.video,
		'fps': annotation.info.fps,		
		'image1': annotation.images[current_frame_index-1].file_name,
		'image2': annotation.images[current_frame_index].file_name,
		'image1_ts': annotation.images[current_frame_index-1].timestamp,
		'image2_ts': annotation.images[current_frame_index].timestamp,
		'bbox': bbox,
	};

	$.ajax({
		url: 'predict_next_frame.php',
		type: 'GET',
		contentType: 'application/json',
		data: data,
	}).done(function(data) {
		JSON.parse(data).forEach(function(bb){
			bb = bb.split(' ');
			var tracklet_id = Number(bb[0]);
			var [x, y, w, h] = [Number(bb[1]), Number(bb[2]), Number(bb[3]), Number(bb[4])];

			annotation.annotations.forEach(function(annot){
				if (annot.image_id === current_frame_index && annot.tracklet_id === tracklet_id && !annot.manual) {
					annot.bbox = [x, y, w, h];
					annot.bbox[0] = Math.max(Math.min(annot.bbox[0], current_image.width - annot.bbox[2]), 0);
					annot.bbox[1] = Math.max(Math.min(annot.bbox[1], current_image.height - annot.bbox[3]), 0);
					annot.manual = true;
				}
			});
		});
		updateAnnotation();
		updateDrawCanvas();
	}).fail(function(data) {
		alert('Next Frame Prediction failed for some reason. Please check log.');
		console.log(data);
	}).always(function() {
		$('#predict-next-frame').attr('disabled', $('#auto-predict').prop('checked'));
		$('#predict-next-frame').text('Predict Next Frame');
	});
}
