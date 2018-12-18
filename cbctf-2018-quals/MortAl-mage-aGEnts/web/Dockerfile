FROM php:5.6-apache

RUN apt-get update && \
    apt-get upgrade && \
    docker-php-ext-install mysqli pdo_mysql opcache

RUN a2enmod rewrite && \
    rm -rf /etc/apache2/sites-enabled && \
    mkdir /etc/apache2/sites-enabled

ADD ./default.conf /etc/apache2/sites-enabled/default.conf
