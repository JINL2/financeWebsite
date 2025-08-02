FROM php:8.2-apache

# 기본 DocumentRoot 설정
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Apache 모듈 활성화
RUN a2enmod rewrite

# 파일 복사
COPY . /var/www/html/

# 권한 설정
RUN chown -R www-data:www-data /var/www/html

# Railway 포트 설정
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -i 's/:80/:${PORT}/' /etc/apache2/sites-enabled/000-default.conf

EXPOSE ${PORT}

CMD ["sh", "-c", "apache2-foreground"]