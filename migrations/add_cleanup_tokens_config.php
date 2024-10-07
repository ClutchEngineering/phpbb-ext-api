<?php

namespace clutchengineering\api\migrations;

class add_cleanup_tokens_config extends \phpbb\db\migration\migration
{
   public function effectively_installed()
   {
      return isset($this->config['clutcheng_api_cleanup_tokens_gc']);
   }

   static public function depends_on()
   {
      return array('\phpbb\db\migration\data\v310\dev');
   }

   public function update_data()
   {
      return array(
         array('config.add', array('clutcheng_api_cleanup_tokens_last_gc', 0)), // last run
         array('config.add', array('clutcheng_api_cleanup_tokens_gc', (60 * 60 * 24))), // seconds between run; 1 day
      );
   }
}