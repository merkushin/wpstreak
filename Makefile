PLUGIN    := wpstreak
BUILD_DIR := build/$(PLUGIN)
ZIP       := $(PLUGIN).zip
WP        ?= wp

POT_ARGS := --slug=$(PLUGIN) --domain=$(PLUGIN) --include=wpstreak.php,src --exclude=build,vendor,node_modules,tests \
	--headers='{"Report-Msgid-Bugs-To":"https://github.com/merkushin/wpstreak/issues"}'

# Files and directories that ship in the plugin zip.
DIST_FILES := wpstreak.php uninstall.php src languages LICENSE

.PHONY: all install build-js test lint lint-fix i18n i18n-check i18n-compile dist clean

all: dist

install:
	composer install
	npm ci

build-js:
	npm run build

test:
	vendor/bin/phpunit

# WordPress Coding Standards and PHP 7.4+ compatibility, see phpcs.xml.dist.
lint:
	vendor/bin/phpcs

lint-fix:
	vendor/bin/phpcbf

# Regenerates languages/wpstreak.pot from the sources and merges it into every .po file.
# Run after changing translatable strings, then translate the new entries.
i18n:
	$(WP) i18n make-pot . languages/$(PLUGIN).pot $(POT_ARGS)
	$(WP) i18n update-po languages/$(PLUGIN).pot languages

# Fails when languages/wpstreak.pot is out of date with the sources (ignores the creation date).
i18n-check:
	mkdir -p build
	$(WP) i18n make-pot . build/$(PLUGIN).pot $(POT_ARGS)
	grep -v '^"POT-Creation-Date:' languages/$(PLUGIN).pot > build/expected.pot
	grep -v '^"POT-Creation-Date:' build/$(PLUGIN).pot > build/actual.pot
	diff -u build/expected.pot build/actual.pot || (echo "languages/$(PLUGIN).pot is out of date, run 'make i18n'." && exit 1)

# Compiles .po files into the .mo and .l10n.php files WordPress loads.
i18n-compile:
	$(WP) i18n make-mo languages
	$(WP) i18n make-php languages

# Builds a release zip in a separate directory, so the working copy is never modified:
# copies the plugin files, compiles translations, installs runtime dependencies,
# prefixes them with wp-scoper into vendor-prefixed/ and drops vendor/ and the Composer files.
dist: clean install build-js
	mkdir -p $(BUILD_DIR)/assets
	cp -R $(DIST_FILES) composer.json composer.lock $(BUILD_DIR)/
	cp -R assets/dist $(BUILD_DIR)/assets/
	$(WP) i18n make-mo $(BUILD_DIR)/languages
	$(WP) i18n make-php $(BUILD_DIR)/languages
	composer install --working-dir=$(BUILD_DIR) --no-dev --no-plugins --no-scripts --no-autoloader --no-interaction --quiet
	vendor/bin/wp-scoper $(BUILD_DIR)
	rm -rf $(BUILD_DIR)/vendor $(BUILD_DIR)/composer.json $(BUILD_DIR)/composer.lock
	cd build && zip -rq ../$(ZIP) $(PLUGIN)

clean:
	rm -rf build assets/dist $(ZIP) languages/*.mo languages/*.l10n.php
