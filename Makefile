# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
docker_image=christophwurst/nextcloud-mail-test-docker
mail_user=user@domain.tld
mail_pwd=mypassword

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
	npm install --deps

optimize-js: install-npm-deps-dev
	./node_modules/webpack/bin/webpack.js --config js/webpack.prod.config.js

dev-setup: install-composer-deps-dev install-npm-deps-dev optimize-js

start-imap-docker:
	docker pull $(docker_image)
	docker run --name="ncimaptest" -d \
	-p 993:993 \
	-e POSTFIX_HOSTNAME=mail.domain.tld $(docker_image)

start-smtp-docker:
	docker pull catatnight/postfix
	docker run --name="ncsmtptest" -d \
	-e maildomain=domain.tld \
	-e smtp_user=user@domain.tld:mypassword \
	-p 2525:25 \
	catatnight/postfix

add-imap-account:
	docker exec -it ncimaptest /opt/bin/useradd $(mail_user) $(mail_pwd)

update-composer: composer.phar
	rm -f composer.lock
	php composer.phar install --prefer-dist

appstore: clean install-deps optimize-js
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=bower.json \
	--exclude=.bowerrc \
	--exclude=/build \
	--exclude=composer.json \
	--exclude=composer.lock \
	--exclude=composer.phar \
	--exclude=CONTRIBUTING.md \
	--exclude=coverage \
	--exclude=.git \
	--exclude=.gitattributes \
	--exclude=.github \
	--exclude=.gitignore \
	--exclude=Gruntfile.js \
	--exclude=.hg \
	--exclude=issue_template.md \
	--exclude=.jscsrc \
	--exclude=.jshintignore \
	--exclude=.jshintrc \
	--exclude=js/tests \
	--exclude=karma.conf.js \
	--exclude=l10n/no-php \
	--exclude=.tx \
	--exclude=Makefile \
	--exclude=nbproject \
	--exclude=/node_modules \
	--exclude=package.json \
	--exclude=.phan \
	--exclude=phpunit*xml \
	--exclude=screenshots \
	--exclude=.scrutinizer.yml \
	--exclude=tests \
	--exclude=.travis.yml \
	--exclude=vendor/bin \
	$(project_dir)/ $(sign_dir)/$(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(sign_dir)/$(app_name); \
	fi
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64; \
	fi

