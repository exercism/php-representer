FROM composer:latest AS builder

WORKDIR /opt/representer
COPY . /opt/representer

RUN /usr/bin/composer install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader --working-dir=/opt/representer

FROM php:8.1-cli-alpine

COPY --from=builder /opt/representer /opt/representer

ENTRYPOINT ["/opt/representer/bin/run.sh"]
