import sys, json, os
import cv2
import numpy as np

OPENCV_OBJECT_TRACKERS = {
	"csrt": cv2.TrackerCSRT_create,
	"kcf": cv2.TrackerKCF_create,
	"boosting": cv2.TrackerBoosting_create,
	"mil": cv2.TrackerMIL_create,
	"tld": cv2.TrackerTLD_create,
	"medianflow": cv2.TrackerMedianFlow_create,
	"mosse": cv2.TrackerMOSSE_create,
}

data = json.load(open(sys.argv[1]))
cap = cv2.VideoCapture(os.path.join(data['project_url'], data['video']))
image1_ts = float(data['image1_ts'])
image2_ts = float(data['image2_ts'])

trackers = cv2.MultiTracker_create()

# Because OpenCV VideoCapture has samall bug on timestamp
cap.set(cv2.CAP_PROP_POS_MSEC, image2_ts)
cap.read()
goal_ts = cap.get(cv2.CAP_PROP_POS_MSEC)

cap.set(cv2.CAP_PROP_POS_MSEC, image1_ts)
ret, frame = cap.read()

boxes = []
for bb in data['bbox']:
	index = int(bb[0])
	x = float(bb[1])
	y = float(bb[2])
	w = float(bb[3])
	h = float(bb[4])

	tracker = OPENCV_OBJECT_TRACKERS['csrt']()
	trackers.add(tracker, frame, (x, y, w, h))
	boxes.append((x, y, w, h))

while cap.isOpened():
	ret, frame = cap.read()
	(success, boxes) = trackers.update(frame)
	if goal_ts <= cap.get(cv2.CAP_PROP_POS_MSEC) or not ret:
		break

for i, bb in enumerate(boxes):
	index = int(data['bbox'][i][0])
	x, y, w, h = bb

	print(index, x, y, w, h)