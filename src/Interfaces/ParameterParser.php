<?php

namespace Koochik\QueryHall\Interfaces;

interface ParameterParser
{
    public function pars($rawParameters): array;
}
