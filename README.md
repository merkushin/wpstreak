## WP Streak

Adds a panel and shows your current writing streak.

Built on [merkushin/wpal](https://github.com/merkushin/wpal).

### Development

Requirements: PHP 7.4+, Composer 2, Node.js 20.9+.

```bash
make install   # composer install && npm ci
make build-js  # build assets into assets/dist
make test      # run PHPUnit
```

In a development checkout the plugin loads `vendor/autoload.php`, so it can be symlinked into a local WordPress install as-is.

### Building a release

```bash
make dist
```

This creates `wpstreak.zip`. The build happens in `build/wpstreak`, so the working copy is left untouched:

1. Plugin files and built assets are copied to `build/wpstreak`.
2. Runtime dependencies are installed there and prefixed with [wp-scoper](https://github.com/veronalabs/wp-scoper) into `vendor-prefixed/` (namespace `Merkushin\Wpstreak\Vendor`), and the `use` statements in `src/` are rewritten to match.
3. `vendor/` and the Composer files are removed and the directory is zipped.

`vendor-prefixed/` is never committed. The wp-scoper Composer plugin is disabled in `composer.json` (`allow-plugins`) so it doesn't rewrite the sources on `composer install`; it only runs from `make dist`.
