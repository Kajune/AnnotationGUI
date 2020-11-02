FROM php:7.4.8-apache

RUN apt update
RUN apt install -y python3 python3-pip
RUN apt install -y ffmpeg
RUN pip3 install opencv-python opencv-contrib-python

COPY server/ /var/www/html/
COPY docker/php.ini /usr/local/etc/php/
RUN mkdir -m a+rw /var/www/html/projects
