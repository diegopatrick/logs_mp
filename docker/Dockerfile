FROM php:8.2-fpm

# Instalar dependências
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev

# Instalar extensão MongoDB
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Instalar outras extensões PHP
RUN pecl install redis \
    && docker-php-ext-enable redis mongodb \
    && docker-php-ext-install zip pdo pdo_mysql bcmath

# Verificar extensões instaladas
RUN php -m | grep -i mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar diretório de trabalho
WORKDIR /var/www

# Copiar arquivos do composer primeiro para aproveitar o cache
COPY composer.json composer.lock ./

# Instalar dependências ignorando requisitos de plataforma inicialmente
RUN composer install --no-interaction --no-scripts --ignore-platform-reqs

# Copiar o resto dos arquivos do projeto
COPY . .

# Ajustar permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/* 