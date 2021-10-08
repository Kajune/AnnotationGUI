import sys, os, shutil, json, base64, cv2
import collections as cl

project_dir = sys.argv[1]
project_name = sys.argv[2]

image_list_json = json.loads(base64.b64decode(sys.argv[3]))

label_name = sys.argv[4]
label_location = sys.argv[5]

os.makedirs(os.path.join(project_dir, project_name))
os.makedirs(os.path.join(project_dir, project_name, 'images'))

#
# Image
#
for i in range(len(image_list_json['name'])):
	new_image_location = os.path.join(project_dir, project_name, 'images', image_list_json['name'][i])
	shutil.move(image_list_json['tmp_name'][i], new_image_location)

#
# Annotation
#
annotation = json.load(open(label_location))
annotation['info'] = cl.OrderedDict()
annotation['info']['type'] = 'image'

if 'images' not in annotation:
	annotation['images'] = []

	for i in range(len(image_list_json['name'])):
		image = cl.OrderedDict()
		image['file_name'] = image_list_json['name'][i]
		image['coco_url'] = os.path.join(project_name, 'images', image_list_json['name'][i])
		img = cv2.imread(os.path.join(project_dir, image['coco_url']))
		image['height'] = int(img.shape[0])
		image['width'] = int(img.shape[1])
		image['id'] = i
		annotation['images'].append(image)

if 'annotations' not in annotation:
	annotation['annotations'] = []

json.dump(annotation, open(os.path.join(project_dir, project_name, 'annotation.json'), 'w'), indent=2)

