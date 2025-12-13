FROM php:8.2-apache

# 1. Gerekli araçları yükle
RUN apt-get update && apt-get install -y \
    gnupg2 curl unixodbc-dev ca-certificates apt-transport-https lsb-release

# 2. Microsoft GPG Anahtarı ve Repo
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
RUN curl https://packages.microsoft.com/config/debian/12/prod.list | tee /etc/apt/sources.list.d/mssql-release.list

# 3. Sürücüleri Kur
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18

# 4. PHP Eklentilerini Aktifleştir
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# 5. Dosyaları Kopyala ve İzinleri Ver
COPY ./src /var/www/html
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80