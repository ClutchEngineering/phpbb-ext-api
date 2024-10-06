<?php

namespace clutchengineering\api\acp;

class main_info
{
    public function module()
    {
        return [
            'filename'    => '\clutchengineering\api\acp\main_module',
            'title'        => 'ACP_API_SETTINGS',
            'modes'        => [
                'settings'    => [
                    'title' => 'ACP_API_SETTINGS',
                    'auth' => 'ext_clutchengineering/api && acl_a_board',
                    'cat' => ['ACP_API_SETTINGS']
                ],
            ],
        ];
    }
}