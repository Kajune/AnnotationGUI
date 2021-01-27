FROM php:7.4.8-apache

RUN apt update
RUN apt install -y python3 python3-pip
RUN apt install -y ffmpeg
RUN pip3 install --upgrade pip
RUN pip3 install opencv-contrib-python

#COPY server/ /var/www/html/
COPY php.ini /usr/local/etc/php/
RUN mkdir /var/www/html/projects
RUN chmod 777 /var/www/html/projects
