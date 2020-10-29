import sys, json, os
import cv2

data = json.load(open(sys.argv[1]))

img1 = cv2.imread(os.path.join(data['project_url'], 'images', data['image1']))
img2 = cv2.imread(os.path.join(data['project_url'], 'images', data['image2']))

for bb in data['bbox']:
	index = int(bb[0])
	x = float(bb[1])
	y = float(bb[2])
	w = float(bb[3])
	h = float(bb[4])

	print(index, x, y, w, h)
