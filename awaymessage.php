<?php 
/*
* Plugin to allow user's away message configuration.
* @package     plugins
* @uses        rcube_plugin
* @author      Ilton Carlos <ilton@vialink.com.br>
* @author      Marcelo Salhab Brogliato <msbrogli@vialink.com.br>
* @author	   Mauricio de Barros Nunes <mnunes@vialink.com.br>
* @author	   Rarylson
* @version     v1.2 (Beta)
*/

class awaymessage extends rcube_plugin {

  public $task = 'settings';

  function init() {
    $this->add_texts('localization/', array('awaymessage'));
    $this->register_action('plugin.awaymessage', array($this, 'awaymessage_init'));
    $this->register_action('plugin.awaymessage-save', array($this, 'awaymessage_save'));
    $this->include_script('awaymessage.js');

    $rcmail = rcmail::get_instance();
    $this->load_config();

    $this->db_select = $rcmail->config->get('awaymessage_db_select');
    $this->db_update = $rcmail->config->get('awaymessage_db_update');
    // Using a DSN config option. This is the Roundcube way of doing this.
    // Using DSN also permit us to set the connection charset.
    $this->dsn = $rcmail->config->get('awaymessage_db_dsn');
    // enable new_link option
    if (is_array($this->dsn) && empty($this->dsn['new_link']))
        $this->dsn['new_link'] = true;
    else if (!is_array($this->dsn) && !preg_match('/\?new_link=true/', $this->dsn))
        $this->dsn .= '?new_link=true';
  }

  function awaymessage_init() {
    $rcmail = rcmail::get_instance();

    $this->register_handler('plugin.body', array($this, 'awaymessage_form'));
    $rcmail->output->set_pagetitle($this->gettext('title'));
    $rcmail->output->send('plugin');
  }

  function awaymessage_form() {
    $rcmail = rcmail::get_instance();
    $login  =  $rcmail->user->get_username();

    // Using DSN and the native way of RoundCube to connect to databases
    $db = rcube_db::factory($this->dsn, '', false);
    $db->set_debug((bool)$rcmail->config->get('sql_debug'));
    $db->db_connect('r');

    $query = $this->db_select;
    $query = str_replace(':login', $db->quote($login, 'text'), $query);

    $res = $db->query($query);
    $data = $db->fetch_assoc($res);

    $table = new html_table(array('cols' => 2, 'class' => 'propform'));

    $table->add('title', $this->gettext('active'));
    if ($data['enable']){ 
        $table->add(null, html::tag('input', array('type' => "checkbox", 'name' => "_status", 'checked' => $data['enable'])));
    } else {
        $table->add(null, html::tag('input', array('type' => "checkbox", 'name' => "_status")));
    }

    $table->add('title', $this->gettext('subject'));
    $table->add(null, html::tag('input', array('type' => "text", 'name' => "_header", "size"=> '62', 'value' => $data['subject'])));

    $table->add('title', $this->gettext('message'));
    $table->add(null, html::tag('textarea', array('type' => "text", 'name' => "_message", "cols" => '60', "rows"=> '10' ), $data['message']));

    $out = html::div(array('id' => 'tittle_message', 'class' => 'boxtitle'), $this->gettext('awaymessage')).
      html::div(array('class' => "boxcontent"), 
      html::tag('fieldset', null, html::tag('legend', null, $this->gettext('title')) . $table->show()) .
      html::div(array('class' => 'formbuttons'),
      html::tag('input', array('type' => "submit", 'class' => "button mainaction", 'value' => $this->gettext('save') , 'name' => "_submit"))
      )
    );

    // update formatting (compatibility with new RoundCube) [rarylson 20130906]
    return html::tag('form',  array (
            'action' => $rcmail->url('plugin.awaymessage-save'),
            'method' => "post",
            'class' => 'propform'),
           $out);
  }

  function awaymessage_save(){
    $this->add_texts('localization');
    $rcmail = rcmail::get_instance();
    $this->register_handler('plugin.body', array($this, 'awaymessage_form'));

    $identity = $rcmail->user->get_identity();
    $login   = $rcmail->user->get_username();

    $save_data = array(
        'subject' => trim(rcube_utils::get_input_value('_header', rcube_utils::INPUT_POST, true)),
        'message' => trim(rcube_utils::get_input_value('_message', rcube_utils::INPUT_POST, true)),
        'status' => rcube_utils::get_input_value('_status', rcube_utils::INPUT_POST, true),
    );


	print_r($save_data);

    //transform as integer value checkbox //
    if ($save_data['status'] == 'on') { $save_data['status'] = 1; }
    else { $save_data['status'] = 0; }

    if ($save_data['status'] && (!$save_data['subject'] || !$save_data['message'])) {
        $rcmail->output->command('display_message', $this->gettext('enableerror'), 'error');
    } else {
        try {
            // Using DSN and the native way of RoundCube to connect to databases
            // [rarylson 20141104]
            $db = rcube_db::factory($this->dsn, '', false);
            $db->set_debug((bool)$rcmail->config->get('sql_debug'));
            $db->db_connect('w');

            $query = $this->db_update;
            $query = str_replace(':login', $db->quote($login, 'text'), $query);
            $query = str_replace(':subject', $db->quote($save_data['subject'], 'text'), $query);
            $query = str_replace(':message', $db->quote($save_data['message'], 'text'), $query);
            $query = str_replace(':status', $db->quote($save_data['status'], 'integer'), $query);

            $res = $db->query($query);
            // This is the good case: 1 row updated
            if (!$db->is_error() && $db->affected_rows($res) == 1) {
                $rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
            }
            else {
                $rcmail->output->command('display_message', $this->gettext('internalerror'), 'error');
            }

        } catch(Exception $e) {
            $rcmail->output->command('display_message', $this->gettext('connecterror'), 'error');
        }
    }
    $rcmail->overwrite_action('plugin.awaymessage');
    $rcmail->output->send('plugin');
  }
}
?>
