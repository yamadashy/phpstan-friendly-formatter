FROM mcr.microsoft.com/vscode/devcontainers/php:8.2

# PHP memory limit
RUN echo "memory_limit=768M" > /usr/local/etc/php/php.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
