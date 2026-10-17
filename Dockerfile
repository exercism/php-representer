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

FROM php:8.4.26-cli-alpine3.23@sha256:da7a7a945a493ac9c015f327231afd820bbe57fc6ae22f4f520a79fbd635ba39

COPY --from=builder /opt/representer /opt/representer

ENTRYPOINT ["/opt/representer/bin/run.sh"]
