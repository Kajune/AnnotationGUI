// random color generation function
function randColor() {
	return "rgb(" + (~~(256 * Math.random())) + ", " + (~~(256 * Math.random())) + ", " + (~~(256 * Math.random())) + ")";
}

function real_scale_(canvas, image, scale) {
	return Math.min(canvas.width / image.width, canvas.height / image.height) * scale;
}

function canvasToImage_(x, y, img_x, img_y, canvas, image, scale) {
	var rs = real_scale_(canvas, image, scale);
	var canvas_offsetX = (canvas.width / rs - image.width) / 2
	var canvas_offsetY = (canvas.height / rs - image.height) / 2

	var x_ = ((-img_x / 2 + x) * canvas.width) / rs - canvas_offsetX;
	var y_ = ((img_y / 2 + y) * canvas.height) / rs - canvas_offsetY;

	return [x_, y_];
}

function imageToCanvas_(x, y, img_x, img_y, canvas, image, scale) {
	var rs = real_scale_(canvas, image, scale);
	var canvas_offsetX = (canvas.width / rs - image.width) / 2
	var canvas_offsetY = (canvas.height / rs - image.height) / 2

	var x_ = (x + canvas_offsetX) * rs / canvas.width + img_x / 2;
	var y_ = (y + canvas_offsetY) * rs / canvas.height - img_y / 2;

	return [x_, y_];
}

function drawGrid(canvas, ctx, mx, my) {
	ctx.strokeStyle = "black";
	ctx.lineWidth = 2;
	ctx.beginPath();
	ctx.setLineDash([15, 5]);
	ctx.moveTo(0, my * canvas.height);
	ctx.lineTo(canvas.width, my * canvas.height);
	ctx.stroke();

	ctx.moveTo(mx * canvas.width, 0);
	ctx.lineTo(mx * canvas.width, canvas.height);
	ctx.stroke();
	ctx.closePath();
}

function drawMakingBox(canvas, ctx, mx, my, img_x, img_y, image, scale) {
	ctx.strokeStyle = "navy";
	ctx.lineWidth = 3;
	ctx.setLineDash([]);

	var [x1, y1] = canvasToImage_(sx, sy, img_x, img_y, canvas, image, scale);
	var [x2, y2] = canvasToImage_(mx, my, img_x, img_y, canvas, image, scale);

	x1 = Math.max(Math.min(x1, image.width), 0);
	x2 = Math.max(Math.min(x2, image.width), 0);
	y1 = Math.max(Math.min(y1, image.height), 0);
	y2 = Math.max(Math.min(y2, image.height), 0);

	[x1, y1] = imageToCanvas_(x1, y1, img_x, img_y, canvas, image, scale);
	[x2, y2] = imageToCanvas_(x2, y2, img_x, img_y, canvas, image, scale);

	ctx.strokeRect(x1 * canvas.width, y1 * canvas.height, (x2 - x1) * canvas.width, (y2 - y1) * canvas.height);			
}

function drawMainImage(canvas, ctx, image, img_x, img_y, rs) {
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	ctx.scale(rs, rs);
	ctx.translate((canvas.width / rs - image.width) / 2, (canvas.height / rs - image.height) / 2);
	ctx.translate(img_x * 0.5 * canvas.width / rs, -img_y * 0.5 * canvas.height / rs);
	ctx.drawImage(image, 0, 0);
	ctx.resetTransform();
}

function drawSubImage(canvas, canvas_main, ctx, image, img_x, img_y, rs) {
	var left = Math.min(image.width, Math.max(0, image.width / 2 - ((img_x + 1) / 2) * canvas_main.width / rs));
	var right = Math.min(image.width, Math.max(0, image.width / 2 + ((1 - img_x) / 2) * canvas_main.width / rs));
	var top = Math.min(image.height, Math.max(0, image.height / 2 - ((1 - img_y) / 2) * canvas_main.height / rs));
	var bottom = Math.min(image.height, Math.max(0, image.height / 2 + ((1 + img_y) / 2) * canvas_main.height / rs));

	var thumb_scale_x = canvas.width / image.width;
	var thumb_scale_y = canvas.height / image.height;

	ctx.lineWidth = 2;
	ctx.strokeStyle = 'red';
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	ctx.strokeRect(left * thumb_scale_x, top * thumb_scale_y, (right - left) * thumb_scale_x, (bottom - top) * thumb_scale_y);
	ctx.resetTransform();
}

