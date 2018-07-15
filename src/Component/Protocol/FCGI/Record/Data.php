<?php
/**
 * @author Alexander.Lisachenko
 * @date 08.09.2015
 */

namespace SwooleGlue\Component\Protocol\FCGI\Record;

use SwooleGlue\Component\Protocol\FCGI\FCGI;
use SwooleGlue\Component\Protocol\FCGI\Record;

/**
 * Data binary stream
 *
 * FCGI_DATA is a second stream record type used to send additional data to the application.
 */
class Data extends Record {
    public function __construct($contentData = '') {
        $this->type = FCGI::DATA;
        $this->setContentData($contentData);
    }
}
