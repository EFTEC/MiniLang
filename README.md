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
$result=['field1'=>1,'field2'=>0]; // used for variables.
$callback=new stdClass(); // used for callbacks if any

$mini=new MiniLang($callback,$result);
$mini->separate("when field1=1 then field2=2"); // we set the logic of the language but we are not executed it yet.
$mini->separate("when field1=2 then field2=4"); // we set more logic.

$mini->evalAllLogic();
var_dump($result);

```


## definition

### Sintaxis.

The sintaxis of the code is separate in three parts.INIT (initialize) , WHERE (or when) AND SET (or then).

Example:

```php
$mini->separate("field=0 when field1=1 then field2=2");
```

It says, field=0 (initialize the value only on start) if field1=1 then we set field2 as 2.


### Variables

A variable is defined by 

`varname`

Example:  [examples/examplevariable.php](examples/examplevariable.php)
```php
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini=new MiniLang($callback,$variables);
$mini->separate("when field1>0 then field2=3"); // we prepare the language

$mini->evalAllLogic(); // we set the variables and run the languageand run the language
var_dump($variables); // field1=1, field2=3
```
### Variables defined by a PHP Object

A variable could host a PHP object and it is possible to call and to access the fields inside it.

`varname.field`

* If the field exists then it uses it.
* If the field doesn't exist then it uses a method of the caller.
* If the method of the caller doesn't exist then it tries to use the method of the service class 
* Finally, if everything fails then it trigges an error.

Example of code [examples/examplevariable2.php](examples/examplevariable2.php)

```php
class MyModel {
	var $id=1;
	var $value="";	
	public function __construct($id=0, $value="")
	{
		$this->id = $id;
		$this->value = $value;
	}
}
class ClassCaller {
	public function Processcaller($arg) {
		echo "Caller: setting the variable {$arg->id}<br>";
	}
}
class ClassService {
	public function ProcessService($arg) {
		echo "Service: setting the variable {$arg->id}<br>";
	}
}
$variables=['field1'=>new MyModel(1,"hi")
			,'field2'=>new MyModel(2,'')
			,'field3'=>new MyModel(3,'')]; // we define regular variables
$callback=new ClassCaller();
$mini=new MiniLang($callback,$variables,[],[],new ClassService());
$mini->separate("when field1.id>0 then 
				field2.value=3 
				and field3.processcaller 
				and processcaller(field3) 
				and processservice(field3)"); // we prepare the language

$mini->evalAllLogic(false); // we set the variables and run the languageand run the language
var_dump($variables);
```

* field2.value references the field "value" (MyModel)
* field3.processcaller references the method ClassCaller::processcaller()
* processcaller(field3) does the same than field3.processcaller
* processservice(field3) calls the method ClassService::processservice()

### Variables defined by a PHP array

A variable could host a PHP array and it is possible to call and to access the elements inside it.

`varname.field`

* If the element exists then it uses it.
* If the element doesn't exist then it uses a method of the caller.
* If the method of the caller doesn't exist then it tries to use the method of the service class 
* Finally, if everything fails then it trigges an error.

Example of code [examples/examplevariable_arr.php](examples/examplevariable_arr.php)

```php
class ClassCaller {
	public function Processcaller($arg) {
		echo "Caller: setting the variable {$arg['id']}<br>";
	}
}
class ClassService {
	public function ProcessService($arg) {
		echo "Service: setting the variable {$arg['id']}<br>";
	}
}
$variables=['field1'=>['id'=>1,'value'=>3]
			,'field2'=>['id'=>2,'value'=>'']
			,'field3'=>['id'=>3,'value'=>'']]; 
$callback=new ClassCaller();
$mini=new MiniLang($callback,$variables,[],[],new ClassService());
$mini->separate("when field1.id>0 then 
				field2.value=3 
				and field3.processcaller 
				and processcaller(field3) 
				and processservice(field3)"); 


$mini->evalAllLogic(false);
var_dump($variables);

```

* field2.value references the element "value" of the array
* field3.processcaller references the method ClassCaller::processcaller()
* processcaller(field3) does the same than field3.processcaller
* processservice(field3) calls the method ClassService::processservice()


### Global variables

A global variable, takes the values of the PHP ($GLOBAL), so it doesn't need to be defined or set inside the language

A global variable is defined by

`$globalname`

For example:

`$globalname=30`

Example Code: [examples/exampleglobal.php](examples/exampleglobal.php)
```php
$field1=1; // global variable
$variables=[]; // local variables
$callback=new stdClass();
$mini=new MiniLang($callback,$variables);
$mini->separate('when $field1>0 then $field1=3'); // we prepare the language

$mini->evalAllLogic(); // we set the variables and run the languageand run the language
var_dump($field1); // returns 3
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
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini=new MiniLang($callback,$variables);
$mini->separate("when true=true then field1=timer()"); // we prepare the language

$mini->evalAllLogic(); // we set the variables and run the language
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
$variables=['field1'=>0,'field2'=>0]; // we define regular variables
$callback=new ClassWithTimer();
$mini=new MiniLang($callback,$variables);
$mini->separate("when true=true then field1=interval() and field2=fullinterval()"); // we prepare the language

$mini->evalAllLogic(); // we set the variables and run the language
var_dump($variables);
```

## Documentation

[Medium-Creating a new scripting language on PHP](https://medium.com/@jcastromail/creating-a-new-scripting-language-on-php-e12b9a2884da)


## To-do

* Documentation.

## Version

* 2.2  2019-06-16 var.fun = fun(var)
* 2.00 2019-05-28 Now it has INIT part together with WHERE and SET
* 1.17 2019-05-25 Some maintenance. Added new documentation.
* 1.16 2019-05-24 Fixed some bug (if the method is not defined)   
* 1.15 2019-01-06 If we add (+) two values and they are numeric then we add, otherwise we concantenate.  
* 1.14 2018-12-26 First open source version.   

