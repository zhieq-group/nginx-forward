FROM php:cli
MAINTAINER ilovintit <ilovintit@gmail.com>
RUN apt-get update && apt-get install -y nginx --no-install-recommends && rm -r /var/lib/apt/lists/*
COPY . /app
RUN chmod +x /app/start.sh
CMD ["/app/start.sh"]

