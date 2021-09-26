<?php

namespace eftec\minilang;

class MinLangExp
{
    /** @var MiniLang */
    public $parent;

    /**
     * @param MiniLang $parent
     */
    public function __construct(MiniLang $parent)
    {
        $this->parent = $parent;
    }
    public function when($value1,$comparison,$value2) {
        $f=false;
        $this->parent->addOp('where',$f,$comparison);
        $this->parent->addBinOper($f,'where',false,'string',$value1);
    }


}