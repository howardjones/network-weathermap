<?php

class WMRectangle
{
    public $topLeft;
    public $bottomRight;

    public function __construct($xPoint1, $yPoint1, $xPoint2, $yPoint2)
    {
        // swap points around so that topLeft is actually top-left
        if ($xPoint2 < $xPoint1) {
            $tmp = $xPoint1;
            $xPoint1 = $xPoint2;
            $xPoint2 = $tmp;
        }

        if ($yPoint2 < $yPoint1) {
            $tmp = $yPoint1;
            $yPoint1 = $yPoint2;
            $yPoint2 = $tmp;
        }

        $this->topLeft = new WMPoint($xPoint1, $yPoint1);
        $this->bottomRight = new WMPoint($xPoint2, $yPoint2);
    }

    public function getCentre()
    {
        return new WMPoint(($this->bottomRight->x - $this->topLeft->x) / 2,
            ($this->bottomRight->y - $this->topLeft->y) / 2);
    }

    public function reCentre($new_centre)
    {
        $newX = -$this->width() / 2;
        $newY = -$this->height() / 2;

        $this->translate($newX - $this->topLeft->x, $newY - $this->topLeft->y);
        $this->translate($new_centre->x, $new_centre->y);

        return new WMPoint(($this->bottomRight->x - $this->topLeft->x) / 2,
            ($this->bottomRight->y - $this->topLeft->y) / 2);
    }

    public function identical($otherRect)
    {
        return ($this->topLeft->identical($otherRect->topLeft) && $this->bottomRight->identical($otherRect->bottomRight));
    }

    public function copy()
    {
        return new WMRectangle($this->topLeft->x, $this->topLeft->y, $this->bottomRight->x, $this->bottomRight->y);
    }

    public function translate($deltaX, $deltaY)
    {
        $this->topLeft->translate($deltaX, $deltaY);
        $this->bottomRight->translate($deltaX, $deltaY);
    }

    public function inflate($amount)
    {
        $this->topLeft->translate(-$amount, -$amount);
        $this->bottomRight->translate($amount, $amount);
    }

    public function width()
    {
        return ($this->bottomRight->x - $this->topLeft->x);
    }

    public function height()
    {
        return ($this->bottomRight->y - $this->topLeft->y);
    }

    public function containsPoint($p)
    {
        if ($this->topLeft->x <= $p->x
            && $this->bottomRight->x >= $p->x
            && $this->topLeft->y <= $p->y
            && $this->bottomRight->y >= $p->y
        ) {
            return true;
        }

        return false;
    }

    public function asArray()
    {
        return array($this->topLeft->x, $this->topLeft->y, $this->bottomRight->x, $this->bottomRight->y);
    }

    public function __toString()
    {
        return sprintf("[%sx%s]", $this->topLeft, $this->bottomRight);
    }
}
