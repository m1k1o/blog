FROM php:8.5-apache-trixie

ARG LDAP=false

RUN set -eux; apt-get update; \
	apt-get install -y --no-install-recommends libpq-dev \
	#
	# install gd dependencies
	zlib1g-dev libpng-dev libjpeg62-turbo-dev \
	libwebp-dev libxpm-dev libfreetype-dev; \
	#
	# configure extensions
	docker-php-ext-configure gd --enable-gd \
	--with-jpeg --with-webp --with-xpm --with-freetype; \
	#
	# install extensions (curl and pdo are built into the base image)
	docker-php-ext-install gd pdo_mysql pdo_pgsql exif; \
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
