<?php
if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
    'ACP_API_SETTINGS'			=> 'API Settings',
    'ACP_JWT_SECRET_KEY'		=> 'JWT Secret Key',
    'ACP_JWT_SECRET_KEY_EXPLAIN'=> 'Enter a secure random string to use as the JWT secret key.',
    'ACP_JWT_SECRET_KEY_SET'	=> 'JWT Secret Key is set.',
    'ACP_JWT_SECRET_KEY_NOT_SET'=> 'Warning: JWT Secret Key is not set. Please set it for secure API functionality.',
    'ACP_API_SETTINGS_SAVED'	=> 'API settings have been saved successfully.',
    'ACP_API_SETTING_SAVED'     => 'API settings have been saved successfully.',
]);