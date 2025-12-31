<?php

if (!defined('ABSPATH')) {
    exit;
}

class DBDT_Webhook {
    private $debounce_seconds = 60;

    public function trigger($action = 'unknown') {
        if (!get_option('dbdt_enabled', '1')) {
            return false;
        }

        if ($this->is_debounced()) {
            $this->log($action, false, 'Debounced: triggered too recently');
            return false;
        }

        $token = DBDT_Settings::decrypt_token();
        if (empty($token)) {
            $this->log($action, false, 'No GitHub token configured');
            $this->set_notice('Deploy trigger failed: No GitHub token configured.', false);
            return false;
        }

        $repo = get_option('dbdt_github_repo', 'gaupoit/devbanhmi-blog');
        if (empty($repo)) {
            $this->log($action, false, 'No repository configured');
            return false;
        }

        $url = "https://api.github.com/repos/{$repo}/dispatches";

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.github+json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'DevBanhMi-Deploy-Trigger/1.0'
            ],
            'body' => json_encode([
                'event_type' => 'wordpress_publish',
                'client_payload' => [
                    'action' => $action,
                    'time' => current_time('mysql')
                ]
            ]),
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            $this->log($action, false, $error);
            $this->set_notice('Deploy trigger failed: ' . $error, false);
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 204 || $code === 200) {
            $this->set_debounce();
            $this->log($action, true, "HTTP {$code}");
            $this->set_notice('Deploy triggered successfully! Blog will rebuild shortly.', true);
            return true;
        }

        $this->log($action, false, "HTTP {$code}: {$body}");
        $this->set_notice("Deploy trigger failed: HTTP {$code}", false);
        return false;
    }

    private function is_debounced() {
        $last = get_transient('dbdt_last_trigger');
        return $last !== false;
    }

    private function set_debounce() {
        set_transient('dbdt_last_trigger', time(), $this->debounce_seconds);
    }

    private function log($action, $success, $response) {
        $logs = get_option('dbdt_logs', []);

        $logs[] = [
            'time' => current_time('mysql'),
            'action' => $action,
            'success' => $success,
            'response' => $response
        ];

        if (count($logs) > 10) {
            $logs = array_slice($logs, -10);
        }

        update_option('dbdt_logs', $logs);
    }

    private function set_notice($message, $success) {
        set_transient('dbdt_admin_notice', [
            'message' => $message,
            'success' => $success
        ], 30);
    }
}
