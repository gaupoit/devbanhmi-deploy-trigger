<?php
/**
 * Plugin Name: DevBanhMi Deploy Trigger
 * Plugin URI: https://devbanhmi.com
 * Description: Triggers GitHub Actions to rebuild the Astro blog when posts are published or updated.
 * Version: 1.0.0
 * Author: Paul
 * Author URI: https://devbanhmi.com
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DBDT_VERSION', '1.0.0');
define('DBDT_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once DBDT_PLUGIN_DIR . 'includes/class-settings.php';
require_once DBDT_PLUGIN_DIR . 'includes/class-webhook.php';

class DevBanhMi_Deploy_Trigger {
    private static $instance = null;
    private $settings;
    private $webhook;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = new DBDT_Settings();
        $this->webhook = new DBDT_Webhook();

        add_action('publish_post', [$this, 'on_post_publish'], 10, 2);
        add_action('edit_post', [$this, 'on_post_edit'], 10, 2);
        add_action('trash_post', [$this, 'on_post_trash']);
    }

    public function on_post_publish($post_id, $post) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        $this->webhook->trigger('publish');
    }

    public function on_post_edit($post_id, $post) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if ($post->post_status !== 'publish') {
            return;
        }
        $this->webhook->trigger('edit');
    }

    public function on_post_trash($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_status === 'publish') {
            $this->webhook->trigger('trash');
        }
    }
}

add_action('plugins_loaded', function() {
    DevBanhMi_Deploy_Trigger::get_instance();
});

register_activation_hook(__FILE__, function() {
    add_option('dbdt_github_repo', 'gaupoit/devbanhmi-blog');
    add_option('dbdt_enabled', '1');
    add_option('dbdt_logs', []);
});

register_deactivation_hook(__FILE__, function() {
    delete_transient('dbdt_last_trigger');
});
