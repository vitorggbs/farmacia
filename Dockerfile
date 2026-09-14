FROM php:8.2-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html/

RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html
RUN echo "versao-cloud-2" > /var/www/html/versao.txt
EXPOSE 8080

CMD ["apache2-foreground"]
