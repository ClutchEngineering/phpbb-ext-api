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
use phpbb\group\helper as group_helper;
use clutchengineering\api\auth\api_auth_service;
use clutchengineering\api\util\DateUtil;

use Symfony\Component\HttpFoundation\Request as Request;
use \Symfony\Component\HttpFoundation\Response as Response;
use \Symfony\Component\HttpFoundation\JsonResponse as JsonResponse;

class users {

    protected $config;
    protected $database;
    protected $helper;
    protected $language;
    protected $auth_service;
    protected $group_helper;

    public function __construct(
        config $config,
        database $database,
        helper $helper,
        language $language,
        api_auth_service $auth_service,
        group_helper $group_helper
    ) {
        $this->config = $config;
        $this->database = $database;
        $this->helper = $helper;
        $this->language = $language;
        $this->auth_service = $auth_service;
        $this->group_helper = $group_helper;
    }

    /**
     * Fetch selected user by their identifier.
     * 
     * @param integer $user_id
     * @return boolean|array
     */
    private function get_user_by_id($user_id) {
        if ( 0 === $user_id ) {
            return false;
        }

        $sql = 'SELECT u.user_id, u.username, u.user_email, u.user_regdate, u.user_lastvisit, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, g.group_colour, g.group_name
            FROM ' . USERS_TABLE . ' u
            LEFT JOIN ' . GROUPS_TABLE . ' g ON (u.group_id = g.group_id)
            WHERE ' . $this->database->sql_build_array( 'SELECT', [
            'u.user_id' => $user_id
        ] );

        $result = $this->database->sql_query( $sql );
        $user = $this->database->sql_fetchrow( $result );
        $this->database->sql_freeresult( $result );

        return $user ?: false;
    }

    private function get_user_by_username($username)
    {
        $sql = 'SELECT u.user_id, u.username, u.user_regdate, u.user_lastvisit, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, g.group_colour, g.group_name
            FROM ' . USERS_TABLE . ' u
            LEFT JOIN ' . GROUPS_TABLE . ' g ON (u.group_id = g.group_id)
            WHERE ' . $this->database->sql_build_array('SELECT', [
            'username_clean' => utf8_clean_string($username)
        ]);

        $result = $this->database->sql_query($sql);
        $user = $this->database->sql_fetchrow($result);
        $this->database->sql_freeresult($result);

        return $user ?: false;
    }

    private function format_user_response($user)
    {
        $avatar = $this->get_avatar($user);

        $response = [
            'user_id' => (int)$user['user_id'],
            'username' => $user['username'],
            'registered' => DateUtil::formatDate($user['user_regdate']),
            'last_visit' => DateUtil::formatDate($user['user_lastvisit']),
            'avatar' => $avatar,
        ];
        if (!empty($user['group_colour'])) {
            $response['group_color'] = $user['group_colour'];
        }
        if (!empty($user['group_name'])) {
            $response['group_name'] = $this->group_helper->get_name($user['group_name']);
        }
        // If user contains user_email, add it to the response
        if (!empty($user['user_email'])) {
            $response['email'] = $user['user_email'];
        }
        return $response;
    }

    public function me(Request $request)
    {
        $auth_result = $this->auth_service->authenticate();
        if ($auth_result !== true) {
            return $auth_result; // This will be a JsonResponse with an error
        }

        $user_id = $this->auth_service->get_user_id();
        $user = $this->get_user_by_id($user_id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
        return new JsonResponse($this->format_user_response($user), 200);
    }

    public function user_by_username(Request $request, $username = '')
    {
        if (empty($username)) {
            return new JsonResponse(['error' => 'Username is required'], 400);
        }

        $user = $this->get_user_by_username($username);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
        return new JsonResponse($this->format_user_response($user), 200);
    }

    private function get_avatar($user)
    {
        if (empty($user['user_avatar'])) {
            return null;
        }

        $avatar = [
            'type' => $user['user_avatar_type'],
            'width' => (int)$user['user_avatar_width'],
            'height' => (int)$user['user_avatar_height'],
        ];

        switch ($user['user_avatar_type'])
        {
            case 'avatar.driver.upload':
                $avatar['url'] = generate_board_url() . '/download/file.php?avatar=' . $user['user_avatar'];
                break;

            case 'avatar.driver.gravatar':
                $avatar['url'] = 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($user['user_email']))) . '?s=' . $user['user_avatar_width'];
                break;

            case 'avatar.driver.remote':
                $avatar['url'] = $user['user_avatar'];
                break;

            default:
                return null;
        }

        return $avatar;
    }
}