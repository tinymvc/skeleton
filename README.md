# TinyMVC Framework

[![Latest Version](https://img.shields.io/github/v/release/tinymvc/tinymvc?style=flat-square)](https://github.com/tinymvc/tinymvc/releases)
[![License](https://img.shields.io/github/license/tinymvc/tinymvc?style=flat-square)](https://github.com/tinymvc/tinymvc/blob/main/LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/tinymvc/tinymvc?style=flat-square)](https://github.com/tinymvc/tinymvc/stargazers)
[![Open Issues](https://img.shields.io/github/issues-raw/tinymvc/tinymvc?style=flat-square)](https://github.com/tinymvc/issues)

**A minimalist MVC PHP framework for modern web artisans**  
Lightning-fast · Elegant Syntax · Developer Friendly

## Key Features

- **MVC Architecture** - Clean separation of concerns
- **Lightning Fast** - Minimal overhead, maximum performance
- **Built-in ORM** - Simple ActiveRecord implementation
- **Routing System** - RESTful routing with parameter binding
- **Dependency Injection** - Powerful IoC container
- **Template Engine** - PHP-based views with layout support
- **Security First** - CSRF protection, input sanitization
- **CLI Tools** - Built-in development server and generator commands

## Installation

Create a new project with Composer:

```bash
composer create-project tinymvc/tinymvc myapp

```

Start development server:

```bash
cd myapp

php spark serve

```

**🌐 Production Note:** Configure your web server to point to the */public* directory.

## Quick Start

- Create your first route in *routes/web.php*:
    ```php
    use App\Http\Controllers\WelcomeController;

    router()->get('/hello/{name}', [WelcomeController::class, 'index']);
    ```
- Create a controller:
    ```php
    <?php
    namespace App\Http\Controllers;

    class WelcomeController
    {
        public function index(string $name)
        {
            return response("Hello, $name!");
        }
    }
    ```

## Documentation

Full documentation is available at: [https://tinymvc.github.io](https://tinymvc.github.io)

[![Documentation](https://img.shields.io/badge/docs-online-8A2BE2?style=for-the-badge&logo=gitbook)](https://tinymvc.github.io)

## Contributing

We welcome contributions! Please:

1. ⭐ Star the repository
2. 🐞 Report issues [here](https://github.com/tinymvc/issues)
3. 🛠 Submit PRs following our [contribution guidelines](https://tinymvc.github.io/contributing)

## License

TinyMVC is open-source software licensed under the [MIT License](https://github.com/tinymvc/tinymvc/blob/main/LICENSE).