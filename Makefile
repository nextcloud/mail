# SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates

all: appstore

clean:
	rm -rf $(build_dir)
	rm -rf node_modules

install-deps: install-composer-deps-dev install-npm-deps-dev

install-composer-deps:
	composer install --no-dev -o

install-composer-deps-dev:
	composer install -o

install-npm-deps:
	npm install --production

install-npm-deps-dev:
	npm install

optimize-js: install-npm-deps-dev
	npm run build

build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

dev-setup: install-composer-deps-dev install-npm-deps-dev build-js

start-docker:
	docker pull ghcr.io/christophwurst/docker-imap-devel
	docker run --name="ncmailtest" -d \
	-p 25:25 \
	-p 143:143 \
	-p 993:993 \
	-p 4190:4190 \
	--hostname mail.domain.tld \
	-e MAILNAME=mail.domain.tld \
	-e MAIL_ADDRESS=user@domain.tld \
	-e MAIL_PASS=mypassword \
	ghcr.io/christophwurst/docker-imap-devel

appstore:
	krankerl package

