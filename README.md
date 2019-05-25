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
$mini->separate("when field1=2 then field2=4"); // we set more logic.
$result=['field1'=>1,'field2'=>0]; // used for variables.
$callback=new stdClass(); // used for callbacks if any
$mini->evalAllLogic($callback,$result);
var_dump($result);

```


## definition

### Sintaxis.

The sintaxis of the code is separate in two parts. WHERE (or when) AND SET (or then).

Example:

```php
$mini->separate("when field1=1 then field2=2");
```

It says, if field1=1 then we set field2 as 2.


### Variables

A variable is defined by 


`varname`

Example: 
```php
$mini=new MiniLang();
$mini->separate("when field1>0 then field2=3"); // we prepare the language
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the languageand run the language
var_dump($variables);
```

### Global variables

A global variable, takes the values of the PHP ($GLOBAL), so it doesn't need to be defined or set inside the language

A global variable is defined by

`$globalname`

For example:

`$globalname=30`

Example Code: 
```php
$field1=1; // global variable
$mini=new MiniLang();
$mini->separate('when $field1>0 then $field2=3'); // we prepare the language
$variables=[]; // local variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the languageand run the language
var_dump($field1);
```

### Reserved methods

| Reserved word | Explanation                                                                  |
|---------------|------------------------------------------------------------------------------|
| null()          | null value                                                                   |
| false()         | false value                                                                  |
| true()          | true value                                                                   |
| on()            | 1                                                                            |
| off()           | 0                                                                            |
| undef()         | -1 (for undefined)                                                           |
| flip()          | (special value). It inverts a value ON<->OFF<br>Used as value=flip()                                 |
| now()          | returns the current timestamp (integer)                                      |
| timer()         | returns the current timestamp (integer)                                      |
| interval()      | returns the interval (in seconds) between the last change and now. It uses the field dateLastChange or method dateLastChange() of the callback class            |
| fullinterval()  | returns the interval (in seconds) between the start of the process and now. It uses the field dateInit or method dateInit() of the callback class |


Example: [examples/examplereserved.php](examples/examplereserved.php)  
```php
$mini=new MiniLang();
$mini->separate("when true=true then field1=timer()"); // we prepare the language
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the language
var_dump($variables);
```


Example Timer: [examples/examplereservedtimer.php](examples/examplereservedtimer.php)  
```php
class ClassWithTimer {
	var $dateLastChange;
	public function dateInit() {
		return time();
	}
	public function __construct()
	{
		$this->dateLastChange=time();
		
	}
}
$mini=new MiniLang();
$mini->separate("when true=true then field1=interval() and field2=fullinterval()"); // we prepare the language
$variables=['field1'=>0,'field2'=>0]; // we define regular variables
$callback=new ClassWithTimer();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the language
var_dump($variables);
```

## Documentation

[Medium-Creating a new scripting language on PHP](https://medium.com/@jcastromail/creating-a-new-scripting-language-on-php-e12b9a2884da)


## To-do

* Documentation.

## Version

* 1.16 2019-05-24 Fixed some bug (if the method is not defined)   
* 1.15 2019-01-06 If we add (+) two values and they are numeric then we add, otherwise we concantenate.  
* 1.14 2018-12-26 First open source version.   

