# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
docker_image=iredmail/mariadb:nightly
mail_user=postmaster@mail.domain.tld
mail_pwd=my-secret-password

all: appstore

clean:
	rm -rf $(build_dir)
	rm -rf node_modules

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install-deps: install-composer-deps-dev install-npm-deps-dev

install-composer-deps: composer.phar
	php composer.phar install --no-dev -o

install-composer-deps-dev: composer.phar
	php composer.phar install -o

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

dev-setup: install-composer-deps-dev install-npm-deps-dev optimize-js

start-imap-docker:
	docker pull $(docker_image)
	docker run --name="ncimaptest" -d \
	-p 993:993 \
	--hostname mail.domain.tld \
	-e HOSTNAME=mail.domain.tld \
	-e FIRST_MAIL_DOMAIN=mail.domain.tld \
	-e FIRST_MAIL_DOMAIN_ADMIN_PASSWORD=my-secret-password \
	-e MLMMJADMIN_API_TOKEN=szpUAZAH4H+jyEzH3GPcsVjOGYI1I6VohT7MLaxfZXw= \
	-e ROUNDCUBE_DES_KEY=7RPfVaqOfQUIkog68QZQxh0qxUFrK8BdwOQeVxJaVrs= \
	$(docker_image)

start-docker:
	docker pull antespi/docker-imap-devel:latest
	docker run --name="ncmailtest" -d \
	-p 25:25 \
	-p 143:143 \
	-p 993:993 \
	--hostname mail.domain.tld \
	-e MAILNAME=mail.domain.tld \
	-e MAIL_ADDRESS=user@domain.tld \
	-e MAIL_PASS=mypassword \
	antespi/docker-imap-devel:latest

start-smtp-docker:
	docker pull catatnight/postfix
	docker run --name="ncsmtptest" -d \
	-e maildomain=domain.tld \
	-e smtp_user=postmaster@mail.domain.tld:my-secret-password \
	-p 2525:25 \
	catatnight/postfix

add-imap-account:
	docker exec -it ncimaptest /opt/bin/useradd $(mail_user) $(mail_pwd)

update-composer: composer.phar
	rm -f composer.lock
	php composer.phar install --prefer-dist

appstore:
	krankerl package

