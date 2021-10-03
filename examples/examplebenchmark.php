<?php

use eftec\minilang\MiniLang;

include "../lib/MiniLang.php";

echo "<h1>test</h1>";

class DummyClassExample {
	var $values=[];
	public function test() {
		return 1;
	}
}

$expr='when true() and field1=1 and field1=1 and field1=1 and field1=1 then field3="abc" and field3="abc" and field3="abc" and field3="abc" ';



$caller=new DummyClassExample();
$caller->values=['field1'=>1,'field2'=>0,'field3'=>123,'countries'=>['us'=>'usa','ca'=>'canada']];

$mini=new MiniLang($caller,$caller->values);
$n=2;
$arr=[10,20,30,40,'a'=>666];

// ************************** (reset+separate2+evalAllLogic2) x 1000
echo "<h2>(reset+separate2+evalAllLogic2) x 1000</h2>";
$t0=microtime(true);
for($i=0;$i<1000;$i++) {
    $mini->reset();
    $mini->separate2($expr);
    $mini->evalAllLogic2();
}
$t1=microtime(true);
$t100=($t1-$t0); // it is the slower time.
echo "<br><b>Speed: ".round(($t1-$t0),6)."</b> seconds. Comparison: <b>".round((($t1-$t0)*100/$t100),2)."%</b> (smaller is better)";


// ************************** (reset+separate+evalAllLogic) x 1000
echo "<h2>(reset+separate+evalAllLogic) x 1000</h2>";
$t0=microtime(true);
for($i=0;$i<1000;$i++) {
    $mini->reset();
    $mini->separate($expr);
    $mini->evalAllLogic(true);
    //var_dump($mini->getDictEntry('field3'));
}
$t1=microtime(true);
echo "<br><b>Speed: ".round(($t1-$t0),6)."</b> seconds. Comparison: <b>".round((($t1-$t0)*100/$t100),2)."%</b> (smaller is better)";

echo "<h2>evalAllLogic x 1000</h2>";
$t0=microtime(true);
$mini->reset();
$mini->separate($expr);

for($i=0;$i<1000;$i++) {
    $mini->evalAllLogic(true);
    //var_dump($mini->getDictEntry('field3'));
}
$t1=microtime(true);
echo "<br><b>Speed: ".round(($t1-$t0),6)."</b> seconds. Comparison: <b>".round((($t1-$t0)*100/$t100),2)."%</b> (smaller is better)";
function true() {
    return true;
}

// *********************************************************

echo "<h2>(evalAllLogic2) x 1000</h2>";
$t0=microtime(true);
$mini->reset();
$mini->separate2($expr);
/*
$mini->reset();
$mini->separate2($expr);
echo "<pre>";
echo $mini->generateClass2();
echo "</pre>";
die(1);
*/
for($i=0;$i<1000;$i++) {
    $mini->evalAllLogic2();
    //var_dump($mini->getDictEntry('field3'));
}
$t1=microtime(true);
echo "<br><b>Speed: ".round(($t1-$t0),6)."</b> seconds. Comparison: <b>".round((($t1-$t0)*100/$t100),2)."%</b> (smaller is better)";




// **************************************************************

// This code was generated with the next code

/*
$mini->reset();
$mini->separate2($expr);
echo $mini->generateClass2();
echo "</pre>";
die(1);
*/

class RunClass extends MiniLang {
    public function Code($idx=0) {
        switch($idx) {
            case 0:
                if ($this->callFunction('true',[]) && $this->dict['field1']==1 && $this->dict['field1']==1 && $this->dict['field1']==1 && $this->dict['field1']==1) {
                    $_foundIt=true;
                    $this->dict['field3']='abc';
                    $this->dict['field3']='abc';
                    $this->dict['field3']='abc';
                    $this->dict['field3']='abc';

                }
                break;
            default:
                trigger_error('Line '.$idx.' is not defined');
        }
    }
}

echo "<h2>PHP method of class x 1000</h2>";
$t0=microtime(true);
$r=new RunClass($caller,$caller->values);

for($i=0;$i<1000;$i++) {
   
    $r->Code(0);
    //var_dump($mini->getDictEntry('field3'));
}
$t1=microtime(true);
echo "<br><b>Speed: ".round(($t1-$t0),6)."</b> seconds. Comparison: <b>".round((($t1-$t0)*100/$t100),2)."%</b> (smaller is better)";