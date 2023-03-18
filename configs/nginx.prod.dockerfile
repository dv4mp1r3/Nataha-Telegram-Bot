FROM library/nginx:alpine as nginx

RUN addgroup -S web \
    && adduser \
    --disabled-password \
    --gecos "" \
    --home /home/web \
    --ingroup web \
    --uid "1000" \
    web \
    && touch /var/run/nginx.pid \
    && chown -R web:web /var/run/nginx.pid /var/cache/nginx

COPY ./php /var/www
COPY ./configs/nginx.conf /etc/nginx/conf.d/site.conf

USER web
