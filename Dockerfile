FROM alpine:3.9

LABEL maintainer="Swoole Jobs <pei.greet@qq.com>" version="1.0" license="MIT"

##
# ---------- building ----------
##
RUN set -ex \
        # change apk source repo
        && sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/' /etc/apk/repositories \
        && apk update \
        && apk add --no-cache \
        # Install base packages ('ca-certificates' will install 'nghttp2-libs')
        ca-certificates \
        curl \
        wget \
        tar \
        xz \
        libressl \
        tzdata \
        pcre \
        php7 \
        php7-pecl-amqp \
        php7-bcmath \
        php7-curl \
        php7-ctype \
        php7-dom \
        php7-fileinfo \
        php7-gd \
        php7-iconv \
        php7-json \
        php7-mbstring \
        php7-mysqlnd \
        php7-openssl \
        php7-pdo \
        php7-pdo_mysql \
        php7-pdo_sqlite \
        php7-phar \
        php7-posix \
        php7-redis \
        php7-simplexml \
        php7-sockets \
        php7-sodium \
        php7-sysvshm \
        php7-sysvmsg \
        php7-sysvsem \
        php7-tokenizer \
        php7-zip \
        php7-zlib \
        php7-xml \
        php7-xmlreader \
        php7-xmlwriter \
        php7-pcntl \
        && apk del --purge *-dev \
        && rm -rf /var/cache/apk/* /tmp/* /usr/share/man /usr/share/php7 \
        && php -v \
        && php -m 


ARG swoole

##
# ---------- env settings ----------
##
ENV SWOOLE_VERSION=${swoole:-"4.4.3"} \
        #  install and remove building packages
        PHPIZE_DEPS="autoconf dpkg-dev dpkg file g++ gcc libc-dev make php7-dev php7-pear pkgconf re2c pcre-dev zlib-dev libtool automake"

# update
RUN set -ex \
        && apk update \
        # for swoole extension libaio linux-headers
        && apk add --no-cache libstdc++ openssl git bash \
        && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libaio-dev openssl-dev \
        # download
        && cd /tmp \
        && curl -SL "https://github.com/swoole/swoole-src/archive/v${SWOOLE_VERSION}.tar.gz" -o swoole.tar.gz \
        && ls -alh \
        # php extension:swoole
        && cd /tmp \
        && mkdir -p swoole \
        && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
        && ( \
        cd swoole \
        && phpize \
        && ./configure --enable-mysqlnd --enable-openssl \
        && make -s -j$(nproc) && make install \
        ) \
        && printf "extension=swoole.so\n\
        swoole.use_shortname = 'Off'\n\
        swoole.enable_coroutine = 'Off'\n\
        " >/etc/php7/conf.d/swoole.ini \
        # clear
        && php -v \
        && php -m \
        && php --ri swoole \
        # ---------- clear works ----------
        && apk del .build-deps \
        && rm -rf /var/cache/apk/* /tmp/* /usr/share/man
       
RUN printf "# composer php cli ini settings\n\
        date.timezone=PRC\n\
        memory_limit=-1\n\
        " > /etc/php7/php.ini
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /tmp
ENV COMPOSER_VERSION 1.9.0

RUN curl -SL "https://github.com/composer/composer/releases/download/${COMPOSER_VERSION}/composer.phar" -o composer.phar \
        && mv composer.phar /usr/bin/composer \
        && chmod u+x /usr/bin/composer \
        && echo -e "\033[42;37m Build Completed :).\033[0m\n"