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

FROM php:8.4.27-cli-alpine3.23@sha256:05aa2edde7c3f828fef15b2508edfbac334dda7e1070de56d86a8b2c40b116a6

COPY --from=builder /opt/representer /opt/representer

ENTRYPOINT ["/opt/representer/bin/run.sh"]
