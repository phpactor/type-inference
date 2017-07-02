<?php

namespace DTL\TypeInference\Domain;

interface Reflector
{
    public function classMethod(): ClassMethod;

    public function classProperty(): ClassProperty;
}
