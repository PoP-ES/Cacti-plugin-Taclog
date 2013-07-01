<?php

function plugin_init_taclog() {
	global $plugin_hooks;
	$plugin_hooks['config_arrays']['taclog'] = 'taclog_config_arrays';
	$plugin_hooks['draw_navigation_text']['taclog'] = 'taclog_draw_navigation_text';
	$plugin_hooks['config_settings']['taclog'] = 'taclog_config_settings';
	$plugin_hooks['top_header_tabs']['taclog'] = 'taclog_show_tab';
	$plugin_hooks['top_graph_header_tabs']['taclog'] = 'taclog_show_tab';
        $plugin_hooks['top_graph_refresh']['taclog'] = 'taclog_graph_refresh';
}

function taclog_graph_refresh(){
        return '';
}

function taclog_version () {
	return array( 'name' 	=> 'taclogplugin',
			'version' 	=> '1.0',
			'longname'	=> 'TaclogPlugin',
			'author'	=> 'PoP-ES/RNP',
			'homepage'	=> 'http://www.pop-es.rnp.br',
			'email'		=> '',
			'url'		=> 'http://cactiusers.org/cacti/versions.php'
			);
}

function taclog_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu;

	$user_auth_realms[69]='taclog Plugin';
	$user_auth_realm_filenames['taclog.php'] = 69;
	$user_auth_realm_filenames['whois.php'] = 69;
	$user_auth_realm_filenames['search.php'] = 69;
	$user_auth_realm_filenames['cancel.php'] = 69;
}
function taclog_draw_navigation_text ($nav) {
	$nav["taclog.php:"] = array("title" => "taclog Plugin", "mapping" => "index.php:", "url" => "taclog.php", "level" => "1");
	return $nav;
}

function taclog_config_settings () {
	global $settings, $tabs;
	$tabs["taclog"] = "taclog";
	$temp = array(
		"taclog_database_header" => array(
		"friendly_name" => "Database Options",
		"method" => "spacer",
		),
			"taclog_dbType" => array(
			"friendly_name" => "Database Type",
			"description" => "Database Type. Eg.: mysql, pgsql",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "pgsql"
		),
			"taclog_dbUser" => array(
			"friendly_name" => "Database User",
			"description" => "Database Username",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "taclog"
		),
			"taclog_dbPass" => array(
			"friendly_name" => "Database Password",
			"description" => "Database Password",
			"method" => "textbox_password",
			"max_length" => "255"
		),
			"taclog_dbHost" => array(
			"friendly_name" => "Database Host",
			"description" => "Database Hostname",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "localhost"
		),
			"taclog_dbPort" => array(
			"friendly_name" => "Database Port",
			"description" => "Database Port",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "5432"
		),
			"taclog_dbName" => array(
			"friendly_name" => "Database Name",
			"description" => "Database Name",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "taclog"
		),
		"taclog_header" => array(
		 	"friendly_name" => "taclog Options",
			"method" => "spacer",
		),
			"taclog_fields" => array(
			"friendly_name" => "Fields",
			"description" => "Name of the Fields to search",
			"method" => "textbox",
			"max_length" => 255,
			"default" => "*"
		),

	);
	if (isset($settings["taclog"]))
		$settings["taclog"] = array_merge($settings["taclog"], $temp);
    	else
 	        $settings["taclog"]=$temp;
}

function taclog_show_tab () {
	global $config, $user_auth_realms, $user_auth_realm_filenames;
	$realm_id2 = 0;
	//make sure user has rights to tab
	if (isset($user_auth_realm_filenames{basename('taclog.php')})) {
		$realm_id2 = $user_auth_realm_filenames{basename('taclog.php')};
	}
	if ((db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"] . "' and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {
		print '<a href="' . $config['url_path'] . 'plugins/taclog/taclog.php"><img src="' . $config['url_path'] . 'plugins/taclog/images/tab_taclog' . ((substr(basename($_SERVER["PHP_SELF"]),0,11) == "taclog.php") ? "_down": "") . '.gif" alt="taclog" align="absmiddle" border="0"></a>';
	}
}
