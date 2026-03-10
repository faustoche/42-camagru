FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
	libpq-dev \
	libgd-dev \
	msmtp \
	msmtp-mta \
	&& docker-php-ext-install pdo pdo_mysql gd

COPY msmtprc /etc/msmtprc

RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/mail.ini

RUN echo "upload_max_filesize = 10M\npost_max_size = 10M" > /usr/local/etc/php/conf.d/uploads.ini