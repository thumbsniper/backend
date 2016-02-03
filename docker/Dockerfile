FROM opensuse:13.2

MAINTAINER Thomas Schulte <thomas@cupracer.de>

EXPOSE 9100

CMD ["/usr/sbin/php-fpm"]

RUN zypper --non-interactive ref && zypper --non-interactive in curl

RUN rpm --import \
	http://download.opensuse.org/repositories/devel:/languages:/nodejs/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/home:/TERROR-FX:/thumbsniper:/utilities/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php:/applications/openSUSE_13.2/repodata/repomd.xml.key \
	http://download.opensuse.org/repositories/server:/php:/extensions/openSUSE_13.2/repodata/repomd.xml.key

RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/devel:/languages:/nodejs/openSUSE_13.2/devel:languages:nodejs.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/home:/TERROR-FX:/thumbsniper:/utilities/openSUSE_13.2/home:TERROR-FX:thumbsniper:utilities.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php/openSUSE_13.2/server:php.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php:/applications/openSUSE_13.2/server:php:applications.repo
RUN /usr/bin/zypper ar --refresh http://download.opensuse.org/repositories/server:/php:/extensions/openSUSE_13.2/server:php:extensions.repo

RUN zypper --non-interactive ref && zypper --non-interactive in \
	ca-certificates-mozilla \
	git \
	ImageMagick \
	nodejs-npm \
	php-composer \
	php5 \
	php5-bz2 \
	php5-ctype \
	php5-curl \
	php5-exif \
	php5-fastcgi \
	php5-fpm \
	php5-gd \
	php5-gettext \
	php5-imagick \
	php5-intl \
	php5-json \
	php5-mbstring \
	php5-mongo \
	php5-openssl \
	php5-pear-Mail \
	php5-pear-Mail_Mime \
	php5-pear-Net_SMTP \
	php5-zip \
	php5-zlib

RUN /usr/sbin/groupadd -g 999 phpfpm
RUN /usr/sbin/useradd -u 999 -g 999 -c "docker user" -m phpfpm

RUN cat /etc/php5/fpm/php-fpm.conf.default | \
	sed -e "s/^;error_log = log\/php-fpm.log/error_log = \/proc\/self\/fd\/2/" | \
	sed -e "s/^;daemonize = yes/daemonize = no/" | \
	sed -e "s/^;prefix = \/path\/to\/pools\/\$pool/prefix = \//" | \
	sed -e "s/^user = nobody/user = phpfpm/" | \
	sed -e "s/^group = nobody/group = phpfpm/" | \
	sed -e "s/^listen = 127.0.0.1:9000/listen = 9100/" | \
	sed -e "s/^;listen.owner = nobody/listen.owner = root/" | \
	sed -e "s/^;listen.group = nobody/listen.group = root/" | \
	sed -e "s/^;listen.mode = 0660/listen.mode = 0666/" | \
	sed -e "s/^pm.max_children = 5/pm.max_children = 1536/" | \
	sed -e "s/^pm.start_servers = 2/pm.start_servers = 448/" | \
	sed -e "s/^pm.min_spare_servers = 1/pm.min_spare_servers = 64/" | \
	sed -e "s/^pm.max_spare_servers = 3/pm.max_spare_servers = 512/" | \
	sed -e "s/^;access.log = log\/\$pool.access.log/access.log = \/proc\/self\/fd\/2/" | \
	sed -e "s/^;chdir = \/var\/www/chdir = \//" \
	> /etc/php5/fpm/php-fpm.conf

COPY config/php-fpm.conf.include /root/php-fpm.conf.include
RUN cat /root/php-fpm.conf.include >> /etc/php5/fpm/php-fpm.conf

RUN git clone https://github.com/thumbsniper/backend.git /opt/thumbsniper # && cd /opt/thumbsniper && git checkout v0.9.2

RUN composer --working-dir=/opt/thumbsniper update

RUN npm install -g bower
WORKDIR /opt/thumbsniper/web_panel
RUN bower --allow-root --config.interactive=false update

RUN chown 999:999 /opt/thumbsniper/web_panel/templates_c

COPY config/backend-config.inc.php /opt/thumbsniper/config/backend-config.inc.php
COPY config/panel-config.inc.php /opt/thumbsniper/config/panel-config.inc.php

RUN zypper --non-interactive ref && zypper --non-interactive up
