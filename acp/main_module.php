<?php

namespace clutchengineering\api\acp;

class main_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    protected $config;
    protected $template;
    protected $request;
    protected $user;
    protected $language;

    public function main($id, $mode)
    {
        global $phpbb_container, $language;

        $this->config = $phpbb_container->get('config');
        $this->template = $phpbb_container->get('template');
        $this->request = $phpbb_container->get('request');
        $this->user = $phpbb_container->get('user');
        $this->language = $language;

        $this->language->add_lang('info_acp_api', 'clutchengineering/api');

        $this->tpl_name = 'acp_api_settings';
        $this->page_title = $this->language->lang('ACP_API_SETTINGS');

        add_form_key('clutchengineering_api_settings');

        $this->handle_settings();
    }

    protected function handle_settings()
    {
        if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('clutchengineering_api_settings'))
            {
                trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
            }

            $this->config->set('jwt_secret_key', $this->request->variable('jwt_secret_key', ''));
            $this->config->set('jwt_secret_key_set', '1');
            $this->config->set('clutcheng_api_oauth_redirect_uri', $this->request->variable('clutcheng_api_oauth_redirect_uri', ''));

            trigger_error($this->language->lang('ACP_API_SETTINGS_SAVED') . adm_back_link($this->u_action));
        }

        $this->template->assign_vars([
            'U_ACTION'        => $this->u_action,
            'JWT_SECRET_KEY'  => $this->config['jwt_secret_key'],
            'JWT_SECRET_KEY_SET'  => $this->config['jwt_secret_key_set'],
            'OAUTH_REDIRECT_URI' => $this->config['clutcheng_api_oauth_redirect_uri'],
        ]);
    }
}
