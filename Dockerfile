FROM php:8.2-fpm

# Installations système
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libpq-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libpq-dev \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Configure Nginx
RUN echo 'server {\
    listen 80;\
    server_name localhost;\
    root /var/www/public;\
    index index.php index.html index.htm;\
    \
    location / {\
        try_files $uri $uri/ /index.php?$query_string;\
    }\
    \
    location ~ \.php$ {\
        fastcgi_pass 127.0.0.1:9000;\
        fastcgi_index index.php;\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\
        include fastcgi_params;\
    }\
    \
    location ~ /\.ht {\
        deny all;\
    }\
}' > /etc/nginx/sites-available/default

# Configure Supervisor
RUN echo '[supervisord]\
nodaemon=true\
\
[program:nginx]\
command=/usr/sbin/nginx -g "daemon off;"\
autostart=true\
autorestart=true\
\
[program:php-fpm]\
command=/usr/local/sbin/php-fpm\
autostart=true\
autorestart=true\
' > /etc/supervisor/conf.d/supervisord.conf

# Expose port 80 for web traffic
EXPOSE 80

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
