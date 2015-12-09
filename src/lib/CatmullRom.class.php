<?php

// Given 4 ordinates and a parameter from 0 to 1, calculate a point on the Catmull Rom spline through them.
class CatmullRom1D
{
    private $aParam;
    private $bParam;
    private $cParam;
    private $dParam;

    public function __construct($point0, $point1, $point2, $point4)
    {
        $this->aParam = - $point0 + 3 * $point1 - 3 * $point2 + $point4;
        $this->bParam = 2 * $point0 - 5 * $point1 + 4 * $point2 - $point4;
        $this->cParam = - $point0 + $point2;
        $this->dParam = 2 * $point1;
    }

    public function calculate($parameter)
    {
        $parameterSquared = $parameter * $parameter;
        $parameterCubed = $parameterSquared * $parameter;

        return ((
                ($this->aParam * $parameterCubed)
                + ($this->bParam * $parameterSquared)
                + ($this->cParam * $parameter)
                + $this->dParam
            ) / 2);
    }
}
