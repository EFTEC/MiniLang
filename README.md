# MiniLang
This library is used to store business logic in a simple and yet powerful definition.

A mini script language for PHP.  It does three simple tasks.

1. (optional) It set some initial values **(INIT)**.
2. It evaluates a logic expression **(WHERE)**.
3. If the expression (logic) is true then it executes the SET expression **(SET)**, so we could change the value of a variable, call a function and task like that.
4. (optional) If the expression (logic) is false then it executes the ELSE expression **(INIT)**.

For example :

```
when var1>5 and var2>20 then var3=20 // when and then 
init var5=20 when var1>5 and var2>20 then var3=var5  // init, when and then
init var5=20 when var1>5 and var2>20 then var3=var5 else var3=var20 // init, when, then and else
when var1>$abc then var3=var5 // $abc is a PHP variable.
```

[![Packagist](https://img.shields.io/packagist/v/eftec/minilang.svg)](https://packagist.org/packages/eftec/minilang)
[![Total Downloads](https://poser.pugx.org/eftec/minilang/downloads)](https://packagist.org/packages/eftec/minilang)
[![Maintenance](https://img.shields.io/maintenance/yes/2024.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.8-blue.svg)]()
[![php](https://img.shields.io/badge/php->7.4-green.svg)]()
[![php](https://img.shields.io/badge/php-8.3-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Why we need a mini script?

Sometimes we need to execute arbitrary code in the basis of "if some value equals to, then we set or execute another code."

In PHP, we could do the next code.

```php
if($condition) {
    $variable=1;
}
```
However, this code is executed at runtime.  What if we need to execute this code at some specific point?.

We could do the next code:

```php
$script='if($condition) {
    $variable=1;
}';

// and later..
eval($script);
```

This solution works (and it only executes if we call the command eval). But it is verbose, prone to error, and it's dangerous.

Our library does the same but safe and clean.

```php
$mini->separate("when condition then variable=1");
```

## Table of Content

<!-- TOC -->
* [MiniLang](#minilang)
  * [Why we need a mini script?](#why-we-need-a-mini-script)
  * [Table of Content](#table-of-content)
  * [Getting started](#getting-started)
  * [Methods](#methods)
    * [Constructor](#constructor)
    * [reset()](#reset)
    * [setCaller(&$caller)](#setcallercaller)
    * [setDict(&$dict)](#setdictdict)
    * [function separate($miniScript)](#function-separateminiscript)
    * [evalLogic($index)](#evallogicindex)
    * [evalAllLogic($stopOnFound = true, $start = false)](#evalalllogicstoponfound--true-start--false)
    * [evalSet($idx = 0, $position = 'set')](#evalsetidx--0-position--set)
  * [Fields](#fields)
    * [$throwError](#throwerror-)
    * [$errorLog](#errorlog)
  * [Definition](#definition)
    * [Sintaxis.](#sintaxis)
    * [Variables](#variables)
    * [Variables defined by a PHP Object](#variables-defined-by-a-php-object)
    * [Variables defined by a PHP array](#variables-defined-by-a-php-array)
    * [Global variables](#global-variables)
  * [Literals](#literals)
    * [Examples](#examples)
    * [Reserved methods](#reserved-methods)
  * [init](#init)
    * [Code:](#code)
  * [where](#where-)
    * [Example](#example)
    * [Logical expressions allowed](#logical-expressions-allowed)
  * [set](#set)
    * [Setting expressions allowed](#setting-expressions-allowed)
    * [Example:](#example-1)
    * [Code:](#code-1)
  * [else](#else)
    * [Example](#example-2)
  * [Loop](#loop)
  * [Compiling the logic into a PHP class](#compiling-the-logic-into-a-php-class)
    * [Creating the class](#creating-the-class)
    * [Using the class](#using-the-class)
  * [Benchmark](#benchmark)
      * [(reset+separate+evalAllLogic) x 1000](#resetseparateevalalllogic-x-1000)
      * [evalAllLogic x 1000](#evalalllogic-x-1000)
      * [(reset+separate2+evalAllLogic2) x 1000](#resetseparate2evalalllogic2-x-1000)
      * [(evalAllLogic2) x 1000](#evalalllogic2-x-1000)
      * [PHP method of class x 1000](#php-method-of-class-x-1000)
  * [Documentation](#documentation)
  * [To-do](#to-do)
  * [Version](#version)
<!-- TOC -->

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

Another example:

```php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

$result=['var1'=>'hello'];
$global1="hello";
$mini=new MiniLang(null,$result);
$mini->throwError=false; // if error then we store the errors in $mini->errorLog;
$mini->separate('when var1="hello" then var2="world" '); // if var1 is equals "hello" then var2 is set "world"
$mini->separate('when $global1="hello" then $global2="world" '); // we can also use php variables (global)
$mini->evalAllLogic(false); // false means it continues to evaluate more expressions if any 
							// (true means that it only evaluates the first expression where "when" is valid)
var_dump($result); //  array(2) { ["var1"]=> string(5) "hello" ["var2"]=> string(5) "world" }
var_dump($global2); //  string(5) "world"
```

## Methods

### Constructor

> __construct(&$caller,&$dict,array $specialCom=[],$areaName=[],$serviceClass=null)

* object $caller Indicates the object with the callbacks
* array **$dict** Dictionary with initial values
* array **$specialCom** Special commands. it calls a function of the caller.
* array **$areaName** It marks special areas that could be called as "<namearea> somevalue."
*null|object $serviceClass A service class. By default, it uses the $caller.

### reset()

It reset the previous definitions but the variables, service and areas.

### setCaller(&$caller)

Set a caller object.     The caller object it could be a service class with method that they could be called inside the script.

### setDict(&$dict)

Set a dictionary with the variables used by the system.

### function separate($miniScript)

It sends an expression to the MiniLang, and it is decomposed in its parts. The script is not executed but parsed.

### evalLogic($index)

It evaluates a logic. It returns true or false.

* $index is the number of logic added by separate()

### evalAllLogic($stopOnFound = true, $start = false)

* bool $stopOnFound exit if some evaluation matches
* bool $start if true then it always evaluates the "init" expression.

### evalSet($idx = 0, $position = 'set')

It sets a value or values. It does not consider if WHERE is true or not.

* int  $idx number of expression
* string $position =['set','init']\[$i] It could be set or init

## Fields

### $throwError 

Boolean. 

* If true (default value), then the library throw an error when an error is found (for example if a method does not exist).
* If false, then every error is captured in the array $errorLog



### $errorLog

Array of String. if $throwError is false then every error is stored here.

Example:

```php
$this->throwError=false;
$mini->separate("when FIELDDOESNOTEXIST=1 then field2=2");
var_dump($this->errorLog);
```



## Definition

### Sintaxis.

The syntaxis of the code is separated into four parts. INIT, WHERE (or when), SET (or THEN) and ELSE.

Example:

```php
$mini->separate("when field1=1 then field2=2");
```

It says if field1=1 then we set field2 as 2.


### Variables

A variable is defined by `varname`

Example:  [examples/examplevariable.php](examples/examplevariable.php)
```php
$mini=new MiniLang();
$mini->separate("when field1>0 then field2=3"); // we prepare the language
$variables=['field1'=>1]; // we define regular variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the languageand run the language
var_dump($variables); // field1=1, field2=3
```
### Variables defined by a PHP Object

A variable could host a PHP object, and it is possible to call and to access the fields inside it.

`varname.field`

* If the field exists, then it uses it.
* If the field doesn't exist, then it uses a method of the object CALLER.
* If the method of the CALLER doesn't exist then it tries to use the method of the service class 
* If the method of the service class doesn't exist then it tries to use the inner method of the class MiniLang (with a prefix _). Example function _param()
* Finally, if everything fails then it triggers an error.

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
$mini=new MiniLang([],[],new ClassService());
$mini->separate("when field1.id>0 then 
                field2.value=3 
                and field3.processcaller 
                and processcaller(field3) 
                and processservice(field3)"); // we prepare the language
$variables=['field1'=>new MyModel(1,"hi")
            ,'field2'=>new MyModel(2,'')
            ,'field3'=>new MyModel(3,'')]; // we define regular variables
$callback=new ClassCaller();
$mini->evalAllLogic($callback,$variables,false); // we set the variables and run the languageand run the language
var_dump($variables);
```

* field2.value references the field "value" (MyModel)
* field3.processcaller references the method ClassCaller::processcaller()
* processcaller(field3) does the same as field3.processcaller
* processservice(field3) calls the method ClassService::processservice()

### Variables defined by a PHP array

A variable could hold an associative/index array, and it is possible to read and to access the elements inside it.

Example:

```php
$mini=new MiniLang(null,
                   [
                       'vararray'=>['associindex'=>'hi',0=>'a',1=>'b',2=>'c',3=>'d',4=>'last','a'=>['b'=>['c'=>'nested']]]
                   ]
                  );
```

```php
vararray.associndex // vararray['associindex'] ('hi')
vararray.4 // vararray[4] 'last'
vararray.123 // it will throw an error (out of index)
vararray.param('a.b.c')) // vararray['a']['b']['c'] ('nested')
param(vararray,'a.b.c')) // vararray['a']['b']['c'] ('nested')
vararray._first // first element ('hi')
vararray._last // last element ('last')
vararray._count // returns the number of elements. (6)
```

* If the element exists, then it uses it.
* If the element doesn't exist, then it uses a method of the caller.
* If the method of the caller doesn't exist then it tries to use the method of the service class 
* Finally, if everything fails then it triggers an error.

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

$mini=new MiniLang([],[],new ClassService());
$mini->separate("when field1.id>0 then 
                field2.value=3 
                and field3.processcaller 
                and processcaller(field3) 
                and processservice(field3)"); 

$variables=['field1'=>['id'=>1,'value'=>3]
            ,'field2'=>['id'=>2,'value'=>'']
            ,'field3'=>['id'=>3,'value'=>'']
           ]; 
$callback=new ClassCaller();
$mini->evalAllLogic($callback,$variables,false);
var_dump($variables);
```

* field2.value references the element "value" of the array
* field3.processcaller references the method ClassCaller::processcaller()
* processcaller(field3) does the same as field3.processcaller
* processservice(field3) calls the method ClassService::processservice()


### Global variables

A global variable takes the values of the PHP ($GLOBAL), so it doesn't need to be defined or set inside the language

A global variable is defined by

`$globalname`

```php
$globalname.associndex // $globalname['associindex']
$globalname.4 // $globalname[4]
$globalname.param('a.b.c') // $globalname['a']['b']['c']
param($globalname,'a.b.c') // $globalname['a']['b']['c']
```

Example:

`$globalname=30`

Example Code: [examples/exampleglobal.php](examples/exampleglobal.php)
```php
$field1=1; // our global variable
$mini=new MiniLang();
$mini->separate('when $field1>0 then $field1=3'); // we prepare the language
$variables=[]; // local variables
$callback=new stdClass();
$mini->evalAllLogic($callback,$variables); // we set the variables and run the languageand run the language
var_dump($field1); // returns 3
```

## Literals

| Type     | Example                      |
|----------|------------------------------|
| Number   | 20                           |
| string   | "hello world", 'hello world' |
| stringp  | "my name is {{var}}"         |
| function | namefunction(arg,arg)        |

### Examples

> set var=20 and var2="hello" and var3="hello {{var}}" and var4=fn()



### Reserved methods

| Reserved word                  | Explanation                                                                                                                                          |
|--------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------|
| null()                         | null value                                                                                                                                           |
| false()                        | false value                                                                                                                                          |
| true()                         | true value                                                                                                                                           |
| on()                           | 1                                                                                                                                                    |
| param(var,'l1.l2.l3')          | Separates an array (var) into var['l1']\['l2']\['l3']                                                                                                |
| off()                          | 0                                                                                                                                                    |
| undef()                        | -1 (for undefined)                                                                                                                                   |
| flip()                         | (special value). It inverts a value ON<->OFF<br>Used as value=flip()                                                                                 |
| now()                          | returns the current timestamp (integer)                                                                                                              |
| timer()                        | returns the current timestamp (integer)                                                                                                              |
| interval()                     | returns the interval (in seconds) between the last change and now. It uses the field dateLastChange or method dateLastChange() of the callback class |
| fullinterval()                 | returns the interval (in seconds) between the start of the process and now. It uses the field dateInit or method dateInit() of the callback class    |
| contains()/str_contains()      | returns true if the text is contained in another text.Example: str_contains(field1,'hi')                                                             |
| str_starts_with(), startwith() | returns true if the text starts with another text                                                                                                    |
| str_ends_with(),endwith()      | returns true if the text ends with another text.                                                                                                     |




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

## init

This part of the expression allows setting a value. This expression is usually optional, and it could be omitted.

> It is similar to SET, but it is executed before WHERE and no matter if WHERE is valid or not.

> **init counter=20** where variable1=20 set variable+counter 

* it set the counter to 20.
* And compares: variable1 is equaled to 20
* If yes, then increases a variable by counter

### Code:

```php
$mini->separate("init tmp=50 when condition=1 then field1+10"); // set tmp to 50. If condition is 1 then it increases the field1 by 10.
```


## where 

This part of the expression adds a condition to the statement.

We can also use "when."

> **where** expression    

or

> **when** expression    

It's possible to compare more than a condition at the same time by separating by "and" or "or."

> where v1=10 and v2=20 or v3<50

### Example

> where variable1=20 and $variable2=variable3 or function(20)=40

> where $field=20 and field2<>40 or field3=40 // sql syntax

> where $field==20 && field2!=40 || field3=+40 // PHP syntax

> where 1 // it always true

### Logical expressions allowed

* **equals:**  = and ==  (the symbol "=" and "==" acts similarly.)
* **not equals:** <> and !=
* **less than:** <
* **less or equal than:** <=
* **greater than:** >
* **greater or equal than:** >=
* **and logic:** "and" and &&
* **or logic:** "or" and ||


## set

This part of the expression allows setting the value of a variable.  It is possible to set more than one variable at the same time by separating by "," or "and."

We can also use the expression "set" or "then"

> **set** expression    

or

> **then** expression    

> This part of the expression is only executed if WHERE is valid



### Setting expressions allowed

We could set a variable using the next expressions:

* variable=20 
* variable=anothervariable
* variable=20+30
* variable=20-30  // it will work
* variable=20+-30 // !!!! **it will not work correctly because it will consider the -30 as a negative number "-30" and not "20 minus 30"**
* variable=40*50+30
* variable+30 // it increases the variable by 30
* variable+=anothervariable // it will increase the variable by the value of anothervariable
* variable-30 // it decreases the variable by -30  Note: **+variable-30 will not work because the symbol - is an operation so +-20 is a double operator**
* variable=flip() // it flips the value 0->1 or 1->0

This library does not allow complex instruction such as

* variable=20+30*(20+30) // is not allowed.

### Example:

> set variable1=20 and $variable2=variable3 and function(20)=40

### Code:

```php
$mini->separate("when condition=1 then field1+10"); // if condition is 1 then it increases the field1 by 10.
```

## else

This optional part of the expression allows setting the value of a variable.  It is possible to set more than one variable at the same time by separating by "," or "and".

> This code is only evaluated if "where" returns false of if ELSE is called manually.

### Example

> else variable1=20 and $variable2=variable3 and function(20)=40

## Loop

It is possible to create a loop using the space "loop"

To start a loop, you must write 

```php
$this->separate('loop variableloop=variable_with_values');
// where variable_with_values is must contains an array of values
// variableloop._key will contain the key of the loop
// variableloop._value will contain the value of the loop    
```

And to end the loop, you must use

```php
$this->separate('loop end');
```

You can escape the loop using the operator "break" in the "set" or "else".

```php
$this->separate('when condition set break else break');
```

> Note: Loops are only evaluated when you evaluate all the logic.  It does not work with evalLogic() and evalLogic2()
>
> Note: You can't add a condition to a loop, however you can skip a loop assigning an empty array

Example:

```php
$this->separate('loop $row=variable');
	$this->separate('loop $row2=variable');
		$this->separate('where op=2 then cc=2');
		$this->separate('where op=3 then break'); // ends of the first loop
	$this->separate('loop end');
$this->separate('loop end')     
$obj->evalAllLogic();        
```



## Compiling the logic into a PHP class

It is possible to create a class with the logic created in the language.  The goal is to increase the performance of the code.

### Creating the class

To generate the class, first we need to write the logic using the method **separate2()** instead of **separate()**.
It will store the logic inside an array of the instance of the class. You could use the code directly, or you could
save inside a class as follows:

```php
// create an instance of MiniLang
$mini=new MiniLang(null);
// the logic goes here
$mini->separate2('when var1="hello" and comp.f=false() then var2="world" '); // if var1 is equals "hello" then var2 is set "world"
// and the generation of the class
$r=$mini->generateClass('ExampleBasicClass','ns\example','ExampleBasicClass.php');
```

It will save a new file called 📄 ExampleBasicClass.php  (you can check the example 📁 [example/genclass/1.examplebasic.php]())

### Using the class

With the class generated, you can use this new class instead of **MiniLang**. Since this class is already compiled, then it is blazing fast. However, if you need to change the logic, then you will need to compile it again. (you can check the example 📁 [example/genclass/2.examplebasic.php]() and 📁 [example/genclass/ExampleBasicClass.php]())

```php
$result=['var1'=>'hello'];
$obj=new ExampleBasicClass(null,$result); 
$obj->evalAllLogic(true);
```

The class will look like:

```php
<?php
namespace ns\example;
use eftec\minilang\MiniLang;
class ExampleBasicClass extends MiniLang {
   public $numCode=2; // num of lines of code 
   public $usingClass=true; // if true then we are using a class (this class) 
   public function whereRun($lineCode=0) {
       // ...
   } // end function WhereRun
   public function setRun($lineCode=0) {
       // ...
   } // end function SetRun
   public function elseRun($lineCode=0) {
       // ...
   } // end function ElseRun
   public function initRun($lineCode=0) {
       // ...       
   } // end function InitRun
} // end class
```

Where each method evaluates a part of the expression.

## Benchmark

[examples/examplebenchmark.php](examples/examplebenchmark.php)

We call some operations 1000 times.

#### (reset+separate+evalAllLogic) x 1000

* We call the method reset(), separate() and evalAllLogic 1000 times.
* Speed: 0.028973 seconds. Comparison: 46.6% (smaller is better)

#### evalAllLogic x 1000

* We call the method reset() and separate() 1 time
* And we call the method evalAllLogic(true) 1000 times.
* Speed: 0.002387 seconds. Comparison: 3.84% (smaller is better)

#### (reset+separate2+evalAllLogic2) x 1000

* We call the method reset(), separate2() and evalAllLogic2() 1000 times.
* Speed: 0.06217 seconds. Comparison: 100% (smaller is better). It is the slower.

#### (evalAllLogic2) x 1000

* We call the method reset() and separate2() 1 time
* And we call the method evalAllLogic2() 1000 times.
* Speed: 0.013418 seconds. Comparison: 21.58% (smaller is better)

#### PHP method of class x 1000

* We create a class with the method $mini->generateClass2(); 1 time
* Then, we call the class (as simple php code) 1000 times.
* Speed: 0.000763 seconds. Comparison: 1.23% (smaller is better). It is the fastest method.

## Documentation

[Medium-Creating a new scripting language on PHP](https://medium.com/@jcastromail/creating-a-new-scripting-language-on-php-e12b9a2884da)


## To-do

* Documentation.

## Version
* 2.28 2024-03-02
  * Updating dependency to PHP 7.4. The extended support of PHP 7.2 ended 3 years ago.
  * Added more type hinting in the code.
* 2.27   2022-09-11
  * added an optional description 
* 2.26   2022-09-11
  * added method setDictEntry() 
* 2.25   2022-09-03
  * type hinting/validation for most methods. 
* 2.24   2022-08-26
  * clean the code.
  * [fix] $this->caller could be a null
* 2.23   2022-02-06
  * [fix] update for php 8.1
  * [change] update dependency for php 7.2.5 and higher.
* 2.22   2021-12-05
   * [fix] Added some validations and some small corrections when the value is incorrect.
   * Now, every valued set is a clone and not an instance of an object (runtime)
* 2.21   2021-10-03
   * Now a=b-1 works however a=b+-2 will not work anymore
   * Added loops
   * Fixed a bug when set $var.field=20. Now it sets the value correctly
   * Removed evalAllLogic2() because it is never used. Use instead evalAllLogic()
* 2.20.2 2021-09-26
   * Fixed the generation of the class when the number is negative.
* 2.20.1 2021-09-26
   * Fixed: "<>" in comparison.
   * Fixed a comparison with zero. Now, zero are converted (in the PHP class) as "0" instead of 0. Why? It is because field==null is equals than field==0. However, field=='0' is not equals
   * Class generated now it is more compressed.
   * Special functions are now correctly stored in the class generated
* 2.20 2021-09-26
   * It allows more features when it uses a class with the values pre-calculated
* 2.19 2021-09-26
   * It allows to save the library into a PHP native class.
* 2.18.1 2021-08-25
   * Fixed a problem where the language calls a custom function and there is not a service class.   
* 2.18 2021-01-16
    * Some cleanups. The operator "@" impacts the performance of PHP, so it is better to use isset(x)?x:null rather than @x
* 2.17.1 2020-10-13
    * Fixed a bug when the field is a number variable.30=30    
* 2.17 2020-10-13
    * Added new field **$throwError** and **$errorLog**.  Now the library throws an error (by default) if an error is found, instead of a trigger_error
    * The logic **AND** is optimized, if the first expression is false, then the second expression is never evaluated.
    * Added array.**\_first**, array.**\_last** and array.**\_count**
* 2.16 2020-09-22
    * The code was refactored and optimized.       
* 2.15 2020-01-12
    * fixed the evaluation of 'set variable=function(variable2)' where function is a php function
* 2.14 2019-10-26
  * Fixed the method **callFunction()** when the first argument of the function is an object but the method is defined in the caller or service class
* 2.12 2019-10-21 
  * New method **separate2()** and evalAllLogic2() it works with PHP's eval.
  * New method generateClass2()
  * separate2() and evalAllLogic2() works by parsing the tokens and converting in native PHP code.
  * However, it is from x2 to x5 slower than evalAllLogic(true).
  * This new motor could work by caching the evaluation in fields $setTxt,$whereTxt,$elseTxt,$initTxt
  * See benchmark 
* 2.11 2019-10-11 method _param (class).  Also, $a.fn() is allowed.
* 2.10 2019-10-07 method create()
* 2.9 2019-08-28
* * set field.value=20 , where field is an array works.  However, field.1=20 does not work (the parser considers .1 as a decimal)
* 2.8 2019-08-26
* *  if a field (inside where section) is an object. then it is possible to call the method as field.method(arg)
* *  Method getDictEntry() 
* 2.7 2019-08-04 
* * Added the methods serialize() and unserialize().
* * setCaller() argument is not a reference anymore (objects are reference by default)
* 2.6 2019-08-03 Now it allows "else". Example: "where exp then exp else exp"
* 2.5 2019-08-03 Now it allows to reference index of an array (numeric or associative)
* 2.4 2019-08-02 Added more documentation.  Now we allow unitary expression. 
* 2.3 2019-05-24 Fixed some bug (if the method is not defined)
* 2.0 2019-05-20 Second version. It uses PHP to parse the file.   
* 1.15 2019-01-06 If we add (+) two values, and they are numeric then we add. Otherwise, it concatenates.  
* 1.14 2018-12-26 First open-source version.   
