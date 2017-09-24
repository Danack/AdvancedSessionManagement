FROM debian:9

USER root

RUN apt-get update -qq \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y nginx php7.0-fpm git \
    php-xml php-mbstring \
    php-redis ca-certificates lsb-release apt-transport-https gnupg

WORKDIR /var/app

CMD tail -f /var/app/readme.MD
