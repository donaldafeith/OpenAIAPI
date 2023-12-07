<?php
/**
 * Plugin Name: OpenAI Integration
 * Description: Connects to OpenAI API.
 * Version: 0.1
 * Author: Donalda Feith
 */

// Hook for adding admin menus
add_action('admin_menu', 'openai_integration_menu');

function openai_integration_menu() {
    add_menu_page('OpenAI Integration Settings', 'OpenAI Integration', 'administrator', 'openai-integration-settings', 'openai_integration_settings_page', 'dashicons-admin-generic');
}

function openai_integration_settings_page() {
    ?>
    <div class="wrap">
        <h2>OpenAI Integration</h2>
        <form method="post" action="options.php">
            <?php settings_fields('openai-options-group'); ?>
            <?php do_settings_sections('openai-options-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OpenAI API Key<br><input type="text" name="openai_api_key" value="<?php echo esc_attr(get_option('openai_api_key')); ?>" /></th>
                     <td class="align-top"></td>
					<td>    
					<style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        p {
            margin: 10px 0;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        .box {
            background: #f4f4f4;
            padding: 20px;
            margin: 20px 0;
        }
        code {
            background: #eaeaea;
            display: inline-block;
            padding: 2px 5px;
            margin: 0 2px;
            font-family: 'Courier New', Courier, monospace;
        }
    </style>
    <div class="container">
        <h1>OpenAI Integration Plugin Guide</h1>
        
        <div class="box">
            <h2>Installation</h2>
            <p>To install the OpenAI Integration plugin, upload the plugin files to the <code>/wp-content/plugins/</code> directory, or install the plugin through the WordPress plugins screen.</p>
            <p>Once uploaded, activate the plugin through the 'Plugins' screen in WordPress.</p>
        </div>
        
        <div class="box">
            <h2>Configuration</h2>
            <p>After activation, go to the newly added 'OpenAI Integration' menu in your WordPress admin panel.</p>
            <p>Enter your OpenAI API Key in the provided field. This key is necessary for the plugin to interact with OpenAI's API.</p>
        </div>
        
        <div class="box">
            <h2>Using the Plugin</h2>
            <p>The plugin works by using a shortcode that you can add to any post or page. This shortcode allows you to send prompts to the OpenAI API and display the response.</p>
            <h3>Shortcode Format:</h3>
            <code>[openai prompt="Your question or prompt here"]</code>
            <p>Replace <code>"Your question or prompt here"</code> with the actual question or prompt you want to send to the OpenAI API.</p>
            <h3>Example:</h3>
            <code>[openai prompt="What is the capital of France?"]</code>
            <p>This will display the answer to the question directly in your post or page.</p>
        </div>
        
        <div class="box">
            <h2>Notes</h2>
            <p>Keep in mind that each request to the OpenAI API might consume your API quota depending on your OpenAI plan. Use the plugin judiciously.</p>
            <p>It's important to ensure that your prompts do not violate OpenAI's usage policies.</p>
        </div>
    </div></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'update_openai_integration_settings');

function update_openai_integration_settings() {
    register_setting('openai-options-group', 'openai_api_key');
}

function call_openai_api($prompt) {
    $api_key = get_option('openai_api_key');
    if (!$api_key) {
        return 'API key is not set.';
    }

    $ch = curl_init('https://api.openai.com/v1/completions');
    $data = json_encode([
        'model' => 'gpt-3.5-turbo-instruct', // Specify the model here
        'prompt' => sanitize_text_field($prompt), // Sanitize the user input
        'max_tokens' => 150,
        'temperature' => 0.7, // You can adjust this as needed
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return 'Curl error: ' . $error_msg;
    }

    curl_close($ch);

    $decoded_response = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return 'Invalid JSON response';
    }

    return $decoded_response;
}

// Shortcode for using the API in posts/pages
function openai_shortcode($atts = [], $content = null) {
    $atts = shortcode_atts(['prompt' => ''], $atts);
    $response = call_openai_api($atts['prompt']);
    if (is_array($response)) {
        return esc_html($response['choices'][0]['text'] ?? 'Error processing request.');
    }
    return esc_html($response);
}
add_shortcode('openai', 'openai_shortcode');
