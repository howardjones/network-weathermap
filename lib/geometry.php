<?php 
    /**
     * Utility 'class' for 2D points.
     *
     * we use enough points in various places to make it worth a small class to
     * save some variable-pairs.
     *
     * TODO: Actually USE this, where we can.
     */
    class WMPoint
    {
        var $x, $y;
    
        function WMPoint($x = 0, $y = 0)
        {
            $this->x = $x;
            $this->y = $y;
        }
    
        function VectorTo($p2)
        {
            $v = new WMVector ( $p2->x - $this->x, $p2->y - $this->y );
    
            return $v;
        }
    
        function LineTo($p2)
        {
            $l = new WMLine ( $this->x, $this->y, $p2->x, $p2->y );
    
            return $l;
        }
    
    
        function DistanceToLine($l)
        {
            // TODO: Implement this
        }
    
        function DistanceToLineSegment($l)
        {
            // TODO: Implement this
        }
    
        function DistanceTo($p2)
        {
            $v = $this->VectorTo ( $p2 );
            $d = $v->length ();
    
            return $d;
        }
    
        function AddVector($v, $fraction = 1.0)
        {
            if ($fraction == 0)
                return;
    
            $this->x = $this->x + $fraction * $v->dx;
            $this->y = $this->y + $fraction * $v->dy;
        }
    
        function as_string()
        {
            return sprintf("(%f,%f)", $this->x, $this->y);
        }
    }
    
    /**
     * Utility class for 2D vectors.
     * Mostly used in the VIA calculations
     */
    class WMVector
    {
        var $dx, $dy;
    
        function WMVector($dx = 0, $dy = 0)
        {
            $this->dx = $dx;
            $this->dy = $dy;
        }
    
        function flip()
        {
            $this->dx = - $this->dx;
            $this->dy = - $this->dy;
        }
    
        function get_angle()
        {
            return rad2deg ( atan2 ( - ($this->dy), ($this->dx) ) );
        }
    
        function rotate($angle)
        {
            $points = array ();
            $points [0] = $this->dx;
            $points [1] = $this->dy;
    
            RotateAboutPoint ( $points, 0, 0, $angle );
    
            $this->dx = $points [0];
            $this->dy = $points [1];
        }
    
        function get_normal()
        {
            $len = $this->length ();
    
            $nx1 = 0;
            $ny1 = 0;
    
            if ($len > 0) {
                $nx1 = $this->dy / $len;
                $ny1 = - $this->dx / $len;
            }
    
            return new WMVector ( $nx1, $ny1 );
        }
    
        function normalise()
        {
            $len = $this->length ();
            if ($len > 0) {
                $this->dx = $this->dx / $len;
                $this->dy = $this->dy / $len;
            }
        }
    
        function slength()
        {
            if (($this->dx == 0) && ($this->dy == 0)) {
                return 0;
            }
            $slen = ($this->dx) * ($this->dx) + ($this->dy) * ($this->dy);
    
            return $slen;
        }
    
        function length()
        {
            return (sqrt ( $this->slength () ));
        }
    
        function as_string()
        {
            return sprintf("[%f,%f]", $this->dx, $this->dy);
        }
    }
    
    class WMRectangle
    {
        var $topleft;
        var $bottomright;
    
        function WMRectangle($x1,$y1, $x2, $y2)
        {
            if($x2<$x1) {
                $tmp = $x1;
                $x1 = $x2;
                $x2 = $tmp;
            }
    
            if($y2<$y1) {
                $tmp = $y1;
                $y1 = $y2;
                $y2 = $tmp;
            }
    
            $topleft = new WMPoint($x1,$y1);
            $bottomright = new WMPoint($x2,$y2);
        }
    
        function width()
        {
            return ($this->bottomright->x - $this->topleft->x);
        }
    
        function height()
        {
            return ($this->bottomright->y - $this->topleft->y);
        }
    }
    
    /**
     * A Line is simply a Vector that passes through a Point
     */
    class WMLine
    {
        var $point;
        var $vector;
    
        function WMLine($p, $v)
        {
            $this->point = $p;
            $this->vector = $v;
        }
    }
    class WMLineSegment
    {
        var $p1, $p2;
        var $vector;
    
        function WMLineSegment($p1, $p2)
        {
            $this->p1 = $p1;
            $this->p2 = $p2;
    
            $this->vector = new WMVector ( $p2->x - $p1->x, $p2->y - $p1->y );
        }
    }
