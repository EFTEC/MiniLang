<?php
use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

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