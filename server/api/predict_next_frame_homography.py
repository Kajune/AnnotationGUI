import sys, json, os
import cv2
import numpy as np

data = json.load(open(sys.argv[1]))

img1 = cv2.imread(os.path.join(data['project_url'], 'images', data['image1']))
img2 = cv2.imread(os.path.join(data['project_url'], 'images', data['image2']))

#
# Feature matching
#
orb = cv2.ORB_create(1000)
kp1, des1 = orb.detectAndCompute(img1, None)
kp2, des2 = orb.detectAndCompute(img2, None)
bf = cv2.BFMatcher(cv2.NORM_HAMMING, crossCheck=True)

matches = bf.match(des1, des2)

good_match_rate = 0.5
matches = sorted(matches, key=lambda x: x.distance)
good = matches[:int(len(matches) * good_match_rate)]

#
# Homography Transformation
#
min_match = 10
if len(good) > min_match:
	src_pts = np.float32([kp1[m.queryIdx].pt for m in good]).reshape(-1,1,2)
	dst_pts = np.float32([kp2[m.trainIdx].pt for m in good]).reshape(-1,1,2)
	homography, mask = cv2.findHomography(src_pts, dst_pts, cv2.RANSAC)

cp = []
for bb in data['bbox']:
	index = int(bb[0])
	x = float(bb[1])
	y = float(bb[2])
	w = float(bb[3])
	h = float(bb[4])

	cp.append(np.float32([[x, y], [x+w, y], [x, y+h], [x+w, y+h]]))

cp = np.float32(cp).reshape(-1,4,2)
cp = cv2.perspectiveTransform(cp, homography)

for i, bb in enumerate(data['bbox']):
	index = int(bb[0])
	w1 = cp[i,1,0] - cp[i,0,0]
	w2 = cp[i,3,0] - cp[i,2,0]
	h1 = cp[i,2,1] - cp[i,0,1]
	h2 = cp[i,3,1] - cp[i,1,1]

	w = (w1 + w2) / 2
	h = (h1 + h2) / 2

	cx = np.mean(cp[i,:,0])
	cy = np.mean(cp[i,:,1])

	x = cx - w / 2
	y = cy - h / 2

	print(index, x, y, w, h)
