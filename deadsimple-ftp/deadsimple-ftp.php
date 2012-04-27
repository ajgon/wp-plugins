<?php
/*
Plugin Name: Deadsimple FTP
Plugin URI: http://github.com/ajgon/wp-plugins/tree/master/deadsimple-ftp
Description: Simple ftp upload support for wordpress. Provides admin menu for FTP server configuration and function <em>wp_deadsimple_ftp</em> for handling it.
Author: Igor Rzegocki
Version: 0.271
Author URI: http://www.rzegocki.pl/
*/

require_once('Thread.php');

function wp_deadsimple_ftp($files, $target_dir = '', $callback_one_file = false, $callback_all_files = false) {
    $options = get_option('deadsimple_ftp_options');
    $destination_dir = $options['deadsimple_ftp_destination_dir'];
    if(empty($destination_dir)) {
        $destination_dir = '/';
    }
    $destination_dir = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array(trim($destination_dir, DIRECTORY_SEPARATOR), trim($target_dir, DIRECTORY_SEPARATOR))));
    $port = $options['deadsimple_ftp_port'];
    if(empty($port)) {
        $port = 21;
    }
    $passive = ((int)$options['deadsimple_ftp_passive'] == 1);
    $files = (array)$files;
    if($options['deadsimple_ftp_ssl']) {
        $stream = ftp_ssl_connect($options['deadsimple_ftp_server'], $port);
    } else {
        $stream = ftp_connect($options['deadsimple_ftp_server'], $port);
    }
    if($stream && ftp_login($stream, $options['deadsimple_ftp_username'], $options['deadsimple_ftp_user_pass'])) {
        ftp_pasv($stream, $passive);
        $thread = new Thread( 'deadsimple_ftp_put' );
        $thread->start( $stream, $files, $destination_dir, $callback_one_file, $callback_all_files );
        return true;
    }
    return false;
}

function deadsimple_ftp_put( $stream, $files, $destination_dir, $callback_one_file, $callback_all_files ) {
    $full_success = true;
    foreach($files as $file) {
        if(!file_exists($file)) continue;

        // Recursively create directories
        $dir_parts = explode(DIRECTORY_SEPARATOR, $destination_dir);
        foreach($dir_parts as $index => $part) {
            if(empty($part)) continue;
            $dir_part = DIRECTORY_SEPARATOR;
            for($i = 0; $i < $index; $i++) {
                if(empty($dir_parts[$i])) continue;
                $dir_part .= $dir_parts[$i] . DIRECTORY_SEPARATOR;
            }
            $dir_part .= $part;
            ftp_mkdir($stream, $dir_part);
        }

        $success = ftp_put( $stream, DIRECTORY_SEPARATOR . trim(implode(DIRECTORY_SEPARATOR, array( trim($destination_dir, DIRECTORY_SEPARATOR), basename($file) )), DIRECTORY_SEPARATOR), $file, FTP_BINARY );
        $full_success = $success && $full_success;

        if($callback_one_file) {
            $callback_one_file($file, $success);
        }
    }
    if($callback_all_files) {
        $callback_all_files($success);
    }
    ftp_close($stream);
}

// ADMIN
function deadsimple_ftp_admin() {
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2><?php _e('Deadsimple FTP: ', 'deadsimple_ftp'); _e('Target FTP server for files upload', 'deadsimple_ftp'); ?></h2>
		<form action="options.php" method="post">
		<?php settings_fields('deadsimple_ftp_options'); ?>
		<?php do_settings_sections(__FILE__); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php _e('Save Changes', 'deadsimple_ftp'); ?>" />
		</p>
		</form>
	</div>
<?php
}

function deadsimple_ftp_admin_menu() {
    add_submenu_page( 'options-general.php', __('Target FTP server for files upload', 'deadsimple_ftp'), __('FTP Server', 'deadsimple_ftp'), 'manage_options', 'deadsimple_ftp', 'deadsimple_ftp_admin' ); 
}


function deadsimple_ftp_set_plugin_meta($links, $file) {
    $plugin = plugin_basename(__FILE__);
    if ($file == $plugin) {
        return array_merge(
            $links,
            array( sprintf( '<a href="options-general.php?page=deadsimple_ftp">%s</a>', __('Server setup', 'deadsimple_ftp') ) )
        );
    }
    return $links;
}

function deadsimple_ftp_server() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_server' name='deadsimple_ftp_options[deadsimple_ftp_server]' size='60' type='text' value='{$options['deadsimple_ftp_server']}' />";
}

function deadsimple_ftp_username() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_username' name='deadsimple_ftp_options[deadsimple_ftp_username]' size='60' type='text' value='{$options['deadsimple_ftp_username']}' />";
}

function deadsimple_ftp_password() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_user_pass' name='deadsimple_ftp_options[deadsimple_ftp_user_pass]' size='60' type='password' value='{$options['deadsimple_ftp_user_pass']}' />";
}

function deadsimple_ftp_port() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_port' name='deadsimple_ftp_options[deadsimple_ftp_port]' size='5' type='text' value='{$options['deadsimple_ftp_port']}' />";
}

function deadsimple_ftp_destination_dir() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_destination_dir' name='deadsimple_ftp_options[deadsimple_ftp_destination_dir]' size='60' type='text' value='{$options['deadsimple_ftp_destination_dir']}' />";
}

function deadsimple_ftp_passive() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_passive' name='deadsimple_ftp_options[deadsimple_ftp_passive]' type='checkbox' value='1' " . ($options['deadsimple_ftp_passive'] == 1 ? 'checked="checked" ' : '') . "/>";
}

function deadsimple_ftp_ssl() {
	$options = get_option('deadsimple_ftp_options');
	echo "<input id='deadsimple_ftp_ssl' name='deadsimple_ftp_options[deadsimple_ftp_ssl]' type='checkbox' value='1' " . ($options['deadsimple_ftp_ssl'] == 1 ? 'checked="checked" ' : '') . "/>";
}

function deadsimple_ftp_section_text() {
}

function deadsimple_ftp_options_list($input) {
	return $input;
}

function deadsimple_ftp_register_settings() {
    register_setting('deadsimple_ftp_options', 'deadsimple_ftp_options', 'deadsimple_ftp_options_list');
    add_settings_section('deadsimple_ftp_section', __('Deadsimple FTP Upload Settings', 'deadsimple_ftp'), 'deadsimple_ftp_section_text', __FILE__);
    add_settings_field('deadsimple_ftp_server', __('FTP server:', 'deadsimple_ftp'), 'deadsimple_ftp_server', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_username', __('FTP username:', 'deadsimple_ftp'), 'deadsimple_ftp_username', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_user_pass', __('Password:', 'deadsimple_ftp'), 'deadsimple_ftp_password', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_port', __('Port:', 'deadsimple_ftp'), 'deadsimple_ftp_port', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_destination_dir', __('Default directory:', 'deadsimple_ftp'), 'deadsimple_ftp_destination_dir', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_passive', __('Passive:', 'deadsimple_ftp'), 'deadsimple_ftp_passive', __FILE__, 'deadsimple_ftp_section');
    add_settings_field('deadsimple_ftp_ssl', __('SSL:', 'deadsimple_ftp'), 'deadsimple_ftp_ssl', __FILE__, 'deadsimple_ftp_section');
}

add_filter( 'plugin_row_meta', 'deadsimple_ftp_set_plugin_meta', 10, 2 );
add_action( 'admin_menu', 'deadsimple_ftp_admin_menu' );
add_action( 'admin_init', 'deadsimple_ftp_register_settings' );
