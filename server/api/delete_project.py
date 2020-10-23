import sys, os, shutil

project_dir = sys.argv[1]
project_name = sys.argv[2]

shutil.rmtree(os.path.join(project_dir, project_name))
