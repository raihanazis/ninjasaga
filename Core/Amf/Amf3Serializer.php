<?php

class Amfphp_Core_Amf_Amf3Serializer
{
    protected $ba;

    public function __construct(Amfphp_Core_Amf_Types_ByteArray $ba)
    {
        $this->ba = $ba;
    }

    public function writeObject($object)
    {
        $this->ba->data .= chr(0x0A); // AMF3 object marker
        $this->writeAmf3AnonymousObject($object);
    }

    protected function writeAmf3AnonymousObject($object)
    {
        $this->ba->data .= chr(0x0B); // Traits info: dynamic anonymous object
        $this->writeAmf3String("");

        foreach ($object as $key => $value) {
            $this->writeAmf3String($key);
            $this->writeAmf3Data($value);
        }

        $this->ba->data .= chr(0x01); // end of dynamic members
    }

    protected function writeAmf3String($string)
    {
        if ($string === "") {
            $this->ba->data .= chr(0x01);
        } else {
            $length = strlen($string);
            $this->ba->data .= $this->encodeU29(($length << 1) | 1);
            $this->ba->data .= $string;
        }
    }

    protected function writeAmf3Data($value)
    {
        if (is_int($value)) {
            $this->ba->data .= chr(0x04); // integer marker
            $this->ba->data .= $this->encodeU29($value);
        } elseif (is_float($value)) {
            $this->ba->data .= chr(0x05); // double marker
            $this->ba->data .= strrev(pack('d', $value));
        } elseif (is_string($value)) {
            $this->ba->data .= chr(0x06); // string marker
            $this->writeAmf3String($value);
        } elseif (is_bool($value)) {
            $this->ba->data .= $value ? chr(0x03) : chr(0x02);
        } elseif ($value === null) {
            $this->ba->data .= chr(0x01);
        } elseif (is_array($value)) {
            $this->ba->data .= chr(0x09); // array marker
            $this->ba->data .= $this->encodeU29((count($value) << 1) | 1);
            $this->writeAmf3String("");
            foreach ($value as $item) {
                $this->writeAmf3Data($item);
            }
        } else {
            // fallback → write as object
            $this->writeObject((array)$value);
        }
    }

    protected function encodeU29($value)
    {
        $value &= 0x1fffffff;
        if ($value < 0x80) return chr($value);
        if ($value < 0x4000) return chr(($value >> 7) | 0x80) . chr($value & 0x7f);
        if ($value < 0x200000) return chr(($value >> 14) | 0x80) . chr((($value >> 7) & 0x7f) | 0x80) . chr($value & 0x7f);
        return chr(($value >> 22) | 0x80) . chr((($value >> 15) & 0x7f) | 0x80) . chr((($value >> 8) & 0x7f) | 0x80) . chr($value & 0xff);
    }
}
?>
