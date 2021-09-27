<?php

namespace eftec\tests;

use eftec\minilang\MiniLang;
use Exception;

class DummyClass
{
    var $values = [];

    public function testMethod()
    {
        echo "calling method from DummyClass\n";
        return 1;
    }
    public function dateLastChange() {
        return 123;
    }
    public function dateInit() {
        return 123;
    }

    public function ping($pong, $arg2 = '', $arg3 = '', $arg4 = '', $arg5 = '')
    {
        return $pong . $arg2 . $arg3 . $arg4 . $arg5;
    }
    public function invert($value) {
        return $value;
    }
}

class CompilationTest extends AbstractMiniLang
{
    /**
     * @throws Exception
     */
    public function test1()
    {
        $this->mini->separate2("when field1=1 then field2=2 and field3=1+2 and field4='a' & 'b'");

        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }

        self::assertEquals(2, $caller->values['field2'], 'field2 must be 2'); // default value
        self::assertEquals(3, $caller->values['field3'], 'field3 must be 3'); // default value
        self::assertEquals('ab', $caller->values['field4'], 'field4 must be ab'); // default value
    }

    /**
     * @throws Exception
     */
    public function testElse()
    {
        $this->mini->separate2("when field1=1 
        then field2=2 
        else field2=100 and field3=field1");
        $caller = new DummyClass();
        $caller->values = ['field1' => 123, 'field2' => 0,'field3'=>111];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        } else {
            $this->mini->evalSet2(0,'else');
        }

        self::assertEquals(100, $caller->values['field2']); // default value
        self::assertEquals(123, $caller->values['field3']); // default value

    }

    /**
     * @throws Exception
     */
    public function testGlobal()
    {
        global $mivar, $mivar2;
        $mivar = "it is a test";
        $mivar2 = '';
        $this->mini->separate2('when field1=1 then field5=$mivar and $mivar2="test"');

        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals('it is a test', $caller->values['field5'], 'field5 must be it is a test'); // global
        self::assertEquals('test', $mivar2, '$mivar2 must be it is a test'); // global
    }

    /**
     * @throws Exception
     */
    public function testServiceClass()
    {
        $this->mini->separate2("when testmethod()=1 then field2=testmethod()");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals(1, $caller->values['field2'], 'field2 must be 1'); // default value

    }
    /**
     * @throws Exception
     */
    public function testMethod()
    {
        $this->mini->separate2("when obj.testmethod(1)=obj.testmethod() then field2=123");
        
        $values= ['obj' => new DummyClass(), 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setDict($values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals(123, $this->mini->getDictEntry('field2'), 'field2 must be 123'); // default value
        self::assertEquals(123, $values['field2'], 'field2 must be 123'); // default value

    }
    /**
     * @throws Exception
     */
    public function testFn2()
    {
        $this->mini->separate2("when 1=1 then field3=ping('pong')");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals('pong', $caller->values['field3'], 'field3 must be pong'); // default value
    }

    /**
     * @throws Exception
     */
    public function testFn3()
    {
        global $globalfield;
        $globalfield['field'] = "pong";
        $this->mini->separate2("when 1=1 then field3=field2.ping() and field4=\$globalfield.field");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => "pong"];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals('pong', $caller->values['field3'], 'field3 must be pong'); // default value
        self::assertEquals('pong', $caller->values['field4'], 'field4 must be pong'); // default value
    }

    /**
     * @throws Exception
     */
    public function testFunctions()
    {
        global $globalfield;
        $globalfield['field'] = "pong";
        $this->mini->separate2("when 1=1 then 
        field1=null()
        and field2=false()
        and field3=true()
        and field4=on()
        and field5=off()
        and field6=param(field6,'l1.l2.l3')
        and field7=undef()
        and field8=flip()
        and field9=now()
        and field10=timer()
        and field11=interval()
        and field12=fullinterval()");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 2, 'field3' => 2, 'field4' => 2, 'field5' => 2, 'field6' => 2
            , 'field7' => 2,'field8'=>123];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals(null, $caller->values['field1'], 'field1');
        self::assertEquals(false, $caller->values['field2'], 'field1');
        self::assertEquals(true, $caller->values['field3'], 'field1');
        self::assertEquals(1, $caller->values['field4'], 'field1');
        self::assertEquals(0, $caller->values['field5'], 'field1');
        self::assertEquals(2, $caller->values['field6'], 'field1'); // ???
        self::assertEquals(-1, $caller->values['field7'], 'field1');
        self::assertEquals(0, $caller->values['field8'], 'field1');
        self::assertGreaterThan(1571701816, $caller->values['field9'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field10'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field11'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field12'], 'field1');
        //self::assertEquals('pong', $caller->values['field1'], 'field1');

    }

    /**
     * @throws Exception
     */
    public function testFn4()
    {
        $this->mini->separate2("when 1=1 then field3=field2.ping(1,2,3,4) and field4=ping('a','b','c') and field5.invert()");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => "pong", 'field5' => 'on'];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }
        self::assertEquals('pong1234', $caller->values['field3'], 'field3 must be pong1234'); // default value
        self::assertEquals('abc', $caller->values['field4'], 'field4 must be abc'); // default value
    }

    /**
     * @throws Exception
     */
    public function test2()
    {
        $this->mini->separate2("when field1=1 then field2=2");

        $caller = new DummyClass();
        $caller->values = ['field1' => 0, 'field2' => 12345, 'field3' => 0, 'field4' => 0];

        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {
            $this->mini->evalSet2();
        }

        self::assertEquals(12345, $caller->values['field2']); // default value

    }

    public function test3add()
    {
        $this->mini->reset();
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 12345, 'field3' => 3, 'field4' => 4,'field6'=>[1,2]];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate2("then field4+1+2 and field5+1+2 and field6._count>1 "); // only the first "+" is converted to +=

        self::assertEquals([
            '$this->dict[\'field4\']+=1+2;'."\n".
            '$this->dict[\'field5\']+=1+2;'."\n".
            '\count($this->dict[\'field6\'])>1;'."\n"
        ], $this->mini->setTxt);
    }
    public function test3rest()
    {
        $this->mini->reset();
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 12345, 'field3' => 3, 'field4' => 4,'field6'=>[1,2]];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate2("then field4+-1+-2 and field5+-1+-2 and field6._count>1 "); // only the first "+" is converted to +=

        self::assertEquals([
            '$this->dict[\'field4\']+=-1+-2;'."\n".
            '$this->dict[\'field5\']+=-1+-2;'."\n".
            '\count($this->dict[\'field6\'])>1;'."\n"
        ], $this->mini->setTxt);
    }
    public function test3()
    {
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 12345, 'field3' => 3, 'field4' => 4];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate2("when field1=1 then field4='it is a value {{field1}},{{field2}}' ");
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic2()) {

            $this->mini->evalSet2();
        }
        self::assertEquals(1, $caller->values['field1']);
        self::assertEquals(12345, $caller->values['field2']); // default value

        self::assertEquals(3, $caller->values['field3']);
        self::assertEquals('it is a value 1,12345', $caller->values['field4']);
    }

    public function testArrays()
    {
        global $countries;
        global $arrays;
        global $megaarray;
        $caller = new DummyClass();
        $megaarray=[
            'first'=>
                ['second'=>
                    ['third'=>'abc']]
        ];
        $countries = ['first','us' => 'usa', 'ca' => 'canada'];
        $arrays = [100, 200, 300, 400];
        $caller->values = [
            'field1' => 1,
            'field2' => 12345,
            'field3' => 12345,
            'field4' => 12345,
            'field5' => 12345,
            'field6' => 12345,
            'countries' => $countries,
            'arrays' => $arrays
        ];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate2("when field1=1 then field2=countries.us");
        $this->mini->separate2("when field1=1 then field3=arrays.1");
        $this->mini->separate2('when field1=1 then field4=$countries.us');
        $this->mini->separate2('when field1=1 then field5=$arrays.1');
        $this->mini->separate2('when field1=1 then field6=param($megaarray,"first.second.third")');
        $this->mini->separate2('when field1=1 then field6b=$megaarray.param("first.second.third")');
        //$this->mini->separate2('when field1=1 then countries.0="mexico"');
        $this->mini->separate2('when field1=1 then countries.us="mexico"'); // us now is mexico
        $this->mini->separate2('when countries.us="mexico" then countries.ca="mexico"'); // if us is mexico then ca is mexico too
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);
        $this->mini->evalAllLogic2(false);
        self::assertEquals('usa', $caller->values['field2']);
        self::assertEquals(200, $caller->values['field3']); 
        self::assertEquals('usa', $caller->values['field4']);
        self::assertEquals(200, $caller->values['field5']);
        self::assertEquals('abc', $caller->values['field6']);
        self::assertEquals('abc', $caller->values['field6b']);
        //self::assertEquals('mexico', $caller->values['countries'][0]);
        self::assertEquals('mexico', $caller->values['countries']['us']);
        self::assertEquals('mexico', $caller->values['countries']['ca']);
    }
}
