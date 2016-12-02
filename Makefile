# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
docker_image=christophwurst/owncloud-mail-test-docker
mail_user=user@domain.tld
mail_pwd=mypassword

all: appstore

clean:
	rm -rf $(build_dir)
	rm -rf node_modules
	rm -rf js/vendor

composer.phar:
	curl -sS https://getcomposer.org/installer | php

install-deps: install-composer-deps install-npm-deps install-bower-deps

install-composer-deps: composer.phar
	php composer.phar install

install-npm-deps:
	npm install --production

install-npm-deps-dev:
	npm install --deps

install-bower-deps: bower.json install-npm-deps
	./node_modules/bower/bin/bower install

optimize-js: install-npm-deps install-bower-deps
	./node_modules/requirejs/bin/r.js -o build/build.js

dev-setup: install-composer-deps install-npm-deps-dev install-bower-deps

start-imap-docker:
	docker pull $(docker_image)
	docker run --name="ocimaptest" -d \
	-p 2525:25 -p 587:587 -p 993:993 \
	-e POSTFIX_HOSTNAME=mail.domain.tld $(docker_image)

add-imap-account:
	docker exec -it ocimaptest /opt/bin/useradd $(mail_user) $(mail_pwd)

update-composer: composer.phar
	rm -f composer.lock
	php composer.phar install --prefer-dist

appstore: clean install-deps optimize-js
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=.git \
	--exclude=.github \
	--exclude=node_modules \
	--exclude=.bowerrc \
	--exclude=.gitattributes \
	--exclude=.gitignore \
	--exclude=.jscsrc \
	--exclude=.jshintrc \
	--exclude=.jshintignore \
	--exclude=.lgtm \
	--exclude=.travis.yml \
	--exclude=.scrutinizer.yml \
	--exclude=build \
	--exclude=bower.json \
	--exclude=CONTRIBUTING.md \
	--exclude=composer.json \
	--exclude=composer.lock \
	--exclude=composer.phar \
	--exclude=Gruntfile.js \
	--exclude=issue_template.md \
	--exclude=karma.conf.js \
	--exclude=package.json \
	--exclude=phpunit*xml \
	--exclude=Makefile \
	--exclude=tests \
	--exclude=js/tests \
	--exclude=l10n/.tx \
	--exclude=l10n/no-php \
	--exclude=vendor/bin \
	--exclude=vendor/ezyang/htmlpurifier/.gitattributes \
	--exclude=vendor/ezyang/htmlpurifier/Doxyfile \
	--exclude=vendor/ezyang/htmlpurifier/FOCUS \
	--exclude=vendor/ezyang/htmlpurifier/INSTALL* \
	--exclude=vendor/ezyang/htmlpurifier/NEWS \
	--exclude=vendor/ezyang/htmlpurifier/phpdoc.ini \
	--exclude=vendor/ezyang/htmlpurifier/README \
	--exclude=vendor/ezyang/htmlpurifier/TODO \
	--exclude=vendor/ezyang/htmlpurifier/VERSION \
	--exclude=vendor/ezyang/htmlpurifier/WHATSNEW \
	--exclude=vendor/ezyang/htmlpurifier/WYSIWYG \
	--exclude=vendor/ezyang/htmlpurifier/art \
	--exclude=vendor/ezyang/htmlpurifier/benchmarks \
	--exclude=vendor/ezyang/htmlpurifier/configdoc \
	--exclude=vendor/ezyang/htmlpurifier/docs \
	--exclude=vendor/ezyang/htmlpurifier/extras \
	--exclude=vendor/ezyang/htmlpurifier/maintenance \
	--exclude=vendor/ezyang/htmlpurifier/plugins \
	--exclude=vendor/ezyang/htmlpurifier/smoketests \
	--exclude=vendor/ezyang/htmlpurifier/tests \
	$(project_dir) $(sign_dir)
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

