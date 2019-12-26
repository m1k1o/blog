FROM php:7.4-apache

WORKDIR /var/www/html

MAINTAINER Miroslav Sedivy

RUN apt-get -y update --fix-missing

# Install curl
RUN apt-get -y install libcurl4-openssl-dev
RUN docker-php-ext-install curl

# Install PDO MYSQL
RUN docker-php-ext-install pdo pdo_mysql

# Install GD
RUN apt-get -y install zlib1g-dev libpng-dev libjpeg-dev \
	libwebp-dev libxpm-dev libfreetype6-dev
RUN docker-php-ext-configure gd --enable-gd --with-jpeg \
	--with-webp --with-xpm --with-freetype
RUN docker-php-ext-install gd

COPY . .

VOLUME "data"

RUN chown -R www-data:www-data . \
    && a2enmod rewrite
