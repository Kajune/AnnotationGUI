# Super Sophisticated Annotation GUI
An annotation tool for Object Detection, Multi Object Tracking, Re-Identification...

![screenshot](https://user-images.githubusercontent.com/14792604/97583380-3a17dd80-1a3a-11eb-8783-18cba0a445c3.png)

## Features
- Simple yet easy to use annotation GUI
- Tracklet annotation (for Re-ID)
- Extended MS-COCO format annotation output
- Automatic tracklet prediction in subsequent frames (and easy to replace with your tracking algorithm)
- Server-Client system for co-operation

## How to use
Try following commands.
```
git clone https://github.com/Kajune/AnnotationGUI
cd AnnotationGUI
(sudo) docker-compose up
```
Then, access localhost:8686 (or specify IP address and Port as you like).

## Dependencies
- JQuery
- Bootstrap4
