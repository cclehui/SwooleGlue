<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace SwooleGlue\Component\Protocol\FCGI\Record;

use SwooleGlue\Component\Protocol\FCGI\FCGI;
use SwooleGlue\Component\Protocol\FCGI\Record;

/**
 * Stdout binary stream
 *
 * FCGI_STDOUT is a stream record for sending arbitrary data from the application to the Web server
 */
class Stdout extends Record {
    public function __construct($contentData = '') {
        $this->type = FCGI::STDOUT;
        $this->setContentData($contentData);
    }
}
