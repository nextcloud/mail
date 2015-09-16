# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
package_name=$(app_name)

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

optimize-js: install-deps
	./node_modules/requirejs/bin/r.js -o build.js

dev-setup: install-composer-deps install-npm-deps-dev install-bower-deps

update-composer:
	rm -f composer.lock
	git rm -r vendor
	composer install --prefer-dist

appstore: clean install-deps optimize-js
	mkdir -p $(appstore_dir)
	tar cvzf $(appstore_dir)/$(package_name).tar.gz $(project_dir) \
	--exclude-vcs \
	--exclude=$(project_dir)/build \
	--exclude=$(project_dir)/build/artifacts \
	--exclude=$(project_dir)/node_modules \
	--exclude=$(project_dir)/.bowerrc \
	--exclude=$(project_dir)/.jscsrc \
	--exclude=$(project_dir)/.jshintrc \
	--exclude=$(project_dir)/.jshintignore \
	--exclude=$(project_dir)/.travis.yml \
	--exclude=$(project_dir)/.scrutinizer.yml \
	--exclude=$(project_dir)/build.js \
	--exclude=$(project_dir)/composer.json \
	--exclude=$(project_dir)/composer.lock \
	--exclude=$(project_dir)/composer.phar \
	--exclude=$(project_dir)/Gruntfile.js \
	--exclude=$(project_dir)/install_ubuntu.sh \
	--exclude=$(project_dir)/karma.conf.js \
	--exclude=$(project_dir)/package.json \
	--exclude=$(project_dir)/translation-extractor.php \
	--exclude=$(project_dir)/translations.js \
	--exclude=$(project_dir)/phpunit*xml \
	--exclude=$(project_dir)/Makefile \
	--exclude=$(project_dir)/tests \
	--exclude=$(project_dir)/l10n/.tx \
	--exclude=$(project_dir)/l10n/no-php \
	--exclude=$(project_dir)/vendor/bin \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/.gitattributes \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/Doxyfile \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/FOCUS \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/INSTALL* \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/NEWS \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/phpdoc.ini \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/README \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/TODO \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/VERSION \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/WHATSNEW \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/WYSIWYG \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/art \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/benchmarks \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/configdoc \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/docs \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/extras \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/maintenance \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/plugins \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/smoketests \
	--exclude=$(project_dir)/vendor/ezyang/htmlpurifier/tests 

