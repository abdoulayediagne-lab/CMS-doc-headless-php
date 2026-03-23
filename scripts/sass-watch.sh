#!/bin/sh
set -eu

npm install -g sass
mkdir -p /workspace/front/public /workspace/front/backoffice

sass --watch --no-source-map /workspace/sass/main.scss:/workspace/front/public/main.css &
sass --watch --no-source-map /workspace/sass/main.scss:/workspace/front/backoffice/main.css &

wait
