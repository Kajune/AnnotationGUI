if not exist "volume" mkdir volume
docker run --name annot_gui -p 8686:80 -v "%cd%"/volume:/var/www/html/projects/ kajune/annot_gui:latest
