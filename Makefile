all: build-js prefix archive 

.PHONY: build-js
build-js:
	npm install
	npm run build

.PHONY: prefix
prefix:
	composer install --no-dev
	vendor/bin/php-scoper add-prefix --config=./config/scoper.inc.php
	cd build && composer dump-autoload

.PHONY: archive
archive:
	mv build wpstreak
	zip -r wpstreak.zip wpstreak
	rm -rf wpstreak

.PHONY: test
test:
	vendor/bin/phpunit --bootstrap=./vendor/autoload.php tests
