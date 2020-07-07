# Dhii - Php Template

[![Build Status](https://travis-ci.org/dhii/php-template.svg?branch=develop)](https://travis-ci.org/dhii/php-template)
[![Code Climate](https://codeclimate.com/github/Dhii/php-template/badges/gpa.svg)](https://codeclimate.com/github/Dhii/php-template)
[![Test Coverage](https://codeclimate.com/github/Dhii/php-template/badges/coverage.svg)](https://codeclimate.com/github/Dhii/php-template/coverage)
[![Latest Stable Version](https://poser.pugx.org/dhii/php-template/version)](https://packagist.org/packages/dhii/php-template)

A concrete PHP (PHTML) template implementation.

## Details
This is an implementation of the [Dhii output renderer standard][dhii/output-renderer-interface];
specifically, the template. It allows consuming file-based PHP templates using an abstract interface.
Now it's possible to outsource your rendering to standardized, stateless PHP template files,
and use them just like any other template, without knowing where the content comes from.

Because this implementation is based on PHP files, it was decided to separate the concern
of rendering a template from the concern of evaluating a PHP file, because the latter
is useful on its own, and because it would make the template implementation thinner
and cleaner.

### Usage
Below examples explain how a template factory could be configured, and used to produce a
standards-compliant template. Then that template is rendered with context. Please note the following:

1. The file at path `template.php` is used to produce the output.
2. Context members are retrieved by `$c('key')`. 
3. It is possible to use the `uc` function with `$f('uc')`.
4. The default context member `time` is present in the template, even though it was not explicitly supplied
at render time.

#### Configuration, usually in a service definition
```php
use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactory;
use Dhii\Output\Template\PhpTemplate\FilePathTemplateFactory;
use Dhii\Output\Template\PathTemplateFactoryInterface;

function (): PathTemplateFactoryInterface {
    return new FilePathTemplateFactory(
        new FilePhpEvaluatorFactory(),
        [ // This will be available by default in all contexts of all templates made by this factory
            'time' => time(), // Let's assume it's 1586364371
        ],
        [ // This will be available by default in all templates made by this factory
            'uc' => function (string $string) {
                return strtoupper($string);
            },
        ]
    );
};
```

#### Consumption, usually somewhere in controller-level code  
```php
use Dhii\Output\Template\PathTemplateFactoryInterface;
use Dhii\Output\Template\PhpTemplate\FilePathTemplateFactory;

/* @var $fileTemplateFactory FilePathTemplateFactory */
(function (PathTemplateFactoryInterface $factory) {
    $template = $factory->fromPath('template.php');
    echo $template->render([
        'username' => 'jcdenton',
        'password' => 'bionicman',
        'status' => 'defected',
    ]);
})($fileTemplateFactory); // This is the factory created by above configuration
```

#### template.php
```php
/* @var $c callable */
/* @var $f callable */
?>
<span class="current-time"><?= $c('time') ?><span />
<span class="username"><?= $c('username') ?></span><br />
<span class="password"><?= $c('password') ?></span><br />
<span class="status"><?= $f('uc', $c('status')) ?></span>
```

#### Resulting output
```html
<span class="current-time">1586364371<span />
<span class="username">jcdenton</span><br />
<span class="password">bionicman</span><br />
<span class="status">DEFECTED</span>
```


[Dhii]: https://github.com/Dhii/dhii
[dhii/output-renderer-interface]: https://travis-ci.org/Dhii/output-renderer-interface
