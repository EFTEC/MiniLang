<?php

namespace eftec\tests;

class DummyClass {
	var $values=[];
	public function test() {
		return 1;
	}
}

class CompilationTest extends AbstractMiniLang {
    /**
     * @throws \Exception
     */
    public function test1() {
	    $this->mini->separate("when field1=1 then field2=2");


	    $caller=new DummyClass();
	    $caller->values=['field1'=>1,'field2'=>0];

	    if ($this->mini->evalLogic($caller,$caller->values)) {
		    $this->mini->evalSet($caller,$caller->values);
	    }
    	
    	
	    self::assertEquals(2,$caller->values['field2'],'field2 must be 2'); // default value


    }

}
