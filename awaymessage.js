/* Show show_new_message_plugin plugin script */
if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginawaymessage').addClass('tablink');
    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.awaymessage').html(rcmail.gettext('awaymessage','awaymessage')).appendTo(tab);
    
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.awaymessage', function(){ rcmail.goto_url('plugin.awaymessage') }, true);
    button.bind('click', function(e){ return rcmail.command('plugin.awaymessage', this) });
    rcmail.add_element(tab, 'tabs');
  })
}

