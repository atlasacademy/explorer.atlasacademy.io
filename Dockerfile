FROM webdevops/php-nginx:8.0

ENV WEB_DOCUMENT_ROOT='/app/public' \
    APP_DEBUG='false' \
    APP_ENV='production' \
    APP_USERNAME='' \
    APP_PASSWORD='' \
    DB_HOST='db' \
    DB_DATABASE='explorer' \
    DB_USERNAME='root' \
    DB_PASSWORD='password' \
    QUEUE_CONNECTION='database' \
    REFRESH_KEY='' \
    S3_KEY='' \
    S3_SECRET='' \
    PUBLIC_BUCKET='' \
    PUBLIC_BUCKET_URL=''

COPY . /app
COPY ./supervisor.conf /opt/docker/etc/supervisor.d/laravel.conf
COPY ./setup.sh /opt/docker/provision/entrypoint.d/99-setup.sh

RUN cd /app \
 && composer install --no-dev \
 && chown -R 1000:1000 /app
