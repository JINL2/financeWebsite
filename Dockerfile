FROM php:8.2-apache

# Apache가 Railway의 PORT 환경변수를 사용하도록 설정
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# PHP 확장 설치
RUN docker-php-ext-install pdo pdo_mysql

# 파일 복사
COPY . /var/www/html/

# 권한 설정
RUN chown -R www-data:www-data /var/www/html

# mod_rewrite 활성화
RUN a2enmod rewrite

# Railway가 제공하는 PORT 사용
CMD sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground