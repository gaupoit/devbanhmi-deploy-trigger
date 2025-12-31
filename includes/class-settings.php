<?php

if (!defined('ABSPATH')) {
    exit;
}

class DBDT_Settings {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'show_notices']);
    }

    public function add_settings_page() {
        add_options_page(
            'Deploy Trigger',
            'Deploy Trigger',
            'manage_options',
            'dbdt-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('dbdt_settings', 'dbdt_github_token', [
            'sanitize_callback' => [$this, 'encrypt_token']
        ]);
        register_setting('dbdt_settings', 'dbdt_github_repo', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('dbdt_settings', 'dbdt_enabled', [
            'sanitize_callback' => 'absint'
        ]);
    }

    public function encrypt_token($token) {
        if (empty($token)) {
            return get_option('dbdt_github_token');
        }
        if (defined('AUTH_KEY')) {
            return base64_encode(openssl_encrypt($token, 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_KEY, 0, 16)));
        }
        return base64_encode($token);
    }

    public static function decrypt_token() {
        $encrypted = get_option('dbdt_github_token', '');
        if (empty($encrypted)) {
            return '';
        }
        if (defined('AUTH_KEY')) {
            return openssl_decrypt(base64_decode($encrypted), 'AES-256-CBC', AUTH_KEY, 0, substr(AUTH_KEY, 0, 16));
        }
        return base64_decode($encrypted);
    }

    public function render_settings_page() {
        $logs = get_option('dbdt_logs', []);
        ?>
        <div class="wrap">
            <h1>Deploy Trigger Settings</h1>

            <form method="post" action="options.php">
                <?php settings_fields('dbdt_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Auto-Deploy</th>
                        <td>
                            <label>
                                <input type="checkbox" name="dbdt_enabled" value="1" <?php checked(get_option('dbdt_enabled', '1'), '1'); ?>>
                                Trigger rebuild when posts are published/updated
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">GitHub Repository</th>
                        <td>
                            <input type="text" name="dbdt_github_repo" value="<?php echo esc_attr(get_option('dbdt_github_repo', 'gaupoit/devbanhmi-blog')); ?>" class="regular-text" placeholder="owner/repo">
                            <p class="description">Format: owner/repository</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">GitHub Token</th>
                        <td>
                            <input type="password" name="dbdt_github_token" value="" class="regular-text" placeholder="<?php echo get_option('dbdt_github_token') ? '••••••••••••' : 'Enter token'; ?>">
                            <p class="description">
                                Create at <a href="https://github.com/settings/tokens" target="_blank">GitHub Settings → Tokens</a>.
                                Needs <code>repo</code> scope. Leave empty to keep existing token.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <hr>

            <h2>Manual Trigger</h2>
            <form method="post">
                <?php wp_nonce_field('dbdt_manual_trigger'); ?>
                <input type="hidden" name="dbdt_manual_trigger" value="1">
                <?php submit_button('Trigger Deploy Now', 'secondary'); ?>
            </form>

            <?php $this->handle_manual_trigger(); ?>

            <hr>

            <h2>Recent Logs</h2>
            <?php if (empty($logs)): ?>
                <p>No webhook attempts yet.</p>
            <?php else: ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($logs) as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log['time']); ?></td>
                                <td><?php echo esc_html($log['action']); ?></td>
                                <td><?php echo $log['success'] ? '<span style="color:green;">Success</span>' : '<span style="color:red;">Failed</span>'; ?></td>
                                <td><code><?php echo esc_html(substr($log['response'], 0, 100)); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" style="margin-top: 10px;">
                    <?php wp_nonce_field('dbdt_clear_logs'); ?>
                    <input type="hidden" name="dbdt_clear_logs" value="1">
                    <?php submit_button('Clear Logs', 'secondary', 'submit', false); ?>
                </form>
                <?php $this->handle_clear_logs(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handle_manual_trigger() {
        if (!isset($_POST['dbdt_manual_trigger'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['_wpnonce'], 'dbdt_manual_trigger')) {
            return;
        }

        $webhook = new DBDT_Webhook();
        $webhook->trigger('manual');
    }

    private function handle_clear_logs() {
        if (!isset($_POST['dbdt_clear_logs'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['_wpnonce'], 'dbdt_clear_logs')) {
            return;
        }

        update_option('dbdt_logs', []);
        echo '<div class="notice notice-success"><p>Logs cleared.</p></div>';
    }

    public function show_notices() {
        $notice = get_transient('dbdt_admin_notice');
        if ($notice) {
            $class = $notice['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
            delete_transient('dbdt_admin_notice');
        }
    }
}
