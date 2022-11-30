# FROM phpswoole/swoole:4.8-php7.4-alpine
FROM phpswoole/swoole:latest

LABEL maintainer="Swoole Jobs <pei.greet@qq.com>" version="1.1" license="MIT"



RUN docker-php-ext-install amqp

RUN printf "# php cli ini settings\n\
        date.timezone=PRC\n\
        memory_limit=-1\n\
        " >> /usr/local/etc/php/conf.d/php.ini
