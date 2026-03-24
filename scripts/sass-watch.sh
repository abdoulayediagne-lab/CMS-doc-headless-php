#!/bin/sh
set -eu

npm install -g sass
mkdir -p /workspace/front/public /workspace/front/backoffice /workspace/app/public/css

# Compile SASS to all locations
sass --watch --no-source-map /workspace/sass/main.scss:/workspace/front/public/main.css &
sass --watch --no-source-map /workspace/sass/main.scss:/workspace/front/backoffice/main.css &
sass --watch --no-source-map /workspace/sass/main.scss:/workspace/app/public/css/main.css &

wait
