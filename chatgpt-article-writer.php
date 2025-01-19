<?php
/*
Plugin Name: ChatGPT Article Writer
Description: Generate WordPress posts using ChatGPT.
Version: 1.0.0
Author: Dujon Pratt
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ChatGPT_Article_Writer {

    public function run() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_articles', array($this, 'generate_articles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_gutenberg_script'));
        add_action('wp_ajax_generate_article_for_post', array($this, 'handle_generate_article_ajax'));
    
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_custom_top_toolbar_script'));

    }

    public function register_settings() {
        register_setting('chatgpt_writer_group', 'chatgpt_api_key', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'validate_api_key'),
            'default' => ''
        ));
    }

    public function validate_api_key($api_key) {
        $api_key = trim($api_key);

        if (empty($api_key)) {
            add_settings_error(
                'chatgpt_api_key',
                'chatgpt_api_key_error',
                __('API Key cannot be empty.', 'chatgpt-article-writer'),
                'error'
            );
            return '';
        }

        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            )
        ));

        if (is_wp_error($response)) {
            add_settings_error(
                'chatgpt_api_key',
                'chatgpt_api_key_error',
                __('Error validating API Key: ', 'chatgpt-article-writer') . $response->get_error_message(),
                'error'
            );
            return '';
        }

        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code == 200) {
            add_settings_error(
                'chatgpt_api_key',
                'chatgpt_api_key_success',
                __('API Key is valid.', 'chatgpt-article-writer'),
                'updated'
            );
            return $api_key;
        } elseif ($http_code == 401) {
            add_settings_error(
                'chatgpt_api_key',
                'chatgpt_api_key_error',
                __('Invalid API Key.', 'chatgpt-article-writer'),
                'error'
            );
            return '';
        } else {
            add_settings_error(
                'chatgpt_api_key',
                'chatgpt_api_key_error',
                __('Unexpected error validating API Key. HTTP Code: ', 'chatgpt-article-writer') . $http_code,
                'error'
            );
            return '';
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('ChatGPT Article Writer', 'chatgpt-article-writer'),
            __('ChatGPT Writer', 'chatgpt-article-writer'),
            'manage_options',
            'chatgpt-article-writer',
            array($this, 'create_admin_page'),
            'dashicons-edit',
            6
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_chatgpt-article-writer') {
            return;
        }

        wp_enqueue_script(
            'chatgpt-article-writer-script',
            plugins_url('/chatgpt-article-writer.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('chatgpt-article-writer-script', 'chatgptArticleWriter', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatgpt_generate_articles_nonce'),
        ));
    }

    public function create_admin_page() {
        $post_types = get_post_types(array('public' => true), 'objects');
        ?>
        <div class="wrap">
            <h1><?php _e('ChatGPT Article Writer v2', 'chatgpt-article-writer'); ?></h1>
            <?php settings_errors(); ?>
            <form id="chatgpt-settings-form" method="post" action="options.php">
                <?php
                settings_fields('chatgpt_writer_group');
                do_settings_sections('chatgpt_writer_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('ChatGPT API Key', 'chatgpt-article-writer'); ?></th>
                        <td>
                            <input type="text" name="chatgpt_api_key" value="<?php echo esc_attr(get_option('chatgpt_api_key')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'chatgpt-article-writer')); ?>
            </form>

            <h2><?php _e('Generate Articles', 'chatgpt-article-writer'); ?></h2>
            <form id="add-prompt-form">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Select Post Type', 'chatgpt-article-writer'); ?></th>
                        <td>
                            <select id="post-type" name="post_type">
                                <?php foreach ($post_types as $post_type): ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>">
                                        <?php echo esc_html($post_type->labels->singular_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Article Prompt', 'chatgpt-article-writer'); ?></th>
                        <td>
                            <input type="text" id="article-prompt" name="article_prompt" placeholder="<?php esc_attr_e('Enter your prompt here', 'chatgpt-article-writer'); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <button type="button" id="add-prompt" class="button button-secondary"><?php _e('Add Prompt', 'chatgpt-article-writer'); ?></button>
            </form>

            <h2><?php _e('Prompts List', 'chatgpt-article-writer'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post Type', 'chatgpt-article-writer'); ?></th>
                        <th><?php _e('Prompt', 'chatgpt-article-writer'); ?></th>
                        <th><?php _e('Actions', 'chatgpt-article-writer'); ?></th>
                    </tr>
                </thead>
                <tbody id="prompts-list">
                </tbody>
            </table>
            <button type="button" id="generate-articles" class="button button-primary"><?php _e('Generate Articles', 'chatgpt-article-writer'); ?></button>
            <div id="article-content"></div>
        </div>
        <?php
    }

    public function generate_articles() {
        check_ajax_referer('chatgpt_generate_articles_nonce', 'security');
    
        $api_key = trim(get_option('chatgpt_api_key'));
        if (empty($api_key)) {
            wp_send_json_error(__('API Key is missing. Please set it in the settings.', 'chatgpt-article-writer'));
            return;
        }
    
        $prompts_list = $_POST['prompts_list'] ?? [];
        if (empty($prompts_list)) {
            wp_send_json_error(__('No prompts provided.', 'chatgpt-article-writer'));
            return;
        }
    
        $results = [];
        foreach ($prompts_list as $item) {
            $post_type = sanitize_text_field($item['postType']);
            $prompt = sanitize_text_field($item['prompt']);
    
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-3.5-turbo', // Ensure you're using a valid chat model
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are a helpful assistant.'),
                        array('role' => 'user', 'content' => $prompt),
                    ),
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                )),
            ));
    
            if (is_wp_error($response)) {
                wp_send_json_error(__('Error: ' . $response->get_error_message(), 'chatgpt-article-writer'));
                return;
            }
    
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
    
            // Debug response
            error_log('OpenAI API Response: ' . print_r($data, true));
    
            if (empty($data['choices'][0]['message']['content'])) {
                $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
                wp_send_json_error(__('Invalid API response: ' . $error_message, 'chatgpt-article-writer'));
            }
    
            $article_content = $data['choices'][0]['message']['content'];
    
            $post_id = wp_insert_post(array(
                'post_type' => $post_type,
                'post_title' => wp_strip_all_tags($prompt),
                'post_content' => $article_content,
                'post_status' => 'publish',
            ));
    
            if (is_wp_error($post_id)) {
                wp_send_json_error($post_id->get_error_message());
            }
    
            $results[] = array('post_id' => $post_id, 'title' => $prompt);
        }
    
        wp_send_json_success($results);
    }
    
    public function enqueue_custom_top_toolbar_script() {
        error_log('enqueue_custom_top_toolbar_script called');
        wp_enqueue_script(
            'custom-top-toolbar-script',
            plugins_url('/toolbar-button.js', __FILE__), // Ensure this path is correct
            array('wp-edit-post', 'wp-element', 'wp-components', 'wp-data'),
            '1.0.0',
            true
        );
    }
    
    public function handle_generate_article_ajax() {
        check_ajax_referer('generate_article_nonce', 'security');
    
        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'post') {
            wp_send_json_error(__('Invalid post ID.', 'chatgpt-article-writer'));
        }
    
        $api_key = get_option('chatgpt_api_key');
        if (empty($api_key)) {
            wp_send_json_error(__('API Key is missing. Please set it in the settings.', 'chatgpt-article-writer'));
        }
    
        $post = get_post($post_id);
        $prompt = 'Write a detailed blog post based on the following title: "' . $post->post_title . '"';
    
        // Call OpenAI API
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a helpful assistant.'),
                    array('role' => 'user', 'content' => $prompt),
                ),
                'max_tokens' => 500,
                'temperature' => 0.7,
            )),
        ));
    
        if (is_wp_error($response)) {
            wp_send_json_error(__('Error: ' . $response->get_error_message(), 'chatgpt-article-writer'));
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (empty($data['choices'][0]['message']['content'])) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            wp_send_json_error(__('Invalid API response: ' . $error_message, 'chatgpt-article-writer'));
        }
    
        $generated_content = $data['choices'][0]['message']['content'];
    
        // Update post content
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $generated_content,
        ));
    
        wp_send_json_success();
    }
}

$chatGPTWriter = new ChatGPT_Article_Writer();
$chatGPTWriter->run();
