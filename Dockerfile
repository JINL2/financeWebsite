FROM php:8.2-apache

# Apache 모듈 활성화
RUN a2enmod rewrite

# PHP 확장 설치
RUN docker-php-ext-install pdo pdo_mysql

# 파일 복사
COPY . /var/www/html/

# 권한 설정
RUN chown -R www-data:www-data /var/www/html

# ServerName 설정으로 경고 제거
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Apache 설정 파일 생성
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Railway PORT 환경변수 사용
EXPOSE 80
CMD ["sh", "-c", "sed -i \"s/80/$PORT/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]