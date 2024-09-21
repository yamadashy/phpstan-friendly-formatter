<div align="center">
  <h1>PHPStan Friendly Formatter</h1>
  A simple error formatter for <a href="https://phpstan.org/">PHPStan</a> that display code frame.
</div>

<img src="./docs/example.png" alt="PHPStan Friendly Formatter Example" width="100%">

<p align="center">
  <a href="https://packagist.org/packages/yamadashy/phpstan-friendly-formatter"><img src="https://shields.io/packagist/dt/yamadashy/phpstan-friendly-formatter" alt="Downloads"></a>
  <a href="https://github.com/yamadashy/phpstan-friendly-formatter/actions"><img src="https://img.shields.io/github/actions/workflow/status/yamadashy/phpstan-friendly-formatter/tests.yml?branch=main&label=tests&logo=github" alt="Test Status"></a>
  <a href="https://packagist.org/packages/yamadashy/phpstan-friendly-formatter"><img src="https://poser.pugx.org/yamadashy/phpstan-friendly-formatter/v/stable.svg" alt="Latest Version"></a>
  <a href="https://github.com/yamadashy/phpstan-friendly-formatter/blob/master/LICENSE.md"><img src="https://poser.pugx.org/yamadashy/phpstan-friendly-formatter/license.svg" alt="License"></a>
</p>

---

# Motivation
The default phpstan formatter displays the file path, line number, and error, but this does not allow us to instantly determine what is actually wrong.

This package aims to complement the default formatter by displaying the corresponding source code alongside the error information, making it easier to locate and address issues more 

# Getting Started

1. You may use [Composer](https://getcomposer.org/) to install this package as a development dependency.
```shell
composer require --dev yamadashy/phpstan-friendly-formatter
```

2. Register error formatter into your `phpstan.neon` or `phpstan.neon.dist`:
```neon
includes:
    - ./vendor/yamadashy/phpstan-friendly-formatter/extension.neon
```

3. Finaly, use phpstan console command with `--error-format` option:
```shell
./vendor/bin/phpstan analyze --error-format friendly
```

## Optional
If you want to make it simpler, setting `scripts` in `composer.json` as follows:

```json
{
    "scripts": {
        "analyze": "phpstan analyze --error-format friendly"
    }
}
```

You can run a short command like this:
```shell
composer analyze
```


# Config
You can customize in your `phpstan.neon`:
```neon
parameters:
    friendly:
        # default is 3
        lineBefore: 3
        lineAfter: 3
        # default is null
        editorUrl: 'phpstorm://open?file=%%file%%&line=%%line%%'
```

- `lineBefore` ... Number of lines to display before error line
- `lineAfter` ... Number of lines to display after error line
- `editorUrl` ... URL with placeholders like [table formatter config](URL for editor like table formatter)


# Example
When you actually introduce it in GitHub Actions, it will be displayed as follows.

![](./docs/github-actions.png)
https://github.com/yamadashy/laravel-blade-minify-directive/actions/runs/4714024802/jobs/8360104870

# License
Distributed under the [MIT license](LICENSE.md).
