<?php
/**
 * @package Hello_Dolly
 * @version 1.6
 */
/*
Plugin Name: RSS Auth Token
Plugin URI: https://github.com/ajgon/wp-plugins/rss-token
Description: Generates a token for every user which allows him to access to RSS feed even if he is signed out.
Author: Igor Rzegocki
Version: 1.0
Author URI: http://rzegocki.pl/
*/

add_action('admin_menu', 'rss_token_create_menu');
add_action('admin_init', 'rss_token_register_setting' );
add_action('wp_loaded', 'rss_token_validate');
add_action('init', 'rss_token_init_default');
add_filter('feed_link', 'rss_token_feed_link');

function rss_token_init_default() {
    if (!get_option(rss_token_field_name())) {
        add_option(rss_token_field_name(), sha1(uniqid() . time() . microtime()));
    }
}

function rss_token_feed_link($feed_link) {
    $token = get_option(rss_token_field_name());
    if ($token) {
        $char = preg_match('/\?[^\/]*$/', $feed_link) ? '&' : '?';
        return $feed_link . $char . 'token=' . $token;
    }
    return $feed_link;
}

function rss_token_field_name($user_id = null) {
    if (is_null($user_id)) {
        $user = wp_get_current_user();
        return 'rss-token-' . $user->ID;
    }
    return 'rss-token-' . (int)($user_id);
}

function rss_token_create_menu() {
    add_submenu_page('tools.php', 'RSS Token', 'RSS Token', 'edit_posts', 'rss-token', 'rss_token_page');
}

function rss_token_validate() {
    global $wp_query;
    $users = get_users();


    if (isset($_GET['feed'])) {
        foreach ($users as $user) {
            if (isset($_GET['token']) && get_option(rss_token_field_name($user->ID)) == $_GET['token']) {
                $wp_query = new WP_Query('posts_per_page=-1');
                do_feed();
                die;
            }
        }
    }
}

function rss_token_register_setting() {
    register_setting( 'rss-token', rss_token_field_name() );
    rss_token_init_default();
}

function rss_token_page() {
?>
<div class="wrap">
<h2>RSS Token</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'rss-token' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Token</th>
        <td><input type="text" id="rss-token" name="<?php echo rss_token_field_name(); ?>" value="<?php echo get_option(rss_token_field_name()); ?>" style="max-width: 500px; width: 100%" /></td>
        </tr>
    </table>

    <p class="submit">
    <input type="button" class="button" value="<?php _e('Regenerate token'); ?>" id="regenerate-token" />
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
</form>

<script type="text/javascript">
(function() {
    function regenerateToken() {
        var token = '';
        for(var i = 0; i < 40; i += 1) {
            token += Math.floor(Math.random() * 15 + 1).toString(16);
        };
        document.getElementById('rss-token').value = token;
    }
    function attachEvent(item, name, f) {
        if (item.addEventListener) {
            item.addEventListener(name, f);
        } else {
            item.attachEvent('on' + name, f);
        }
    }
    attachEvent(document.getElementById('regenerate-token'), 'click', regenerateToken);
}())
</script>

</div>
<?php } ?>
