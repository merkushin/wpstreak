PLUGIN    := wpstreak
BUILD_DIR := build/$(PLUGIN)
ZIP       := $(PLUGIN).zip

# Files and directories that ship in the plugin zip.
DIST_FILES := wpstreak.php uninstall.php src LICENSE

.PHONY: all install build-js test dist clean

all: dist

install:
	composer install
	npm ci

build-js:
	npm run build

test:
	vendor/bin/phpunit

# Builds a release zip in a separate directory, so the working copy is never modified:
# copies the plugin files, installs runtime dependencies, prefixes them with wp-scoper
# into vendor-prefixed/ and drops vendor/ and the Composer files.
dist: clean install build-js
	mkdir -p $(BUILD_DIR)/assets
	cp -R $(DIST_FILES) composer.json composer.lock $(BUILD_DIR)/
	cp -R assets/dist $(BUILD_DIR)/assets/
	composer install --working-dir=$(BUILD_DIR) --no-dev --no-plugins --no-scripts --no-autoloader --no-interaction --quiet
	vendor/bin/wp-scoper $(BUILD_DIR)
	rm -rf $(BUILD_DIR)/vendor $(BUILD_DIR)/composer.json $(BUILD_DIR)/composer.lock
	cd build && zip -rq ../$(ZIP) $(PLUGIN)

clean:
	rm -rf build assets/dist $(ZIP)
