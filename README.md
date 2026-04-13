# Spectra Blocks

A fresh, standalone WordPress Gutenberg block plugin built on the [WordPress Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) (V3 architecture).

**WordPress.org:** [wordpress.org/plugins/spectra-blocks](https://wordpress.org/plugins/spectra-blocks/)

---

## Requirements

| Dependency | Minimum |
|------------|---------|
| PHP | 8.1 |
| WordPress | 6.6 |
| Node.js | 20+ |
| Composer | 2.x |

---

## Development Setup

```bash
# 1. Clone the repository
git clone git@github.com:brainstormforce/wp-spectra-blocks.git
cd spectra-blocks

# 2. Install PHP dependencies (requires SSH access to BSF private repos)
composer install

# 3. Install JS dependencies and build
npm install
npm run build

# 4. Build admin dashboard
cd admin && npm install && npm run build && cd ..
```

## Available Scripts

| Command | Description |
|---------|-------------|
| `npm run build` | Production build (blocks + extensions) |
| `npm run build:fresh` | Clean build (clears build/ first) |
| `npm run start` | Watch mode for development |
| `npm run lint:js` | Lint JavaScript |
| `npm run lint:css` | Lint CSS/SCSS |
| `npm run zip` | Full build + create release zip |

## Creating a Release Zip

```bash
npm run zip
```

This runs the full build pipeline and produces `spectra-blocks.x.x.x.zip` ready for WordPress.org upload.

> **Note:** `composer install` requires SSH access to BSF private GitHub repositories. The `lib/` directory is populated by Composer and is not committed to this repo.

---

## Block Prefix

All blocks use the `spectra/` namespace:

```
spectra/container
spectra/accordion
spectra/tabs
spectra/countdown
spectra/counter
spectra/slider
spectra/modal
spectra/popup-builder
spectra/list
spectra/buttons
spectra/google-map
spectra/icons
spectra/separator
...
```

---

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html) © [Brainstorm Force](https://www.brainstormforce.com)
