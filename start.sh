#!/bin/sh
php /app/build-config.php
cp /app/nginx.conf /etc/nginx/nginx.conf -f
nginx -g "daemon off;"