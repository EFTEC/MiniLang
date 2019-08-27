<?php

namespace eftec\tests;

use eftec\minilang\MiniLang;

class DummyClass
{
    var $values = [];

    public function testMethod()
    {
        echo "calling method";
        return 1;
    }

    public function ping($pong, $arg2 = '', $arg3 = '', $arg4 = '', $arg5 = '')
    {
        return $pong . $arg2 . $arg3 . $arg4 . $arg5;
    }
}

class CompilationTest extends AbstractMiniLang
{
    /**
     * @throws \Exception
     */
    public function test1()
    {
        $this->mini->separate("when field1=1 then field2=2 and field3=1+2 and field4='a'+'b'");

        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }

        self::assertEquals(2, $caller->values['field2'], 'field2 must be 2'); // default value
        self::assertEquals(3, $caller->values['field3'], 'field3 must be 3'); // default value
        self::assertEquals('ab', $caller->values['field4'], 'field4 must be ab'); // default value
    }

    /**
     * @throws \Exception
     */
    public function testElse()
    {
        $this->mini->separate("when field1=1 
        then field2=2 
        else field2=100 and field3=field1");
        $caller = new DummyClass();
        $caller->values = ['field1' => 123, 'field2' => 0,'field3'=>111];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        } else {
            $this->mini->evalSet(0,'else');
        }

        self::assertEquals(100, $caller->values['field2']); // default value
        self::assertEquals(123, $caller->values['field3']); // default value

    }

    /**
     * @throws \Exception
     */
    public function testGlobal()
    {
        global $mivar, $mivar2;
        $mivar = "it is a test";
        $mivar2 = '';
        $this->mini->separate('when field1=1 then field5=$mivar and $mivar2="test"');

        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals('it is a test', $caller->values['field5'], 'field5 must be it is a test'); // global
        self::assertEquals('test', $mivar2, '$mivar2 must be it is a test'); // global
    }

    /**
     * @throws \Exception
     */
    public function testServiceClass()
    {
        $this->mini->separate("when testmethod()=1 then field2=testmethod()");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals(1, $caller->values['field2'], 'field2 must be 1'); // default value

    }
    /**
     * @throws \Exception
     */
    public function testMethod()
    {
        $this->mini->separate("when obj.testmethod(1)=obj.testmethod() then field2=123");
        
        $values= ['obj' => new DummyClass(), 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setDict($values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals(123, $this->mini->getDictEntry('field2'), 'field2 must be 123'); // default value
        self::assertEquals(123, $values['field2'], 'field2 must be 123'); // default value

    }
    /**
     * @throws \Exception
     */
    public function testFn2()
    {
        $this->mini->separate("when 1=1 then field3=ping('pong')");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals('pong', $caller->values['field3'], 'field3 must be pong'); // default value
    }

    /**
     * @throws \Exception
     */
    public function testFn3()
    {
        global $globalfield;
        $globalfield['field'] = "pong";
        $this->mini->separate("when 1=1 then field3=field2.ping and field4=\$globalfield.field");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => "pong"];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals('pong', $caller->values['field3'], 'field3 must be pong'); // default value
        self::assertEquals('pong', $caller->values['field4'], 'field4 must be pong'); // default value
    }

    /**
     * @throws \Exception
     */
    public function testFn4()
    {
        $this->mini->separate("when 1=1 then field3=field2.ping(1,2,3,4) and field4=ping('a','b','c') and field5.invert()");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => "pong", 'field5' => 'on'];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals('pong1234', $caller->values['field3'], 'field3 must be pong1234'); // default value
        self::assertEquals('abc', $caller->values['field4'], 'field4 must be abc'); // default value
    }

    /**
     * @throws \Exception
     */
    public function test2()
    {
        $this->mini->separate("when field1=1 then field2=2");

        $caller = new DummyClass();
        $caller->values = ['field1' => 0, 'field2' => 12345, 'field3' => 0, 'field4' => 0];

        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }

        self::assertEquals(12345, $caller->values['field2']); // default value

    }

    public function test3()
    {
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 12345, 'field3' => 3, 'field4' => 4];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate("when field1=1 then field4='it is a value {{field1}},{{field2}}' ");
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {

            $this->mini->evalSet();
        }
        self::assertEquals(1, $caller->values['field1']);
        self::assertEquals(12345, $caller->values['field2']); // default value

        self::assertEquals(3, $caller->values['field3']);
        self::assertEquals('it is a value 1,12345', $caller->values['field4']);
    }

    public function test4()
    {
        global $countries;
        global $arrays;
        $caller = new DummyClass();
        $countries = ['us' => 'usa', 'ca' => 'canada'];
        $arrays = [100, 200, 300, 400];
        $caller->values = [
            'field1' => 1,
            'field2' => 12345,
            'field3' => 12345,
            'field4' => 12345,
            'field5' => 12345,
            'countries' => $countries,
            'arrays' => $arrays
        ];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate("when field1=1 then field2=countries.us");
        $this->mini->separate("when field1=1 then field3=arrays.1");
        $this->mini->separate('when field1=1 then field4=$countries.us');
        $this->mini->separate('when field1=1 then field5=$arrays.1');        
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);
        $this->mini->evalAllLogic(false);
        self::assertEquals('usa', $caller->values['field2']);
        self::assertEquals(200, $caller->values['field3']); 
        self::assertEquals('usa', $caller->values['field4']);
        self::assertEquals(200, $caller->values['field5']);         

    }
}
