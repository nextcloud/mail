# Makefile for building the project

app_name=mail
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
package_name=$(app_name)

all: dist

clean:
	rm -rf $(build_dir)

dist: install-npm-deps install-bower-deps optimize-js

install-npm-deps: package.json
	npm install

install-bower-deps: bower.json install-npm-deps
	./node_modules/bower/bin/bower install

optimize-js:
	./node_modules/requirejs/bin/r.js -o build.js

update-composer:
	rm -f composer.lock
	git rm -r vendor
	composer install --prefer-dist

appstore: clean
	mkdir -p $(appstore_dir)
	tar cvzf $(appstore_dir)/$(package_name).tar.gz $(project_dir) \
	--exclude-vcs \
	--exclude=$(project_dir)/build \
	--exclude=$(project_dir)/build/artifacts \
	--exclude=$(project_dir)/js/node_modules \
	--exclude=$(project_dir)/js/.bowerrc \
	--exclude=$(project_dir)/.jshintrc \
	--exclude=$(project_dir)/.jshintignore \
	--exclude=$(project_dir)/.travis.yml \
	--exclude=$(project_dir)/.scrutinizer.yml \
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

