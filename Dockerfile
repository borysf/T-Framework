FROM alpine:3.16

ARG target="development"

WORKDIR /var/www/html

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/v3.16/community/ --allow-untrusted \
  curl \
  nginx \
  php81 \
  php81-ctype \
  php81-curl \
  php81-dom \
  php81-fpm \
  php81-json \
  php81-mbstring \
  php81-mysqli \
  php81-opcache \
  php81-openssl \
  php81-phar \
  php81-session \
  php81-xml \
  php81-xmlreader \
  php81-xmlwriter \
  php81-zlib \
  php81-pdo \
  php81-pdo_mysql \
  php81-tokenizer \
  php81-dev \
  php81-pear \
  php81-fileinfo \
  php81-tidy \
  php81-sodium \
  supervisor \
  && apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted \
  php81-pecl-protobuf \
  php81-pecl-grpc

COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/fpm-pool.conf /etc/php81/php-fpm.d/www.conf
COPY nginx/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# copy environment-specific configuration for nginx
COPY config/${target}/nginx/variables.conf /etc/nginx/variables.conf

# copy website files
# COPY --chown=nobody www /var/www/html

RUN chown -R nobody.nobody /var/www/html /run /var/lib/nginx /var/log/nginx

USER nobody

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
