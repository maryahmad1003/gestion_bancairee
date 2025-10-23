FROM php:8.2-fpm

# Install system dependencies
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
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --ignore-platform-reqs --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Configure Nginx
RUN rm -f /etc/nginx/sites-enabled/default
RUN echo 'server {\
    listen 80 default_server;\
    listen [::]:80 default_server;\
    root /var/www/public;\
    index index.php index.html index.htm;\
    server_name _;\
    \
    location / {\
        try_files $uri $uri/ /index.php?$query_string;\
    }\
    \
    location ~ \.php$ {\
        include fastcgi_params;\
        fastcgi_pass 127.0.0.1:9000;\
        fastcgi_index index.php;\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\
        fastcgi_param PATH_INFO $fastcgi_path_info;\
    }\
    \
    location ~ /\.ht {\
        deny all;\
    }\
    \
    error_log /var/log/nginx/error.log;\
    access_log /var/log/nginx/access.log;\
}' > /etc/nginx/sites-available/default

RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Configure PHP-FPM
RUN mkdir -p /run/php /etc/php/8.2/fpm/pool.d && \
    echo '[www]\n\
listen = 127.0.0.1:9000\n\
user = www-data\n\
group = www-data\n\
pm = dynamic\n\
pm.max_children = 5\n\
pm.start_servers = 2\n\
pm.min_spare_servers = 1\n\
pm.max_spare_servers = 3\n\
' > /etc/php/8.2/fpm/pool.d/www.conf

# Configure Supervisor
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
RUN echo '[supervisord]\
nodaemon=true\
user=root\
\
[program:nginx]\
command=/usr/sbin/nginx -g "daemon off;"\
directory=/var/www\
autostart=true\
autorestart=true\
stdout_logfile=/dev/stdout\
stdout_logfile_maxbytes=0\
stderr_logfile=/dev/stderr\
stderr_logfile_maxbytes=0\
\
[program:php-fpm]\
command=/usr/local/sbin/php-fpm\
directory=/var/www\
autostart=true\
autorestart=true\
stdout_logfile=/dev/stdout\
stdout_logfile_maxbytes=0\
stderr_logfile=/dev/stderr\
stderr_logfile_maxbytes=0\
' > /etc/supervisor/conf.d/supervisord.conf

# Create log directories
RUN mkdir -p /var/log/nginx /var/log/supervisor

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
