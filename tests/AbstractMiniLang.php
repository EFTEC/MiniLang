<?php

namespace eftec\tests;




use eftec\minilang\MiniLang;
use PHPUnit\Framework\TestCase;


abstract class AbstractMiniLang extends TestCase {
    protected $mini;
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $dummy=new \stdClass();
        $values=[];
        $this->mini=new MiniLang($dummy,$values);
        //$this->statemachineone->setDebug(true);
    }
}
