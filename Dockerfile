FROM php:8.5-apache-trixie

RUN set -eux; apt-get update; \
	apt-get install -y --no-install-recommends libpq-dev \
	#
	# install gd dependencies
	zlib1g-dev libpng-dev libjpeg62-turbo-dev \
	libwebp-dev libxpm-dev libfreetype-dev \
	#
	# install zip dependencies (zip is needed to export posts)
	libzip-dev \
	#
	# install imagick dependencies (libheif plugin is needed for HEIC images)
	libmagickwand-7.q16-10 libheif-plugin-libde265 libmagickwand-dev; \
	#
	# clean up
	rm -rf /var/lib/apt/lists/*; \
	#
	# configure extensions
	docker-php-ext-configure gd --enable-gd \
	--with-jpeg --with-webp --with-xpm --with-freetype; \
	#
	# install extensions (curl and pdo are built into the base image)
	docker-php-ext-install gd pdo_mysql pdo_pgsql exif zip; \
	pecl install imagick; \
	docker-php-ext-enable imagick; \
	#
	# remove imagick build dependencies
	apt-get purge -y --auto-remove libmagickwand-dev; \
	#
	# set up environment
	a2enmod rewrite;

#
# copy files
COPY --chown=33:33 . /var/www/html

VOLUME /var/www/html/data
