mkdir -p volume
docker run --name annot_gui -p 8686:80 -v $PWD/volume:/var/www/html/projects/ kajune/annot_gui:latest
