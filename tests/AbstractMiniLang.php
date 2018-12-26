<?php

namespace eftec\tests;




use eftec\minilang\MiniLang;
use PHPUnit\Framework\TestCase;


abstract class AbstractMiniLang extends TestCase {
    protected $mini;
    public function __construct($name = null, array $data = [], $dataName = '') {
        parent::__construct($name, $data, $dataName);
        $this->mini=new MiniLang([],[]);
        //$this->statemachineone->setDebug(true);
    }
}
