FROM php:7.4.2-apache-buster

RUN apt-get update && apt-get install -y \
    dialog \
    zip \
    git \
    vim \
    apt-transport-https \
    lsb-release \
    ca-certificates \
    wget \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get -y update && \
    apt-get -y --no-install-recommends install vim wget \
dialog \
libsqlite3-dev \
libsqlite3-0 && \
    apt-get -y --no-install-recommends install default-mysql-client \
zlib1g-dev \
libzip-dev \
libicu-dev && \
    apt-get -y --no-install-recommends install --fix-missing apt-utils \
build-essential \
git \
curl \
libonig-dev && \
    apt-get -y --no-install-recommends install --fix-missing libcurl4 \
libcurl4-openssl-dev \
zip \
openssl && \
    rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install pdo_mysql && \
    docker-php-ext-install mysqli && \
    docker-php-ext-install curl && \
    docker-php-ext-install json && \
    docker-php-ext-install zip && \
    docker-php-ext-install mbstring

# COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www

COPY ./docker .

RUN composer require cottagelabs/coar-notifications

# RUN php vendor/bin/doctrine orm:schema-tool:create