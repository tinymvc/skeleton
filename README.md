# TinyMVC Framework

[![Latest Version](https://img.shields.io/github/v/release/tinymvc/skeleton?style=flat-square)](https://github.com/tinymvc/skeleton/releases)
[![License](https://img.shields.io/github/license/tinymvc/skeleton?style=flat-square)](https://github.com/tinymvc/skeleton/blob/main/LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/tinymvc/skeleton?style=flat-square)](https://github.com/tinymvc/skeleton/stargazers)
[![Open Issues](https://img.shields.io/github/issues-raw/tinymvc/skeleton?style=flat-square)](https://github.com/tinymvc/issues)

**A minimalist MVC PHP framework for modern web artisans**  
Lightning-fast · Elegant Syntax · Developer Friendly

## Key Features

- **MVC Architecture** - Clean separation of concerns
- **Lightning Fast** - Minimal overhead, maximum performance
- **Built-in ORM** - Simple ActiveRecord implementation
- **Routing System** - RESTful routing with parameter binding
- **Dependency Injection** - Powerful IoC container
- **Template Engine** - Blade-Like Lightweight, Super Fast Template Engine
- **Security First** - CSRF protection, Throttling, input sanitization & validation
- **Queue** - Minimal Queue Jobs up-to 4 workers.
- **Cache** - Fast & Lightweight Caching System
- **CLI Tools** - Built-in development server and generator commands

## Installation

Create a new project with Composer:

```bash
composer create-project tinymvc/skeleton myapp

```

Start development server:

```bash
cd myapp

php spark serve

```

**Production Note:** Configure your web server to point to the */public* directory.

## Quick Start
```php
<?php

use Spark\Facades\Route;

Route::get('welcome/{name}', function($name) {
    return "Welcome, $name!";
});

```

## Documentation

Full documentation is available at: [https://tinymvc.github.io](https://tinymvc.github.io)

[![Documentation](https://img.shields.io/badge/docs-online-8A2BE2?style=for-the-badge&logo=gitbook)](https://tinymvc.github.io)

## Contributing

We welcome contributions! Please:

1. ⭐ Star the repository
2. 🐞 Report issues [here](https://github.com/tinymvc/issues)
3. 🛠 Submit PRs following our [contribution guidelines](https://tinymvc.github.io/contribution)

## License

TinyMVC is open-source software licensed under the [MIT License](https://github.com/tinymvc/skeleton/blob/main/LICENSE).
