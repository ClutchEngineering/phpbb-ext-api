<?php

/**
 * @package API
 * @copyright (c) 2024 Daniel James
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace danieltj\api\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface {

    /**
     * @var \phpbb\controller\helper
     */
    protected $helper;

    /**
     * @var \phpbb\template\template
     */
    protected $template;

    /**
     * Constructor
     *
     * @param \phpbb\controller\helper $helper
     * @param \phpbb\template\template $template
     */
    public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template) {

        $this->helper   = $helper;
        $this->template = $template;

    }

    /**
     * Assign functions defined in this class to event listeners in the core
     *
     * @return array
     */
    static public function getSubscribedEvents() {

        return [
            'core.user_setup'  => 'load_languages',
        ];

    }

    /**
     * Load the Acme Demo language file
     *     acme/demo/language/en/demo.php
     *
     * @param \phpbb\event\data $event The event object
     */
    public function load_languages( $event ) {

        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'danieltj/api',
            'lang_set' => 'core',
        ];
        $event[ 'lang_set_ext' ] = $lang_set_ext;

    }

}