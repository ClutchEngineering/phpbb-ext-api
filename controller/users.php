<?php

/**
 * @package API
 * @copyright (c) 2024 Daniel James, (c) 2024 Clutch Engineering
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace clutchengineering\api\controller;

use phpbb\config\config;
use phpbb\db\driver\driver_interface as database;
use phpbb\controller\helper;
use phpbb\language\language;
use clutchengineering\api\auth\api_auth_service;

use \Symfony\Component\HttpFoundation\Response as Response;
use \Symfony\Component\HttpFoundation\JsonResponse as JsonResponse;

class users {

    protected $config;
    protected $database;
    protected $helper;
    protected $language;
    protected $auth_service;

    public function __construct(
        config $config,
        database $database,
        helper $helper,
        language $language,
        api_auth_service $auth_service
    ) {
        $this->config = $config;
        $this->database = $database;
        $this->helper = $helper;
        $this->language = $language;
        $this->auth_service = $auth_service;
    }

    /**
     * Fetch selected user.
     * 
     * @param integer $user_id
     * @return boolean|array
     */
    private function get_user( $user_id = 0 ) {

        if ( 0 === $user_id ) {

            return false;
        
        }

        $sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE ' . $this->database->sql_build_array( 'SELECT', [
            'user_id' => $user_id
        ] );

        $result = $this->database->sql_query( $sql );
        $user = $this->database->sql_fetchrow( $result );
        $this->database->sql_freeresult( $result );

        if ( NULL === $user ) {

            return false;

        }

        return $user;

    }

    public function endpoint(Request $request, $user_id = 0)
    {
        $auth_result = $this->auth_service->authenticate();
        if ($auth_result !== true) {
            return $auth_result; // This will be a JsonResponse with an error
        }

        // Your existing endpoint logic here
        $response = [
            'message' => $this->language->lang('DEFAULT_API_RESPONSE'),
            'status' => 200,
            'data' => [
                'user_id' => (int) $user_id,
                'user' => []
            ]
        ];

        $user = $this->get_user($user_id);

        if (false !== $user) {
            $response['data']['user'] = [
                'user_id' => $user['user_id'],
                'user_name' => $user['username'],
                'user_email' => $user['user_email'],
            ];
        }

        return new JsonResponse($response, 200);
    }

}