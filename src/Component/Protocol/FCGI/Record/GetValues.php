<?php
/**
 * @author Alexander.Lisachenko
 * @date 14.07.2014
 */

namespace SwooleGlue\Component\Protocol\FCGI\Record;

use SwooleGlue\Component\Protocol\FCGI\FCGI;
use SwooleGlue\Component\Protocol\FCGI\Record;

/**
 * GetValues API
 *
 * The Web server can query specific variables within the application.
 * The server will typically perform a query on application startup in order to to automate certain aspects of
 * system configuration.
 *
 * The application responds by sending a record {FCGI_GET_VALUES_RESULT, 0, ...} with the values supplied.
 * If the application doesn't understand a variable name that was included in the query, it omits that name from
 * the response.
 *
 * FCGI_GET_VALUES is designed to allow an open-ended set of variables.
 *
 * The initial set provides information to help the server perform application and connection management:
 *   FCGI_MAX_CONNS:  The maximum number of concurrent transport connections this application will accept,
 *                    e.g. "1" or "10".
 *   FCGI_MAX_REQS:   The maximum number of concurrent requests this application will accept, e.g. "1" or "50".
 *   FCGI_MPXS_CONNS: "0" if this application does not multiplex connections (i.e. handle concurrent requests
 *                    over each connection), "1" otherwise.
 */
class GetValues extends Params {

    /**
     * Constructs a request
     *
     * @param array $keys List of keys to receive
     */
    public function __construct(array $keys = array()) {
        parent::__construct(array_fill_keys($keys, ''));
        $this->type = FCGI::GET_VALUES;
    }
}
