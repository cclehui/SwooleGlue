<?php

namespace SwooleGlue\Component\Protocol\FCGI;

class FCGI {
    /**
     * Number of bytes in a FCGI_Header.  Future versions of the protocol
     * will not reduce this number.
     */
    const HEADER_LEN = 8;

    /**
     * Format of FCGI_HEADER for unpacking in PHP
     */
    const HEADER_FORMAT = "Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/Creserved";

    /**
     * Value for version component of FCGI_Header
     */
    const VERSION_1 = 1;

    /**
     * Values for type component of FCGI_Header
     * 请求包的 type
     */
    const BEGIN_REQUEST = 1;
    const ABORT_REQUEST = 2;
    const END_REQUEST = 3;
    const PARAMS = 4;
    const STDIN = 5;
    const STDOUT = 6;
    const STDERR = 7;
    const DATA = 8;
    const GET_VALUES = 9;
    const GET_VALUES_RESULT = 10;
    const UNKNOWN_TYPE = 11;

    /**
     * Value for requestId component of FCGI_Header
     */
    const NULL_REQUEST_ID = 0;

    /**
     * Mask for flags component of FCGI_BeginRequestBody
     */
    const KEEP_CONN = 1;

    /**
     * Values for role component of FCGI_BeginRequestBody
     *  角色定义
     */
    const RESPONDER = 1;
    const AUTHORIZER = 2;
    const FILTER = 3;

    /**
     * Values for protocolStatus component of FCGI_EndRequestBody
     * 结束请求的包的 的protocolStatus 状态定义
     */
    const REQUEST_COMPLETE = 0; //请求的正常结束
    const CANT_MPX_CONN = 1; //拒绝新请求。
    const OVERLOADED = 2;
    const UNKNOWN_ROLE = 3;

}
