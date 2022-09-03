<?php

namespace eftec\tests;

use eftec\minilang\MiniLang;
use Exception;

class DummyClass
{
    var $values = [];

    public function dateLastChange(): int
    {
        return 123;
    }

    public function dateInit(): int
    {
        return 123;
    }

    public function testMethod(): int
    {
        echo "calling method";
        return 1;
    }

    public function ping($pong, $arg2 = '', $arg3 = '', $arg4 = '', $arg5 = ''): string
    {
        return $pong . $arg2 . $arg3 . $arg4 . $arg5;
    }
}

class CompilationTest extends AbstractMiniLang
{
    /**
     * @throws Exception
     */
    public function test1(): void
    {
        $this->mini->separate("when field1=1 then field2=2 and field3=1+2 and field4='a' & 'b'");

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

    public function test1Case1(): void
    {
        $this->mini->caseSensitive = false;
        $this->mini->separate("when FIELD1=1 then field2=2 and field3=1+2 and field4='a' & 'b'");

        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        $this->mini->caseSensitive = true;
        self::assertEquals(2, $caller->values['field2'], 'field2 must be 2'); // default value
        self::assertEquals(3, $caller->values['field3'], 'field3 must be 3'); // default value
        self::assertEquals('ab', $caller->values['field4'], 'field4 must be ab'); // default value

    }

    public function testArea(): void
    {
        $this->mini->areaName = ['AREA1', 'AREA2'];
        $this->mini->separate("when field1=1 then field2=30*3, field3=30-3, field4=30/2 AREA1 field5=20");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => -1, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals(['AREA1' => [0 => 'field', '1' => 'field5', '2' => null]], $this->mini->areaValue);
    }
    public function testClass2(): void
    {
        $this->mini->reset();
        $this->mini->separate2("loop key=loopvalues");
        $this->mini->separate2("when key._key='k4' then break");
        $this->mini->separate2("when field1=1 then counter1=counter1+1");
        $this->mini->separate2("when field2=1 then counter1='fail' else counter2=counter2-1");
        $this->mini->separate2('loop end');
        $a1=$this->mini->generateClass('GenerateClass',null,__DIR__.'/GenerateClass.php');
        $this->assertNotNull($a1);
        include __DIR__.'/GenerateClass.php';
        $dic=['field1'=>1,'field2'=>2,'counter1'=>0,'counter2'=>0
            ,'loopvalues'=>['k1'=>'v1','k2'=>'v2','k3'=>'v3','k4'=>'v4','k5'=>'v5']];
        $gen=new \GenerateClass(null,$dic);
        $gen->evalAllLogic();
        $this->assertEquals(3,$gen->getDictEntry('counter1')); // 3 because we skip when key='k4'
        $this->assertEquals(-3,$gen->getDictEntry('counter2'));
        $this->assertEquals('k4',$gen->getDictEntry('key._key'));
        $this->assertEquals(['_key'=>'k4','_value'=>'v4'],$gen->getDictEntry('key'));
    }
    public function testloopempty(): void
    {
        $this->mini->reset();
        $this->mini->separate2("loop key=loopvalues");
        $this->mini->separate2("when key._key='k4' then break");
        $this->mini->separate2("when field1=1 then counter1=counter1+1");
        $this->mini->separate2("when field2=1 then counter1='fail' else counter2=counter2-1");
        $this->mini->separate2('loop end');
        $a1=$this->mini->generateClass('GenerateClass2',null,__DIR__.'/GenerateClass2.php');
        $this->assertNotNull($a1);
        include __DIR__.'/GenerateClass2.php';
        $dic=['field1'=>1,'field2'=>2,'counter1'=>0,'counter2'=>0,'loopvalues'=>[]];
        $gen=new \GenerateClass2(null,$dic);
        $gen->evalAllLogic();
        $this->assertEquals(0,$gen->getDictEntry('counter1'));
        $this->assertEquals(0,$gen->getDictEntry('counter2'));
        $this->assertEquals(null,$gen->getDictEntry('key._key'));
        $this->assertEquals(null,$gen->getDictEntry('key'));
    }
    public function testnegative(): void
    {
        $this->mini->reset();
        $this->mini->separate2('when a=-1 then b=b-1');
        $dic=['a'=>-1,'b'=>'1'];
        $this->mini->setDict($dic);
        $this->mini->evalAllLogic();
        $this->assertEquals("\$this->dict['b']=\$this->dict['b']+-1;\n"
            ,$this->mini->setPHP[0]);
        $this->assertEquals("\$this->dict['a']==-1"
            ,$this->mini->wherePHP[0]);
    }
    public function testnegative2(): void
    {
        $this->mini->reset();
        $this->mini->separate2('when a=-1 then b=-1');
        $dic=['a'=>-1,'b'=>'1'];
        $this->mini->setDict($dic);
        $this->mini->evalAllLogic();
        $this->assertEquals("\$this->dict['b']=-1;\n"
            ,$this->mini->setPHP[0]);
        $this->assertEquals("\$this->dict['a']==-1"
            ,$this->mini->wherePHP[0]);
    }
    public function testExtra2(): void
    {
        $this->mini->reset();
        $this->mini->separate('when a=-1 then b=b-1');
        $r=MiniLang::unserialize($this->mini->serialize(),null);
        $this->assertEquals($this->mini->set[0],$r->set[0]);
        $this->assertEquals($this->mini->where[0],$r->where[0]);
    }

    public function testnegative3(): void
    {
        $this->mini->reset();
        $this->mini->separate('when a=-1 then b=b-1');
        $dic=['a'=>-1,'b'=>'1'];
        $this->mini->setDict($dic);
        $this->mini->evalAllLogic();
        $this->assertEquals([0=>[0=>['pair','field','b',null,'=','field','b',null,'+','number',-1,null]]]
            ,$this->mini->set);
        $this->assertEquals([0=>[0=>['pair','field','a',null,'=','number',-1,null]]]
            ,$this->mini->where);
        $this->assertEquals(0,$this->mini->getDictEntry('b'));
    }
    public function testSeparate4(): void
    {
        global $field3,$field4;
        $field3=['alpha'=>'???'];
        $this->mini->reset();
        $this->mini->separate2("when field1>1 or field1<2 or field1<>1 or str_contains(field1,'hi') 
            then field2=30*3");
        $this->mini->separate2("when field1>=1 or field1<=2 or field1<>1 or str_contains(field1,'hi') 
            then field2=30*3");
        $this->mini->separate2("when field1<>555 then field2='*{{field1}}*' and \$field4=2 and \$field3.alpha=20");
        $dic=['field1'=>1];
        $this->mini->setDict($dic);
        $this->mini->evalAllLogic();
        $this->assertEquals("\$this->dict['field1']>1 || \$this->dict['field1']<2 ||".
            " \$this->dict['field1']!=1 ||".
            " \$this->callFunction('str_contains',[\$this->dict['field1'],'hi'])"
            ,$this->mini->wherePHP[0]);
        $this->assertEquals("\$this->dict['field1']>=1 || \$this->dict['field1']<=2 ||".
            " \$this->dict['field1']!=1 ||".
            " \$this->callFunction('str_contains',[\$this->dict['field1'],'hi'])"
            ,$this->mini->wherePHP[1]);
        $this->assertEquals("\$this->dict['field1']!=555"
            ,$this->mini->wherePHP[2]);
        $this->assertEquals("\$this->dict['field2']=\$this->getValueP('*{{field1}}*');\n".
            "\$GLOBALS['field4']=2;\n".
            "\$GLOBALS['field3']['alpha']=20;\n",$this->mini->setPHP[2]);
        $this->assertEquals(['alpha'=>20],$field3);
        $this->assertEquals(2,$field4);
        $this->assertEquals('*1*',$this->mini->getDictEntry('field2'));
    }
    public function testSeparate2(): void
    {
        $this->mini->reset();
        $this->mini->separate2("when field1=1 and field1=2 then field2=30*3");
        $this->mini->separate2("when field1=1 then field2.fn2=20");
        $this->mini->separate2('when field1=1 then field5=$mivar and $mivar2="test"');
        $this->mini->separate2("when testmethod()=1 then field2=testmethod()");
        $this->mini->separate2("when obj.testmethod(1)=obj.testmethod() then field2=123");
        $this->mini->separate2("when 1=1 then field3=ping('pong')");
        $this->mini->separate2("when 1=1 then field3=field2.ping() and field4=\$globalfield.field");
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
        $this->mini->separate2("when 1=1 then field3=field2.ping(1,2,3,4) and field4=ping('a','b','c') and field5.invert()");
        $this->mini->separate2("when field1=1 then field4='it is a value {{field1}},{{field2}}' ");

        $txts=[];
        foreach($this->mini->setPHP as $t) {
            $txts[]=str_replace("\n",'\n',$t);
        }

        self::assertEquals([
            '$this->dict[\'field1\']==1 && $this->dict[\'field1\']==2',
            '$this->dict[\'field1\']==1',
            '$this->dict[\'field1\']==1',
            '$this->callFunction(\'testmethod\',[])==1',
            '$this->callFunction(\'testmethod\',[$this->dict[\'obj\'],1])==$this->callFunction(\'testmethod\',[$this->dict[\'obj\']])',
            '1==1',
            '1==1',
            '1==1',
            '1==1',
            '$this->dict[\'field1\']==1'
        ], $this->mini->wherePHP);
        self::assertEquals([
            '$this->dict[\'field2\']=30*3;\n',
            '$this->dict[\'field2\'][\'fn2\']=20;\n',
            '$this->dict[\'field5\']=$GLOBALS[\'mivar\'];\n$GLOBALS[\'mivar2\']=\'test\';\n',
            '$this->dict[\'field2\']=$this->callFunction(\'testmethod\',[]);\n',
            '$this->dict[\'field2\']=123;\n',
            '$this->dict[\'field3\']=$this->callFunction(\'ping\',[\'pong\']);\n',
            '$this->dict[\'field3\']=$this->callFunction(\'ping\',[$this->dict[\'field2\']]);\n$this->dict[\'field4\']=$GLOBALS[\'globalfield\'][\'field\'];\n',
            '$this->dict[\'field1\']=$this->callFunction(\'null\',[]);\n$this->dict[\'field2\']=$this->callFunction(\'false\',[]);\n$this->dict[\'field3\']=$this->callFunction(\'true\',[]);\n$this->dict[\'field4\']=$this->callFunction(\'on\',[]);\n$this->dict[\'field5\']=$this->callFunction(\'off\',[]);\n$this->dict[\'field6\']=$this->callFunction(\'param\',[$this->dict[\'field6\'],\'l1.l2.l3\']);\n$this->dict[\'field7\']=$this->callFunction(\'undef\',[]);\n$this->dict[\'field8\']=$this->dict[\'field8\']=$this->callFunction(\'flip\',[$this->dict[\'field8\']]);\n$this->dict[\'field9\']=$this->callFunction(\'now\',[]);\n$this->dict[\'field10\']=$this->callFunction(\'timer\',[]);\n$this->dict[\'field11\']=$this->callFunction(\'interval\',[]);\n$this->dict[\'field12\']=$this->callFunction(\'fullinterval\',[]);\n',
            '$this->dict[\'field3\']=$this->callFunction(\'ping\',[$this->dict[\'field2\'],1,2,3,4]);\n$this->dict[\'field4\']=$this->callFunction(\'ping\',[\'a\',\'b\',\'c\']);\n$this->callFunction(\'invert\',[$this->dict[\'field5\']]);\n',
            '$this->dict[\'field4\']=$this->getValueP(\'it is a value {{field1}},{{field2}}\');\n',
        ], $txts);
    }

    public function test1Extra(): void
    {

        $this->mini->separate("when field1=1 then field2=30*3, field3=30-3, field4=30/2 ");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => -1, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }

        self::assertEquals(90, $caller->values['field2']);
        self::assertEquals(15, $caller->values['field4']);
        self::assertEquals(27, $caller->values['field3']);


    }

    public function testStaticCase(): void
    {
        self::assertEquals('lower', MiniLang::getCase('hello'));
        self::assertEquals('upper', MiniLang::getCase('HELLO'));
        self::assertEquals('first', MiniLang::getCase('Hello'));
        self::assertEquals('normal', MiniLang::getCase('HEllo'));
    }

    public function test1Case2(): void
    {
        $this->mini->caseSensitive = false;
        $this->mini->separate("when FIELD1.FIELD2.FIELD3=1 then field2=2 and field3=1+2 and field4='a' & 'b'");

        $caller = new DummyClass();
        $caller->values = ['field1' => ['field2' => ['field3' => 1]], 'field2' => 0, 'field3' => 0, 'field4' => 0, 'field5' => 0];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        $this->mini->caseSensitive = true;
        self::assertEquals(2, $caller->values['field2'], 'field2 must be 2'); // default value
        self::assertEquals(3, $caller->values['field3'], 'field3 must be 3'); // default value
        self::assertEquals('ab', $caller->values['field4'], 'field4 must be ab'); // default value

    }

    /**
     * @throws Exception
     */
    public function testElse(): void
    {
        $this->mini->separate("when field1=1 
        then field2=2 
        else field2=100 and field3=field1");
        $caller = new DummyClass();
        $caller->values = ['field1' => 123, 'field2' => 0, 'field3' => 111];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        } else {
            $this->mini->evalSet(0, 'else');
        }

        self::assertEquals(100, $caller->values['field2']); // default value
        self::assertEquals(123, $caller->values['field3']); // default value

    }

    /**
     * @throws Exception
     */
    public function testGlobal(): void
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
     * @throws Exception
     */
    public function testServiceClass(): void
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
     * @throws Exception
     */
    public function testMethod(): void
    {
        $this->mini->separate("when obj.testmethod(1)=obj.testmethod() then field2=123");

        $values = ['obj' => new DummyClass(), 'field2' => 0, 'field3' => 0, 'field4' => 0];
        $this->mini->setDict($values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals(123, $this->mini->getDictEntry('field2'), 'field2 must be 123'); // default value
        self::assertEquals(123, $values['field2'], 'field2 must be 123'); // default value

    }

    /**
     * @throws Exception
     */
    public function testFn2(): void
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
     * @throws Exception
     */
    public function testFn3(): void
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
     * @throws Exception
     */
    public function testFunctions(): void
    {
        global $globalfield;
        $globalfield['field'] = "pong";
        $this->mini->separate("when 1=1 then 
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
            , 'field7' => 2, 'field8' => 123];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        if ($this->mini->evalLogic()) {
            $this->mini->evalSet();
        }
        self::assertEquals(null, $caller->values['field1'], 'field1');
        self::assertEquals(false, $caller->values['field2'], 'field1');
        self::assertEquals(true, $caller->values['field3'], 'field1');
        self::assertEquals(1, $caller->values['field4'], 'field1');
        self::assertEquals(0, $caller->values['field5'], 'field1');
        self::assertEquals(2, $caller->values['field6'], 'field1'); // ???
        self::assertEquals(-1, $caller->values['field7'], 'field1');
        self::assertEquals(0, $caller->values['field8'], 'the value must flip from 123->0');
        self::assertGreaterThan(1571701816, $caller->values['field9'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field10'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field11'], 'field1');
        self::assertGreaterThan(1571701839, $caller->values['field12'], 'field1');
        //self::assertEquals('pong', $caller->values['field1'], 'field1');

    }

    /**
     * @throws Exception
     */
    public function testFn4(): void
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

    public function testFn3b(): void
    {
        $this->mini->separate("when 1=1 and 1=2 then field3=1 else field3=2");
        $caller = new DummyClass();
        $caller->values = ['field1' => 1, 'field2' => "pong", 'field5' => 'on'];
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);

        $this->mini->evalAllLogic();
        self::assertEquals('2', $caller->values['field3'], 'field3 must be pong1234'); // default value

    }

    /**
     * @throws Exception
     */
    public function test2(): void
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

    public function test3(): void
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

    public function testArrays(): void
    {
        global $countries;
        global $arrays;
        global $megaarray;
        $caller = new DummyClass();
        $megaarray = [
            'first' =>
                ['second' =>
                    ['third' => 'abc']]
        ];
        $countries = ['first', 'us' => 'usa', 'ca' => 'canada'];
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
        $this->mini->separate("when field1=1 then field2=countries.us");
        $this->mini->separate("when field1=1 then field3=arrays.1");
        $this->mini->separate('when field1=1 then field4=$countries.us');
        $this->mini->separate('when field1=1 then field5=$arrays.1');
        $this->mini->separate('when field1=1 then field6=param($megaarray,"first.second.third")');
        $this->mini->separate('when field1=1 then field6b=$megaarray.param("first.second.third")');
        //$this->mini->separate('when field1=1 then countries.0="mexico"');
        $this->mini->separate('when field1=1 then countries.us="mexico"'); // us now is mexico
        $this->mini->separate('when countries.us="mexico" then countries.ca="mexico"'); // if us is mexico then ca is mexico too
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);
        $this->mini->evalAllLogic();
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

    public function testArrays2(): void
    {
        $caller = new DummyClass();

        $caller->values = [
            'field1' => [1 => 1, 2 => 22, 'abc1' => '???', 'abc2' => '???'], 'result' => '???', 'result2' => '???'
        ];

        $this->mini = new MiniLang($caller, $caller->values);
        $this->mini->separate("when field1.1=1 then result='OK' else result='FALLA'");
        $this->mini->separate("when field1.2=33 then result2='FALLA' else result2='OK'");
        $this->mini->setCaller($caller);
        $this->mini->setDict($caller->values);
        $this->mini->evalAllLogic();
        //var_dump($this->mini->where[0]);
        self::assertEquals([0 => ['pair', 'subfield', 'field1', '2', '=', 'number', '33', null]], $this->mini->where[1]);
        self::assertEquals('OK', $caller->values['result']);
        self::assertEquals('OK', $caller->values['result2']);
    }

}
