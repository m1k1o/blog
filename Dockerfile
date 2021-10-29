FROM php:7.4-apache

RUN set -eux; apt-get update; \
	apt-get install -y --no-install-recommends libpq-dev \
	#
	# install curl
	libcurl4-openssl-dev \
	#
	# install gd dependencies
	zlib1g-dev libpng-dev libjpeg-dev \
	libwebp-dev libxpm-dev libfreetype6-dev; \
	#
	# clean up
	rm -rf /var/lib/apt/lists/*; \
	#
	# configure extensions
	docker-php-ext-configure gd --enable-gd \
	--with-jpeg --with-webp --with-xpm --with-freetype; \
	#
	# install extensions
	docker-php-ext-install curl gd pdo pdo_mysql pdo_pgsql exif; \
	#
	# set up environment
	a2enmod rewrite;

#
# copy files
COPY --chown=33:33 . /var/www/html

VOLUME /var/www/html/data
