FROM library/nginx:1.25.4-alpine3.18-perl as nginx

RUN addgroup -S web \
    && adduser \
    --disabled-password \
    --gecos "" \
    --home /home/web \
    --ingroup web \
    --uid "1000" \
    web \
    && touch /var/run/nginx.pid \
    && mkdir /var/www \
    && chown -R web:web /var/run/nginx.pid /var/cache/nginx /etc/nginx/conf.d

COPY ./configs/nginx.conf /etc/nginx/templates/site.conf.template
USER web
