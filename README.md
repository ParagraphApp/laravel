# laravel
Paragraph official Laravel package 

## Installation

The easiest way to install Paragraph Laravel package is by using Composer:

```bash
$ composer require paragraph/laravel
```

It's almost ready, just copy & paste your Paragraph project ID and API key in your .env file:

```bash
PARAGRAPH_PROJECT_ID=XXX
PARAGRAPH_API_KEY=YYY
```

## Commands

To download the latest texts run:

```bash
$ php artisan paragraph:download-texts
```

To parse all language files and Blade templates so that existing texts can be discovered, run:

```bash
$ php artisan paragraph:init
```

## Creating an account

Sign up for a free account on https://paragraph.ph

## Tutorials

Detailed tutorials explaining how to localize Laravel application are available in our blog https://medium.com/@paragraph-dev
