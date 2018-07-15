<?php
namespace SwooleGlue\Component\Protocol\FCGI;

/**
 * Utility class to simplify parsing of FCGI protocol data
 */
class FrameParser {
    /**
     * Mapping of constants to the classes
     *
     * @var array
     */
    protected static $classMapping = [
        FCGI::BEGIN_REQUEST => Record\BeginRequest::class,
        FCGI::ABORT_REQUEST => Record\AbortRequest::class,
        FCGI::END_REQUEST => Record\EndRequest::class,
        FCGI::PARAMS => Record\Params::class,
        FCGI::STDIN => Record\Stdin::class,
        FCGI::STDOUT => Record\Stdout::class,
        FCGI::STDERR => Record\Stderr::class,
        FCGI::DATA => Record\Data::class,
        FCGI::GET_VALUES => Record\GetValues::class,
        FCGI::GET_VALUES_RESULT => Record\GetValuesResult::class,
        FCGI::UNKNOWN_TYPE => Record\UnknownType::class,
    ];

    /**
     * Checks if the buffer contains a valid frame to parse
     *
     * @param string $buffer Binary buffer
     *
     * @return bool
     */
    public static function hasFrame($buffer) {
        $bufferLength = strlen($buffer);
        if ($bufferLength < FCGI::HEADER_LEN) {
            return false;
        }

        $fastInfo = unpack(FCGI::HEADER_FORMAT, $buffer);
        if ($bufferLength < FCGI::HEADER_LEN + $fastInfo['contentLength'] + $fastInfo['paddingLength']) {
            return false;
        }

        return true;
    }

    /**
     * Parses a frame from the binary buffer
     *
     * @param string $buffer Binary buffer
     *
     * @return Record One of the corresponding FCGI record
     */
    public static function parseFrame(&$buffer) {
        $bufferLength = strlen($buffer);
        if ($bufferLength < FCGI::HEADER_LEN) {
            throw new \RuntimeException("Not enough data in the buffer to parse");
        }
        $recordHeader = unpack(FCGI::HEADER_FORMAT, $buffer);
        $recordType = $recordHeader['type'];
        if (!isset(self::$classMapping[$recordType])) {
            throw new \DomainException("Invalid FCGI record type {$recordType} received");
        }

        /** @var Record $className */
        $className = self::$classMapping[$recordType];
        $record = $className::unpack($buffer);


        $offset = FCGI::HEADER_LEN + $record->getContentLength() + $record->getPaddingLength();
        $buffer = substr($buffer, $offset);

        return $record;
    }
}
