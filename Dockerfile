FROM php:8.2-fpm

# Argumentos de build
ARG user=laravel
ARG uid=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar e configurar extensões PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip

# Instalar extensão MongoDB
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini \
    && docker-php-ext-enable mongodb

# Instalar extensão Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Configurar timezone
RUN ln -sf /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime

# Obter último Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário do sistema
RUN useradd -G www-data,root -u $uid -d /home/$user $user \
    && mkdir -p /home/$user/.composer \
    && chown -R $user:$user /home/$user \
    && mkdir -p /var/www \
    && chown -R $user:$user /var/www

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar composer.json
COPY --chown=$user:$user composer.json ./

# Instalar dependências do Composer
RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --no-dev \
    --ignore-platform-reqs

# Copiar configurações do PHP
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Copiar código da aplicação
COPY --chown=$user:$user . .

# Ajustar permissões
RUN chmod -R 755 storage bootstrap/cache

# Definir usuário
USER $user

# Expor porta do PHP-FPM
EXPOSE 9000

# Comando para iniciar o PHP-FPM
CMD ["php-fpm"] 