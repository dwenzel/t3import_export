<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 16:01
 */

namespace CPSIT\T3importExport\Domain\Model;


class XMLStream extends DataStream
{
    public function generateOutput($persist = false)
    {
        $buffer = '';
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('xmlStream');

        foreach ($this->context as $key => $sub) {
            $this->xmlrecursive($xml, $key, $sub);
        }

        $xml->endElement();
        $xml->endDocument();

        $buffer = $xml->outputMemory();

        if ($persist) {
            $this->outputBuffer = $buffer;
        }
        return $buffer;
    }

    private function xmlrecursive(\XMLWriter $xml, $key, $value) {
        if (is_array($value)) {
            $xml->startElement($key);
            foreach ($value as $key => $sub) {
                $this->xmlrecursive($xml, $key, $sub);
            }
            $xml->endElement();
        } else {
            $xml->startElement($key);
            $xml->writeRaw($value);
            $xml->endElement();
        }
    }
}