FROM php:7.4-apache

MAINTAINER Miroslav Sedivy

ARG LDAP=false

RUN set -eux; apt-get update; \
	apt-get install -y --no-install-recommends \
	#
	# install curl
	libcurl4-openssl-dev \
	#
	# install gd dependencies
	zlib1g-dev libpng-dev libjpeg-dev \
	libwebp-dev libxpm-dev libfreetype6-dev; \
	#
	# configure extensions
	docker-php-ext-configure gd --enable-gd \
	--with-jpeg --with-webp --with-xpm --with-freetype; \
	#
	# install extensions
	docker-php-ext-install curl gd pdo pdo_mysql; \
	#
	# LDAP support
	if [ -n "$LDAP" ] && [ "$LDAP" = "true" ]; then \
		apt-get install -y --no-install-recommends libldb-dev libldap2-dev; \
		docker-php-ext-install ldap; \
	fi; \
	#
	# set up environment
	a2enmod rewrite; \
	#
	# clean up
	rm -rf /var/lib/apt/lists/*;

#
# copy files
COPY --chown=33:33 . /var/www/html

VOLUME /var/www/html/data
