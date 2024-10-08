<?php

/**
 * @package API
 * @copyright (c) 2024 Daniel James, (c) 2024 Clutch Engineering
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

if ( ! defined( 'IN_PHPBB' ) ) {

    exit;

}

if ( empty( $lang ) || ! is_array( $lang ) ) {

    $lang = [];

}

$lang = array_merge( $lang, [
    'DEFAULT_API_RESPONSE'          => 'Connection established to the API.',
    'DEFAULT_API_GREETING'          => 'Hello, you\'re using the API extension.',
    'INACTIVE_ENDPOINT_RESPONSE'    => 'Endpoint is inactive, please do not use.',
] );