FROM library/nginx:alpine as nginx

COPY ./php /var/www
COPY ./configs/nginx.conf /etc/nginx/conf.d/site.conf

RUN addgroup -S web \
    && adduser \
    --disabled-password \
    --gecos "" \
    --home /home/web \
    --ingroup web \
    --uid "1000" \
    web \
    && touch /var/run/nginx.pid \
    && chown -R web:web /var/run/nginx.pid /var/cache/nginx /var/www

WORKDIR /var/www

USER web