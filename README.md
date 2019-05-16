# MiniLang
A mini language script for PHP

[![Build Status](https://travis-ci.org/EFTEC/MiniLang.svg?branch=master)](https://travis-ci.org/EFTEC/MiniLang)
[![Packagist](https://img.shields.io/packagist/v/eftec/minilang.svg)](https://packagist.org/packages/eftec/minilang)
[![Total Downloads](https://poser.pugx.org/eftec/minilang/downloads)](https://packagist.org/packages/eftec/minilang)
[![Maintenance](https://img.shields.io/maintenance/yes/2019.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.8-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Getting started

Installing it using composer:

> composer requires eftec/minilang

Creating a new project

```php
use eftec\minilang\MiniLang;
include "../lib/MiniLang.php"; // or the right path to MiniLang.php
$mini=new MiniLang();
$mini->separate("when field1=1 then field2=2"); // we set the logic of the language but we are not executed it yet.
$result=['field1'=>1,'field2'=>0]; // used for variables.
$callback=new stdClass(); // used for callbacks.
$mini->evalAllLogic($callback,$result);
var_dump($result);

```


## definition

### Sintaxis.

The sintaxis of the code is separate in two parts. WHERE (or when) AND SET (or then).

Example:

````php
$mini->separate("when field1=1 then field2=2");
````

It says, if field1=1 then we set field2 as 2.


### Variables

A variable is defined by 

`varname`

For example

`varname=20`

### Global variables

A global variable, takes the values of the PHP ($GLOBAL), so it doesn't need to be defined or set inside the language

A global variable is defined by

`$globalname`

For example:

`$globalname=30`




## Documentation

[Medium-Creating a new scripting language on PHP](https://medium.com/@jcastromail/creating-a-new-scripting-language-on-php-e12b9a2884da)


## To-do

* Documentation.

## Version

* 1.15 2019-01-06 If we add (+) two values and they are numeric then we add, otherwise we concantenate.  
* 1.14 2018-12-26 First open source version.

