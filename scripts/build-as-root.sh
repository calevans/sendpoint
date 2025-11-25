#!/usr/bin/env bash

export PATH="$PATH:/app/vendor/bin:/app/.composer/vendor/bin"

apt-get update \
	&& apt-get install -y less vim nano jq curl nodejs npm git