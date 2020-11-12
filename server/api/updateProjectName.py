import sys, os, json
import collections as cl
import cv2

project_dir = sys.argv[1]
old_name = sys.argv[2]
new_name = sys.argv[3]

data = json.load(open(os.path.join(project_dir, new_name, 'annotation.json')))
for image in data['images']:
	image['coco_url'] = image['coco_url'].replace(old_name, new_name)
json.dump(data, open(os.path.join(project_dir, new_name, 'annotation.json'), 'w'), indent=2)
