<p align="center">
    <img width="1216" height="415" alt="tinymvc-spark" src="https://github.com/user-attachments/assets/c167c5d0-f946-440a-b5fa-012a63cd7910" />
</p>

<p align="center">
<a href="https://github.com/tinymvc/tinycore/releases"><img src="https://img.shields.io/github/v/release/tinymvc/tinycore?style=flat-square" alt="Latest Version"></a>
<a href="https://github.com/tinymvc/tinycore/stargazers"><img src="https://img.shields.io/github/stars/tinymvc/tinycore?style=flat-square" alt="GitHub Stars"></a>
<a href="https://packagist.org/packages/tinymvc/tinycore"><img src="https://img.shields.io/packagist/dt/tinymvc/tinycore" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/tinymvc/tinycore"><img src="https://img.shields.io/packagist/l/tinymvc/tinycore" alt="License"></a>
</p>

<hr/>

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
2. 🐞 Report issues [here](https://github.com/tinycore/issues)
3. 🛠 Submit PRs following our [contribution guidelines](https://tinymvc.github.io/#/docs/contributing)

## License

TinyMVC is open-source software licensed under the [MIT License](https://github.com/tinymvc/tinycore/blob/main/LICENSE).