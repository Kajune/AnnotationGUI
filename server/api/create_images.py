import sys, os, json, subprocess
import glob
import collections as cl

project_dir = sys.argv[1]
project_name = sys.argv[2]
annotation_fps = float(sys.argv[3])
video_name = sys.argv[4]

new_video_location = os.path.join(project_dir, project_name, video_name)

cmd = 'ffprobe -v error -show_entries stream=width,height -of csv=p=0:s=x %s' % new_video_location
output = subprocess.run([cmd], encoding='ascii', stdout=subprocess.PIPE, shell=True).stdout
if '\n' in output:
	width, height = output.split('\n')[0].split('x')
else:
	width, height = output.split('x')
os.system('ffmpeg -i %s -vcodec mjpeg -r %f %s' % 
	(new_video_location, annotation_fps, os.path.join(project_dir, project_name, 'images', '%06d.jpg')))

label_location = os.path.join(project_dir, project_name, 'annotation.json')
annotation = json.load(open(label_location))
annotation['images'] = []
annotation['annotations'] = []

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
json.dump(annotation, open(label_location, 'w'), indent=2)
