import sys, os, shutil, json
import collections as cl

project_dir = sys.argv[1]
project_name = sys.argv[2]
annotation_fps = float(sys.argv[3])

video_name = sys.argv[4]
video_location = sys.argv[5]
label_name = sys.argv[6]
label_location = sys.argv[7]

os.makedirs(os.path.join(project_dir, project_name))
os.makedirs(os.path.join(project_dir, project_name, 'images'))

#
# Video
#
new_video_location = os.path.join(project_dir, project_name, video_name)
shutil.move(video_location, new_video_location)
#os.system('ffmpeg -i %s -vcodec mjpeg -r %f %s' % 
#	(new_video_location, annotation_fps, os.path.join(project_dir, project_name, 'images', '%06d.jpg')))

#
# Annotation
#
annotation = json.load(open(label_location))
annotation['info'] = cl.OrderedDict()
annotation['info']['video'] = video_name
annotation['info']['fps'] = annotation_fps

json.dump(annotation, open(os.path.join(project_dir, project_name, 'annotation.json'), 'w'), indent=2)

