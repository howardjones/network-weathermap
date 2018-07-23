#!/usr/bin/env bash

curl --user ${CIRCLE_TOKEN}: \
    --request POST \
    --form revision=03f4428c6aa44f23c577e2e1f66224b5a4e54a6f\
    --form config=@config.yml \
    --form notify=false \
        https://circleci.com/api/v1.1/project/github/howardjones/network-weathermap/tree/master
