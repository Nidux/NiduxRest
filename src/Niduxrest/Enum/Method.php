<?php

/**
 * Hypertext Transfer Protocol (HTTP) Method Registry
 *
 * http://www.iana.org/assignments/http-methods/http-methods.xhtml
 */

namespace Niduxrest\Enum;

enum Method: string
{
    // RFC7231
    case GET = 'GET';
    case HEAD = 'HEAD';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case CONNECT = 'CONNECT';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';

    // RFC3253
    case BASELINE = 'BASELINE';

    // RFC2068
    case LINK = 'LINK';
    case UNLINK = 'UNLINK';

    // RFC3253
    case MERGE = 'MERGE';
    case BASELINECONTROL = 'BASELINE-CONTROL';
    case MKACTIVITY = 'MKACTIVITY';
    case VERSIONCONTROL = 'VERSION-CONTROL';
    case REPORT = 'REPORT';
    case CHECKOUT = 'CHECKOUT';
    case CHECKIN = 'CHECKIN';
    case UNCHECKOUT = 'UNCHECKOUT';
    case MKWORKSPACE = 'MKWORKSPACE';
    case UPDATE = 'UPDATE';
    case LABEL = 'LABEL';

    // RFC3648
    case ORDERPATCH = 'ORDERPATCH';

    // RFC3744
    case ACL = 'ACL';

    // RFC4437
    case MKREDIRECTREF = 'MKREDIRECTREF';
    case UPDATEREDIRECTREF = 'UPDATEREDIRECTREF';

    // RFC4791
    case MKCALENDAR = 'MKCALENDAR';

    // RFC4918
    case PROPFIND = 'PROPFIND';
    case LOCK = 'LOCK';
    case UNLOCK = 'UNLOCK';
    case PROPPATCH = 'PROPPATCH';
    case MKCOL = 'MKCOL';
    case COPY = 'COPY';
    case MOVE = 'MOVE';

    // RFC5323
    case SEARCH = 'SEARCH';

    // RFC5789
    case PATCH = 'PATCH';

    // RFC5842
    case BIND = 'BIND';
    case UNBIND = 'UNBIND';
    case REBIND = 'REBIND';
}
