FROM php:7.4-apache

WORKDIR /var/www/html

MAINTAINER Miroslav Sedivy

RUN apt-get -y update --fix-missing

# Install curl
RUN apt-get -y install libcurl4-openssl-dev
RUN docker-php-ext-install curl

# Install PDO MYSQL
RUN docker-php-ext-install pdo pdo_mysql

COPY . .

VOLUME "i"
VOLUME "t"
VOLUME "data"
VOLUME "custom.ini"

RUN chown -R www-data:www-data . \
    && a2enmod rewrite
