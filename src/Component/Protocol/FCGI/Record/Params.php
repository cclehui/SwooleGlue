<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace SwooleGlue\Component\Protocol\FCGI\Record;

use SwooleGlue\Component\Protocol\FCGI\FCGI;
use SwooleGlue\Component\Protocol\FCGI\Record;


/**
 * Params request record
 */
class Params extends Record {
    /**
     * List of params
     *
     * @var array
     */
    protected $values = array();

    /**
     * Constructs a param request
     *
     * @param array $values
     */
    public function __construct(array $values = array()) {
        $this->type = FCGI::PARAMS;
        $this->values = $values;
        $this->setContentData($this->packPayload());
    }

    /**
     * Returns an associative list of parameters
     *
     * @return array
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * Method to unpack the payload for the record
     *
     * @param Record|Params $self Instance of current frame
     * @param string $data Binary data
     *
     * @return Record
     */
    protected static function unpackPayload(Record $self, $data) {
        $currentOffset = 0;
        do {
            list($nameLengthHigh) = array_values(unpack('CnameLengthHigh', $data));
            $isLongName = ($nameLengthHigh >> 7 == 1);
            $valueOffset = $isLongName ? 4 : 1;

            list($valueLengthHigh) = array_values(unpack('CvalueLengthHigh', substr($data, $valueOffset)));
            $isLongValue = ($valueLengthHigh >> 7 == 1);
            $dataOffset = $valueOffset + ($isLongValue ? 4 : 1);

            $formatParts = array(
                $isLongName ? 'NnameLength' : 'CnameLength',
                $isLongValue ? 'NvalueLength' : 'CvalueLength',
            );
            $format = join('/', $formatParts);
            list(
                $nameLength,
                $valueLength
                ) = array_values(unpack($format, $data));

            // Clear top bit for long record
            $nameLength &= ($isLongName ? 0x7fffffff : 0x7f);
            $valueLength &= ($isLongValue ? 0x7fffffff : 0x7f);

            list($nameData, $valueData) = array_values(unpack(
                "a{$nameLength}nameData/a{$valueLength}valueData",
                substr($data, $dataOffset)
            ));

            $self->values[$nameData] = $valueData;

            $keyValueLength = $dataOffset + $nameLength + $valueLength;
            $data = substr($data, $keyValueLength);
            $currentOffset += $keyValueLength;
        } while ($currentOffset < $self->getContentLength());

        return $self;
    }

    /**
     * Implementation of packing the payload
     *
     * @return string
     */
    protected function packPayload() {
        $payload = '';
        foreach ($this->values as $nameData => $valueData) {
            $nameLength = strlen($nameData);
            $valueLength = strlen($valueData);
            $isLongName = $nameLength > 127;
            $isLongValue = $valueLength > 127;
            $formatParts = array(
                $isLongName ? 'N' : 'C',
                $isLongValue ? 'N' : 'C',
                "a{$nameLength}",
                "a{$valueLength}"
            );
            $format = join('', $formatParts);

            $payload .= pack(
                $format,
                $isLongName ? ($nameLength | 0x80000000) : $nameLength,
                $isLongValue ? ($valueLength | 0x80000000) : $valueLength,
                $nameData,
                $valueData
            );
        }

        return $payload;
    }
}
