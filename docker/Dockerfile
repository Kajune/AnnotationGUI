FROM php:7.4.8-apache

RUN apt update
RUN apt install -y python3 python3-pip
RUN pip3 install opencv-python

RUN apt install -y ffmpeg

COPY server/ /var/www/html/
COPY docker/php.ini /usr/local/etc/php/
RUN chmod a+rw /var/www/html/projects

