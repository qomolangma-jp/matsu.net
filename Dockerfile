FROM php:8.2-apache

# 必要なパッケージとPHP拡張機能のインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP設定（タイムアウト対応）
RUN echo 'max_execution_time = 300' >> /usr/local/etc/php/php.ini \
    && echo 'max_input_time = 300' >> /usr/local/etc/php/php.ini \
    && echo 'memory_limit = 512M' >> /usr/local/etc/php/php.ini \
    && echo 'upload_max_filesize = 100M' >> /usr/local/etc/php/php.ini \
    && echo 'post_max_size = 100M' >> /usr/local/etc/php/php.ini

# Apache設定
RUN a2enmod rewrite

# DocumentRootをLaravelのpublicディレクトリに設定
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# .htaccess有効化設定
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf && \
    a2enconf laravel

# 作業ディレクトリ
WORKDIR /var/www/html

# 権限設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# ポート80を公開
EXPOSE 80
