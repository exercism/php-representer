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

FROM php:8.4.21-cli-alpine3.23@sha256:4f4fc56fe4ba7b7d241c371eda011b27ca4f3b25bf2a37956ee06e966777d696

COPY --from=builder /opt/representer /opt/representer

ENTRYPOINT ["/opt/representer/bin/run.sh"]
