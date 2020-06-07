FROM php:7.4-apache

WORKDIR /var/www/html

MAINTAINER Miroslav Sedivy

# Install dependencies
RUN apt-get update && apt-get install -y \
	libcurl4-openssl-dev \
	zlib1g-dev libpng-dev libjpeg-dev \
	libwebp-dev libxpm-dev libfreetype6-dev \
 && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-configure gd --enable-gd \
	--with-jpeg --with-webp --with-xpm --with-freetype \
 && docker-php-ext-install curl gd pdo pdo_mysql \
 && a2enmod rewrite

# Copy app files
COPY . .

VOLUME "/var/www/html/data"
