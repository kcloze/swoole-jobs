FROM daocloud.io/library/ubuntu:latest
FROM daocloud.io/library/php:7.0.25-cli

MAINTAINER Kcloze <pei.greet@qq.com>
RUN sed -i "s/archive.ubuntu./mirrors.aliyun./g" /etc/apt/sources.list 
RUN sed -i "s/deb.debian.org/mirrors.aliyun.com/g" /etc/apt/sources.list 
RUN sed -i "s/security.debian.org/mirrors.aliyun.com\/debian-security/g" /etc/apt/sources.list

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        g++ \
        libicu-dev \
        libxml2-dev \
        htop \
        git \
        redis-server \
        vim \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        zlib1g-dev \
        libmagickwand-dev --no-install-recommends

RUN docker-php-ext-configure intl \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install soap \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install opcache \
    && docker-php-ext-install mysqli \
    && pecl install imagick  \
    && docker-php-ext-enable imagick \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && pecl install redis \
    && docker-php-ext-enable redis

VOLUME ["/data"]
# COPY . /usr/src/myapp
WORKDIR /data
# composer
# RUN groupadd docker
# RUN useradd kcloze -g docker -m
# RUN curl -sS https://install.phpcomposer.com/installer | php -- --install-dir=/usr/local/bin --filename=composer
# USER kcloze
# RUN composer config -g repo.packagist composer https://packagist.laravel-china.org
# RUN composer global require hirak/prestissimo
# RUN composer install
# USER root
# RUN redis-server &
CMD [ "/bin/bash"]