function drawTracklets(annotation, selected_box, hovered_box, hovered_cp, no_link_list,
						 canvas, ctx, image, img_x, img_y, scale) {
	if (!annotation || annotation.annotations.length <= 0) {
		return;
	}

	ctx.lineWidth = 2;
	ctx.setLineDash([]);

	var text_width = canvas.width * 0.06;
	var text_height = canvas.height * 0.02;
	var cp_radius = canvas.width * 0.003;

	ctx.font = 'bold ' + text_height + 'px sans-serif';
	ctx.textBaseline = 'bottom';

	annotation.annotations.forEach(annot => {
		if (annot.image_id == frame_index - 1) {
			var [x1, y1] = imageToCanvas_(annot.bbox[0], annot.bbox[1], img_x, img_y, canvas, image, scale);
			var [x2, y2] = imageToCanvas_(annot.bbox[2] + annot.bbox[0], annot.bbox[3] + annot.bbox[1], img_x, img_y, canvas, image, scale);

			// Omit drawing if out of canvas
			if ((x1 < 0 && x2 < 0) || (y1 < 0 && y2 < 0) || (1 < x1 && 1 < x2) || (1 < y1 && 1 < y2)) {
				return;
			}

			if (no_link_list.includes(annot.tracklet_id)) {
				ctx.globalAlpha = 0.25;
			}

			// Draw box
			ctx.strokeStyle = tracklet_colors[annot.tracklet_id];
			ctx.fillStyle = tracklet_colors[annot.tracklet_id];
			if (!no_link_list.includes(annot.tracklet_id)) {
				ctx.globalAlpha = annot.id == selected_box || annot.id == hovered_box ? 0.5 : 0.25;
			}
			ctx.fillRect(x1 * canvas.width, y1 * canvas.height, (x2 - x1) * canvas.width, (y2 - y1) * canvas.height);
			if (!no_link_list.includes(annot.tracklet_id)) {
				ctx.globalAlpha = 1.0;
			}
			ctx.strokeRect(x1 * canvas.width, y1 * canvas.height, (x2 - x1) * canvas.width, (y2 - y1) * canvas.height);
			if (annot.id == selected_box) {
				ctx.strokeRect(x1 * canvas.width - 3, y1 * canvas.height - 3, 
					(x2 - x1) * canvas.width + 6, (y2 - y1) * canvas.height + 6);
			}

			// Draw control points
			if (annot.id == selected_box || annot.id == hovered_box) {
				var cpList = [[x1, y1], [x2, y1], [x1, y2], [x2, y2], [(x1+x2)/2, y1], [x1, (y1+y2)/2], [(x1+x2)/2, y2], [x2,(y1+y2)/2]];
				for (var j = 0; j < cpList.length; j++) {
					ctx.beginPath();
					if (j == hovered_cp && annot.id == hovered_box) {
						ctx.fillStyle = 'white';
						ctx.arc(cpList[j][0] * canvas.width, cpList[j][1] * canvas.height, cp_radius * 2, 0, Math.PI*2, false);
					} else {
						ctx.fillStyle = 'black';
						ctx.arc(cpList[j][0] * canvas.width, cpList[j][1] * canvas.height, cp_radius, 0, Math.PI*2, false);
					}
					ctx.fill();

					ctx.fillStyle = tracklet_colors[annot.tracklet_id];
					ctx.beginPath();
					if (j == hovered_cp && annot.id == hovered_box) {
						ctx.arc(cpList[j][0] * canvas.width, cpList[j][1] * canvas.height, cp_radius * 1.5, 0, Math.PI*2, false);
					} else {
						ctx.arc(cpList[j][0] * canvas.width, cpList[j][1] * canvas.height, cp_radius * 0.75, 0, Math.PI*2, false);					
					}
					ctx.fill();
				};
			}

			// Draw text area
			ctx.strokeRect(x1 * canvas.width, y1 * canvas.height, text_width, -text_height);
			ctx.fillStyle = 'white';
			ctx.fillRect(x1 * canvas.width, y1 * canvas.height, text_width, -text_height);
			ctx.fillStyle = 'black';
			for (var i = 0; i < annotation.categories.length; i++) {
				if (annotation.categories[i].id === annot.category_id) {
					ctx.fillText(annotation.categories[i].name, x1 * canvas.width + 3, y1 * canvas.height, text_width - 6);
					break;
				}
			}

			ctx.globalAlpha = 1.0;
		}
	});
}