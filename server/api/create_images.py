import sys, os, json, subprocess
import glob
import collections as cl
import cv2
import numpy as np

project_dir = sys.argv[1]
project_name = sys.argv[2]
annotation_fps = float(sys.argv[3])
video_name = sys.argv[4]

new_video_location = os.path.join(project_dir, project_name, video_name)

"""
cmd = 'ffprobe -v error -show_entries stream=width,height -of csv=p=0:s=x %s' % new_video_location
output = subprocess.run([cmd], encoding='ascii', stdout=subprocess.PIPE, shell=True).stdout
if '\n' in output:
	width, height = output.split('\n')[0].split('x')
else:
	width, height = output.split('x')
os.system('ffmpeg -i %s -vcodec mjpeg -r %f %s' % 
	(new_video_location, annotation_fps, os.path.join(project_dir, project_name, 'images', '%06d.jpg')))
"""

label_location = os.path.join(project_dir, project_name, 'annotation.json')
annotation = json.load(open(label_location))

cap = cv2.VideoCapture(new_video_location)
width, height = cap.get(cv2.CAP_PROP_FRAME_WIDTH), cap.get(cv2.CAP_PROP_FRAME_HEIGHT)

if 'images' in annotation:
	for image in annotation['images']:
		ts = image['timestamp']
		cap.set(cv2.CAP_PROP_POS_MSEC, ts)
		ret, frame = cap.read()

		if ret:
			width, height = image['width'], image['height']
			if frame.shape[0] != height or frame.shape[1] != width:
				frame = cv2.resize(frame, (width, height))
			cv2.imwrite(os.path.join(project_dir, project_name, 'images', image['file_name']), frame)
			image['coco_url'] = os.path.join(project_name, 'images', image['file_name'])

	if 'annotations' not in annotation:
		annotation['annotations'] = []

else:
	annotation['images'] = []
	annotation['annotations'] = []

	count = 0
	frame_count = cap.get(cv2.CAP_PROP_FRAME_COUNT)
	fps = cap.get(cv2.CAP_PROP_FPS)
	max_count = frame_count / fps * annotation_fps

	while cap.isOpened() and count <= max_count:
		ts = count / annotation_fps * 1000
		cap.set(cv2.CAP_PROP_POS_MSEC, ts)
#		ts = cap.get(cv2.CAP_PROP_POS_MSEC)
		ret, frame = cap.read()
		if not ret:
			break

		fname = '%06d.jpg' % count
		cv2.imwrite(os.path.join(project_dir, project_name, 'images', fname), frame)

		image = cl.OrderedDict()
		image['file_name'] = fname
		image['coco_url'] = os.path.join(project_name, 'images', fname)
		image['height'] = int(height)
		image['width'] = int(width)
		image['id'] = count
		image['timestamp'] = ts
		annotation['images'].append(image)

		np.savetxt(os.path.join(project_dir, project_name, 'tmp.txt'), np.int32([count / max_count * 100]), fmt='%d')

		count += 1

json.dump(annotation, open(label_location, 'w'), indent=2)

"""
	imageList = sorted(glob.glob(os.path.join(project_dir, project_name, 'images', '*')))
	for i, imgPath in enumerate(imageList):
		image = cl.OrderedDict()
		fname = os.path.basename(imgPath)
		image['file_name'] = fname
		image['coco_url'] = imgPath.replace(project_dir, '')
		image['height'] = int(width)
		image['width'] = int(height)
		image['id'] = i
		annotation['images'].append(image)
"""