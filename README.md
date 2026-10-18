<p align="center">
    <img width="1216" height="415" alt="tinymvc-spark" src="https://github.com/user-attachments/assets/c167c5d0-f946-440a-b5fa-012a63cd7910" />
</p>

<p align="center">
<a href="https://github.com/tinymvc/tinycore/releases"><img src="https://img.shields.io/github/v/release/tinymvc/tinycore?style=flat-square&amp;label=TinyCore" alt="TinyCore Version"></a>
<a href="https://github.com/tinymvc/tinycore/stargazers"><img src="https://img.shields.io/github/stars/tinymvc/tinycore?style=flat-square&amp;label=TinyCore%20stars" alt="TinyCore GitHub Stars"></a>
<a href="https://packagist.org/packages/tinymvc/tinycore"><img src="https://img.shields.io/packagist/dt/tinymvc/tinycore?label=TinyCore%20downloads" alt="TinyCore Downloads"></a>
<a href="https://packagist.org/packages/tinymvc/tinycore"><img src="https://img.shields.io/packagist/l/tinymvc/tinycore?label=TinyCore%20license" alt="TinyCore License"></a>
</p>

<hr/>

**TinyMVC / Spark** (`tinymvc/spark`) is a custom minimalist MVC PHP framework for modern web applications.

This repository, **`tinymvc/skeleton`**, is its application starter. It provides the project structure, configuration, routes, and bootstrap files for a new application. Composer installs [TinyCore](https://github.com/tinymvc/tinycore) (`tinymvc/tinycore`), the framework's core engine, under `vendor/tinymvc/tinycore`.

## Framework Features

TinyMVC / Spark provides these features through its TinyCore package:

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

Create a new application from the Skeleton starter with Composer:

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

## AI development

Use the [TinyMVC Spark skill](.agents/skills/tinymvc-spark/SKILL.md) for Spark-specific implementation and testing. Its `references/` folder contains the complete topic-based framework guidance and application examples. Copy the entire skill folder when adopting it in another application; [AGENTS.md](AGENTS.md) provides the project entry point.

## Contributing

We welcome contributions! Please:

1. ⭐ Star the repository
2. 🐞 Report starter issues [here](https://github.com/tinymvc/skeleton/issues)
3. 🛠 Submit PRs following our [contribution guidelines](https://tinymvc.github.io/#/docs/contributing)

For core framework issues or changes, use the [TinyCore repository](https://github.com/tinymvc/tinycore).

## License

This application starter is open-source software licensed under the [MIT License](LICENSE).
