FROM php:7.4.16-fpm

RUN apt-get update -y
RUN apt-get install -y libmcrypt-dev
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get install unzip zip
RUN apt-get install -y git
RUN docker-php-ext-install bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install -y supervisor
COPY supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN apt-get install -y cron

ADD cron/crontab /etc/cron.d/cron
RUN chmod 0644 /etc/cron.d/cron
RUN touch /var/log/crontab.log

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

WORKDIR /var/www
