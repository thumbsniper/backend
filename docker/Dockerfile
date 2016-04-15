FROM php:5-apache

RUN apt-key adv --keyserver keyserver.ubuntu.com --recv 7F0CEB10
RUN echo "deb http://repo.mongodb.org/apt/debian wheezy/mongodb-org/3.0 main" | tee /etc/apt/sources.list.d/mongodb-org-3.0.list

RUN apt-get clean && apt-get update && apt-get install -y \
		git \
		ImageMagick \
		libbz2-dev \
		libcurl3-dev \
		libfreetype6-dev \
		libgeoip-dev \
		libicu-dev \
		libjpeg62-turbo-dev \
		libncurses5-dev \
		libmagickwand-dev \
		libpng12-dev \
		libssl-dev \
		mongodb-org-shell \
		netcat \
		nodejs-legacy \
		npm \
		php-pear \
		zlib1g-dev \
	--no-install-recommends && rm -r /var/lib/apt/lists/*

RUN pecl install \
	geoip \
	imagick \
	mongo

#RUN curl -L https://pecl.php.net/get/redis-2.2.7.tgz > /tmp/redis.tgz \
RUN curl -L https://pecl.php.net/get/redis > /tmp/redis.tgz \
	&& mkdir /usr/src/php/ext/redis \
	&& tar -xf /tmp/redis.tgz -C /usr/src/php/ext/redis --strip-components 1 \
	&& rm /tmp/redis.tgz 

RUN mkdir -p /usr/local/share/GeoIP \
	&& cd /usr/local/share/GeoIP \
	&& curl -o /usr/local/share/GeoIP/GeoIPCity.dat.gz http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz \
	&& gunzip /usr/local/share/GeoIP/GeoIPCity.dat.gz

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

RUN docker-php-ext-install \
	bz2 \
	exif \
	gd \
	gettext \
	intl \
	json \
	mbstring \
	mysqli \
	pdo \
	pdo_mysql \
	phar \
	redis \
	zip

RUN docker-php-ext-enable \
	geoip \
	imagick \
	mongo

RUN pear install \
        Mail \
        Mail_Mime \
        Net_SMTP

RUN curl -s https://getcomposer.org/installer | php -- --install-dir /usr/local/bin --filename composer

RUN npm install -g bower

COPY etc/apache2 /etc/apache2

RUN a2enmod access_compat expires headers remoteip rewrite
RUN a2enconf https remoteip rewrite
RUN a2ensite 000-default api img panel

RUN git clone -b v0.9.6 https://github.com/thumbsniper/backend.git /opt/thumbsniper

RUN composer --working-dir=/opt/thumbsniper update

RUN echo 'always_populate_raw_post_data=-1' >> /usr/local/etc/php/php.ini \
        && echo 'geoip.custom_directory=/usr/local/share/GeoIP' >> /usr/local/etc/php/php.ini \
        && echo 'post_max_size=128M' >> /usr/local/etc/php/php.ini \
        && echo 'upload_max_filesize=128M' >> /usr/local/etc/php/php.ini \
        && echo 'include_path=.:/usr/share/php5:/usr/share/php5/PEAR:/opt/thumbsniper' >> /usr/local/etc/php/php.ini \
        && echo 'open_basedir=/opt/thumbsniper:/usr/share/php5:/tmp' >> /usr/local/etc/php/php.ini \
        && echo 'upload_tmp_dir=/tmp' >> /usr/local/etc/php/php.ini \
        && echo 'session.save_path=/tmp' >> /usr/local/etc/php/php.ini \
        && echo 'sys_temp_dir=/tmp' >> /usr/local/etc/php/php.ini \
        && echo 'error_reporting=E_ALL' >> /usr/local/etc/php/php.ini \
        && echo 'cgi.fix_pathinfo=0' >> /usr/local/etc/php/php.ini \
        && echo 'display_errors=Off' >> /usr/local/etc/php/php.ini \
        && echo 'expose_php=Off' >> /usr/local/etc/php/php.ini \
        && echo 'log_errors=On' >> /usr/local/etc/php/php.ini \
        && echo 'date.timezone=Europe/Berlin' >> /usr/local/etc/php/php.ini

WORKDIR /opt/thumbsniper/web_panel
RUN bower --allow-root --config.interactive=false update
WORKDIR /

RUN groupmod -g 999 www-data \
	&& usermod -u 999 -g 999 www-data \
	&& chown -R www-data:www-data /var/lock/apache2 /var/run/apache2 /var/log/apache2 /var/www/html \
	&& chown www-data:www-data /opt/thumbsniper/web_panel/templates_c

COPY config/backend-config.inc.php /opt/thumbsniper/config/backend-config.inc.php
COPY config/panel-config.inc.php /opt/thumbsniper/config/panel-config.inc.php

COPY init.sh /

ENV MONGO_HOST=mongo MONGO_PORT=27017 MONGO_USER=thumbsniper MONGO_PASS=secret MONGO_DB=thumbsniper

CMD ["/init.sh"]

