<?php
// Git checkouts may use LF or CRLF. Each query must contain exactly one CREATE.
return preg_split( '/;[ \t]*\r?\n(?:\r?\n)*/', file_get_contents( __DIR__ . '/core-schema.sql' ), -1, PREG_SPLIT_NO_EMPTY );
