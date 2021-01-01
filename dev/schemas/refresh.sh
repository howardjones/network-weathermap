#!/bin/bash

SCHEMA=/local/weathermap-manager.v1.yaml

CLIENTS="php python go javascript powershell"
SERVERS="php-laravel php-slim4 python-flask php-symfony php-lumen"

if [ ! -d out ]; then
    mkdir out
fi

for client in $CLIENTS; do
    docker run -u "$(id -u):$(id -g)" --rm -v "${PWD}:/local" openapitools/openapi-generator-cli generate \
        -i ${SCHEMA} \
        -g $client \
        -o /local/out/client-${client}
done

for server in $SERVERS; do
    docker run -u "$(id -u):$(id -g)" --rm -v "${PWD}:/local" openapitools/openapi-generator-cli generate \
        -i ${SCHEMA} \
        -g $server \
        -o /local/out/server-${server}
done

