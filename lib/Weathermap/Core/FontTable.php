<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:12
 */

namespace Weathermap\Core;

class FontTable
{
    private $table = array();

    public function init()
    {
        for ($i = 1; $i < 6; $i++) {
            $newFont = new GDFont($i);
            $this->addFont($i, $newFont);
        }
    }

    public function count()
    {
        return sizeof($this->table);
    }

    public function addFont($fontNumber, $font)
    {
        $this->table[$fontNumber] = $font;
    }

    /**
     * isValid - verify if a font number is valid in the current font table
     *
     * @param $fontNumber int Number of font in table
     * @return bool true if font number is for a valid font
     */
    public function isValid($fontNumber)
    {
        if (!isset($this->table[$fontNumber])) {
            return false;
        }

        return $this->table[$fontNumber]->isLoaded();
    }

    /**
     * @param int $fontNumber
     * @return Font
     */
    public function getFont($fontNumber)
    {
        if (!$this->isValid($fontNumber)) {
            wm_warn("Using a non-existent special font ($fontNumber) - falling back to internal GD fonts [WMWARN36]\n");
            return $this->getFont(5);
        }

        return $this->table[$fontNumber];
    }

    public function getList()
    {
        $list = array();

        foreach ($this->table as $fontNumber => $fontObject) {
            $list[$fontNumber] = array("type" => $fontObject->type);
        }

        return $list;
    }

    public function asConfigData()
    {
        $conf = array();

        foreach ($this->table as $fontNumber => $fontObject) {
            $font = $fontObject->asConfigData($fontNumber);
            $conf[] = $font;
        }

        return $conf;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        $output = "";
        if (count($this->table) > 0) {
            foreach ($this->table as $fontNumber => $fontObject) {
                $output .= $fontObject->getConfig($fontNumber);
            }
        }
        $output .= "\n";

        return $output;
    }

    /**
     * @param string $type
     * @param string $file
     * @param int $size
     * @return GDFont|TrueTypeFont
     * @throws WeathermapInternalFail
     */
    public function makeFontObject($type, $file, $size = 0)
    {
        if ($type == "truetype") {
            return new TrueTypeFont($file, $size);
        }

        if ($type == "gd") {
            return new GDFont($file);
        }

        throw new WeathermapInternalFail("Requested non-existent font type");
    }
}
