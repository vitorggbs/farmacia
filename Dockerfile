FROM php:8.2-apache

# mysqli: banco MySQL/Cloud SQL
# curl: usado pelo agente IA para autenticar no Google Cloud e chamar o Vertex AI
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install mysqli curl \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html
RUN echo "versao-cloud-ai-1" > /var/www/html/versao.txt

EXPOSE 8080
CMD ["apache2-foreground"]
