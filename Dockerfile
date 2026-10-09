FROM composer:latest AS builder

WORKDIR /opt/representer
COPY . /opt/representer

RUN /usr/bin/composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --classmap-authoritative \
    --working-dir=/opt/representer

FROM php:8.5.11-cli-alpine3.23@sha256:1e608501594039b39768dfee78edb47c61599606d5b81ed133937363c61f5703

COPY --from=builder /opt/representer /opt/representer

ENTRYPOINT ["/opt/representer/bin/run.sh"]
