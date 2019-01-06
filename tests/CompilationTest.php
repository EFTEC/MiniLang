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
	    $this->mini->separate("when field1=1 then field2=2 and field3=1+2 and field4='a'+'b'");


	    $caller=new DummyClass();
	    $caller->values=['field1'=>1,'field2'=>0,'field3'=>0,'field4'=>0];

	    if ($this->mini->evalLogic($caller,$caller->values)) {
		    $this->mini->evalSet($caller,$caller->values);
	    }
    	
    	
	    self::assertEquals(2,$caller->values['field2'],'field2 must be 2'); // default value
	    self::assertEquals(3,$caller->values['field3'],'field2 must be 3'); // default value
	    self::assertEquals('ab',$caller->values['field4'],'field2 must be ab'); // default value

	    
	    

    }

	/**
	 * @throws \Exception
	 */
	public function test2() {
		$this->mini->separate("when field1=1 then field2=2");


		$caller=new DummyClass();
		$caller->values=['field1'=>0,'field2'=>12345,'field3'=>0,'field4'=>0];

		if ($this->mini->evalLogic($caller,$caller->values)) {
			$this->mini->evalSet($caller,$caller->values);
		}
		
		self::assertEquals(12345,$caller->values['field2'],'field2 must be 12345'); // default value

	}

}
