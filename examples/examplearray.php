<?php

use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

class DummyClassExample {
	public function show($msg) {
	    echo $msg."<br>";
		return 1;
	}
}
$caller=new DummyClassExample();
$caller->values=['values'=>['0_apple','1_orange','2_lemon','3_pineapple']];

$mini=new MiniLang($caller,$caller->values);
$mini->throwError=false;
$n=2;
$arr=[10,20,30,40,'a'=>666];

//$mini->separate('when true() then field2b=$arr');
$mini->separate('when values.0 then show(values.0) ');
$mini->separate('when values.1 then show(values.1) ');
$mini->separate('when values.2 then show(values.2) ');
$mini->separate('when values.3 then show(values.3) ');
$mini->separate('when values_count>=4 and values.4 then show(values.4) ');






//var_dump($caller->values['field2']);

echo "<hr>run:<br>";
$mini->evalAllLogic(false);

var_dump($caller->values);
echo "<hr>Errors:<br>";
var_dump($mini->errorLog);