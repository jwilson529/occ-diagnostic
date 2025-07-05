<?php
/**
 * Plugin Name:       OneClickContent Advanced Diagnostic
 * Description:       A standalone tool to test API connectivity from a WordPress installation to OneClickContent services.
 * Version:           3.1
 * Author:            James Wilson
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the admin page under the "Tools" menu.
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'OCC Advanced Diagnostic',
        'OCC Advanced Diagnostic',
        'manage_options',
        'oneclick-advanced-diagnostic',
        'occ_advanced_diag_render_page'
    );
});

/**
 * Render the admin page content.
 */
function occ_advanced_diag_render_page() {
    echo '<div class="wrap"><h1>OneClickContent Advanced Diagnostic</h1>';

    // Handle license key submission
    $license_key = get_option( 'occ_diagnostic_license_key', '' );
    if ( isset( $_POST['occ_license_key'] ) && isset( $_POST['occ_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['occ_license_nonce'] ), 'occ_save_license' ) ) {
        $new_license_key = sanitize_text_field( $_POST['occ_license_key'] );
        update_option( 'occ_diagnostic_license_key', $new_license_key );
        $license_key = $new_license_key;
        echo '<div class="notice notice-success"><p>License key saved successfully.</p></div>';
    }

    // License key input form
    echo '<form method="post" action="">';
    wp_nonce_field( 'occ_save_license', 'occ_license_nonce' );
    echo '<p><label for="occ_license_key">Enter License Key:</label><br>';
    echo '<input type="text" id="occ_license_key" name="occ_license_key" value="' . esc_attr( $license_key ) . '" class="regular-text" placeholder="e.g., PK-XXXXXXXXXXXXXXXXXXXX" /></p>';
    echo '<p><input type="submit" class="button button-secondary" value="Save License Key" /></p>';
    echo '</form>';

    if ( isset( $_GET['run_tests'] ) && $_GET['run_tests'] === 'true' ) {
        echo '<p><strong>Running tests... Please wait. This may take a few minutes.</strong></p>';
        
        // Nonce check for security
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'run_occ_diag_tests' ) ) {
            echo '<p style="color:red;">Security check failed. Please go back and try again.</p>';
        } else {
            $tester = new OneClick_Advanced_Diagnostic_Tester( $license_key );
            $results = $tester->run_all_tests();

            // Collect raw responses for JavaScript
            $raw_responses = [];
            foreach ( $results as $result ) {
                $raw_responses[$result['name']] = $result['details'];
            }
            $raw_responses_json = json_encode( $raw_responses );

            echo '<h2>Test Results</h2><table class="wp-list-table widefat striped fixed" id="occ-diagnostic-results">';
            echo '<thead><tr><th style="width:30%">Test Name</th><th style="width:15%">Status</th><th>Details</th></tr></thead><tbody>';
            
            foreach ( $results as $result ) {
                $status_style = $result['passed'] ? 'color:green;font-weight:bold;' : 'color:red;font-weight:bold;';
                $status_text  = $result['passed'] ? '✅ PASSED' : '❌ FAILED';
                
                echo '<tr>';
                echo '<td>' . esc_html( $result['name'] ) . '</td>';
                echo '<td style="' . esc_attr( $status_style ) . '">' . wp_kses_post( $status_text ) . '</td>';
                echo '<td>';

                $details = $result['details'];
                $body = $result['response_body'] ?? [];

                if ( is_string( $details ) && str_starts_with( $details, 'Status:' ) ) {
                    echo '<strong>' . esc_html( strtok( $details, "\n" ) ) . '</strong><br>';

                    // Handle different response types based on test name
                    if ( $result['name'] === 'Step 0: Validate License' ) {
                        if ( isset( $body['status'] ) && $body['status'] === 'success' ) {
                            echo '<strong>Message:</strong> ' . esc_html( $body['message'] ?? 'N/A' ) . '<br>';
                        } else {
                            echo '<strong>Error:</strong> ' . esc_html( $body['message'] ?? 'No metadata returned.' ) . '<br>';
                        }
                    } elseif ( $result['name'] === 'Step 1: Subscriber Check Usage' ) {
                        if ( isset( $body['success'] ) && $body['success'] == 1 ) {
                            echo '<strong>Subscription:</strong> ' . esc_html( $body['subscription'] ?? 'N/A' ) . '<br>';
                            echo '<strong>Usage Limit:</strong> ' . esc_html( $body['usage_limit'] ?? 'N/A' ) . '<br>';
                            echo '<strong>Used Count:</strong> ' . esc_html( $body['used_count'] ?? 'N/A' ) . '<br>';
                            echo '<strong>Remaining Count:</strong> ' . esc_html( $body['remaining_count'] ?? 'N/A' ) . '<br>';
                        } else {
                            echo '<strong>Error:</strong> ' . esc_html( $body['message'] ?? 'No metadata returned.' ) . '<br>';
                        }
                    } elseif ( str_contains( $result['name'], 'Generate Meta' ) ) {
                        $arguments = $body['choices'][0]['message']['function_call']['arguments'] ?? null;
                        if ( $arguments ) {
                            $args = is_string($arguments) ? json_decode($arguments, true) : $arguments;
                            if ( json_last_error() === JSON_ERROR_NONE && is_array($args) ) {
                                echo '<strong>Alt Text:</strong> ' . esc_html( $args['alt_text'] ?? 'N/A' ) . '<br>';
                                echo '<strong>Title:</strong> ' . esc_html( $args['title'] ?? 'N/A' ) . '<br>';
                                echo '<strong>Caption:</strong> ' . esc_html( $args['caption'] ?? 'N/A' ) . '<br>';
                            } else {
                                echo '<em>Failed to parse JSON arguments.</em><br>';
                            }
                        } else {
                            echo '<em>No metadata returned.</em><br>';
                        }
                    }
                } else {
                    echo esc_html( $details );
                }

                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';

            // Add copy button and instructions
            echo '<p style="margin-top: 20px;"><button id="occ-copy-results" class="button button-primary">Copy Results to Clipboard</button></p>';
            echo '<p>Please paste the copied results into an email and send them to <a href="mailto:support@oneclickcontent.com">support@oneclickcontent.com</a> for further assistance.</p>';

            // JavaScript for copying results to clipboard with fallback
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function() {';
            echo '  const rawResponses = ' . $raw_responses_json . ';';
            echo '  document.getElementById("occ-copy-results").addEventListener("click", function() {';
            echo '    let resultsText = "OneClickContent Diagnostic Results\n\n";';
            echo '    const table = document.getElementById("occ-diagnostic-results");';
            echo '    const rows = table.getElementsByTagName("tr");';
            echo '    for (let i = 1; i < rows.length; i++) {'; // Start from 1 to skip header
            echo '      const cells = rows[i].getElementsByTagName("td");';
            echo '      const testName = cells[0].innerText;';
            echo '      const status = cells[1].innerText;';
            echo '      const details = cells[2].innerText;';
            echo '      const rawResponse = rawResponses[testName] || "No raw response available.";';
            echo '      resultsText += `${testName}\n${status}\n${details}\nRaw Response:\n${rawResponse}\n\n`;';
            echo '    }';
            echo '    if (navigator.clipboard && window.isSecureContext) {';
            echo '      navigator.clipboard.writeText(resultsText).then(() => {';
            echo '        alert("Results copied to clipboard! Please paste them into an email.");';
            echo '      }).catch(err => {';
            echo '        console.error("Failed to copy: ", err);';
            echo '        alert("Failed to copy results. Please manualy select and copy the table contents.");';
            echo '      });';
            echo '    } else {';
            echo '      const textarea = document.createElement("textarea");';
            echo '      textarea.value = resultsText;';
            echo '      textarea.style.position = "fixed";';
            echo '      textarea.style.opacity = "0";';
            echo '      document.body.appendChild(textarea);';
            echo '      textarea.focus();';
            echo '      textarea.select();';
            echo '      try {';
            echo '        document.execCommand("copy");';
            echo '        alert("Results copied to clipboard! Please paste them into an email.");';
            echo '      } catch (err) {';
            echo '        console.error("Fallback copy failed: ", err);';
            echo '        alert("Failed to copy results. Please manually select and copy the table contents.");';
            echo '      }';
            echo '      document.body.removeChild(textarea);';
            echo '    }';
            echo '  });';
            echo '});';
            echo '</script>';
        }

    } else {
        echo '<p>This tool runs a series of tests against the OneClickContent API endpoints to verify connectivity and expected responses. This helps diagnose issues related to firewalls or server configuration.</p>';
        $run_url = wp_nonce_url( admin_url('tools.php?page=oneclick-advanced-diagnostic&run_tests=true'), 'run_occ_diag_tests' );
        echo '<a href="' . esc_url( $run_url ) . '" class="button button-primary">Run Diagnostic Tests</a>';
    }
    
    echo '</div>';
}
/**
 * The main testing class, updated to include license validation test and use user-provided license key.
 */
class OneClick_Advanced_Diagnostic_Tester {
    const BASE_URI = 'https://oneclickcontent.com';
    const OCC_IMAGES_PRODUCT_SLUG = 'oneclickcontent-image-meta-generator';
    const SECRET_SALT = 'AbFUY5D9EvkMWu8y3zxwXRhPBpNaejcsKrT4Q6tnm27SfZqVGL';

    private $license_key;
    private $base_64_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAApgAAAKYB3X3/OAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAANCSURBVEiJtZZPbBtFFMZ/M7ubXdtdb1xSFyeilBapySVU8h8OoFaooFSqiihIVIpQBKci6KEg9Q6H9kovIHoCIVQJJCKE1ENFjnAgcaSGC6rEnxBwA04Tx43t2FnvDAfjkNibxgHxnWb2e/u992bee7tCa00YFsffekFY+nUzFtjW0LrvjRXrCDIAaPLlW0nHL0SsZtVoaF98mLrx3pdhOqLtYPHChahZcYYO7KvPFxvRl5XPp1sN3adWiD1ZAqD6XYK1b/dvE5IWryTt2udLFedwc1+9kLp+vbbpoDh+6TklxBeAi9TL0taeWpdmZzQDry0AcO+jQ12RyohqqoYoo8RDwJrU+qXkjWtfi8Xxt58BdQuwQs9qC/afLwCw8tnQbqYAPsgxE1S6F3EAIXux2oQFKm0ihMsOF71dHYx+f3NND68ghCu1YIoePPQN1pGRABkJ6Bus96CutRZMydTl+TvuiRW1m3n0eDl0vRPcEysqdXn+jsQPsrHMquGeXEaY4Yk4wxWcY5V/9scqOMOVUFthatyTy8QyqwZ+kDURKoMWxNKr2EeqVKcTNOajqKoBgOE28U4tdQl5p5bwCw7BWquaZSzAPlwjlithJtp3pTImSqQRrb2Z8PHGigD4RZuNX6JYj6wj7O4TFLbCO/Mn/m8R+h6rYSUb3ekokRY6f/YukArN979jcW+V/S8g0eT/N3VN3kTqWbQ428m9/8k0P/1aIhF36PccEl6EhOcAUCrXKZXXWS3XKd2vc/TRBG9O5ELC17MmWubD2nKhUKZa26Ba2+D3P+4/MNCFwg59oWVeYhkzgN/JDR8deKBoD7Y+ljEjGZ0sosXVTvbc6RHirr2reNy1OXd6pJsQ+gqjk8VWFYmHrwBzW/n+uMPFiRwHB2I7ih8ciHFxIkd/3Omk5tCDV1t+2nNu5sxxpDFNx+huNhVT3/zMDz8usXC3ddaHBj1GHj/As08fwTS7Kt1HBTmyN29vdwAw+/wbwLVOJ3uAD1wi/dUH7Qei66PfyuRj4Ik9is+hglfbkbfR3cnZm7chlUWLdwmprtCohX4HUtlOcQjLYCu+fzGJH2QRKvP3UNz8bWk1qMxjGTOMThZ3kvgLI5AzFfo379UAAAAASUVORK5CYII=';

    public function __construct( $license_key = '' ) {
        $this->license_key = ! empty( $license_key ) ? $license_key : 'PK-OQZPUShcEIXaGS37fFrE'; // Fallback to hardcoded key
    }

    private function get_function_definition(): array {
        return [
            'name' => 'generate_image_metadata',
            'description' => 'Generates structured metadata for an image, including alt text, title, and caption.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'alt_text' => ['type' => 'string', 'description' => 'Detailed alt text for accessibility.'],
                    'title' => ['type' => 'string', 'description' => 'A concise title for the image.'],
                    'caption' => ['type' => 'string', 'description' => 'An informative caption.'],
                ],
                'required' => ['alt_text', 'title']
            ]
        ];
    }

    private function makeImageMessage(): array {
        return [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type'      => 'text',
                        'text'      => 'Generate metadata for this image.',
                    ],
                    [
                        'type'      => 'image_url',
                        'image_url' => [ 'url' => $this->base_64_image ],
                    ],
                ],
            ],
        ];
    }

    private function generate_hmac_hash( $origin_url, $timestamp ): string {
        $data = $origin_url . $timestamp;
        return hash_hmac( 'sha256', $data, self::SECRET_SALT );
    }

    private function do_request( $method, $endpoint, $args = [] ) {
        $url = self::BASE_URI . $endpoint;
        
        $defaults = [
            'method'      => $method,
            'timeout'     => 45,
            'httpversion' => '1.1',
        ];

        if (isset($args['json'])) {
            $defaults['body'] = wp_json_encode($args['json']);
            if (!isset($args['headers']['Content-Type'])) {
                $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
            }
        }

        if (isset($args['query'])) {
            $url = add_query_arg($args['query'], $url);
        }

        $request_args = array_merge($defaults, $args);
        
        return wp_remote_request($url, $request_args);
    }
    
    public function run_all_tests(): array {
        $results = [];
        
        $results[] = $this->test_validate_license();
        $results[] = $this->test_subscriber_check_usage();
        $results[] = $this->test_subscriber_generate_meta();
        $results[] = $this->test_free_trial_generate_meta();

        return $results;
    }

    public function test_validate_license(): array {
        $testName = 'Step 0: Validate License';
        $payload = [
            'license_key' => $this->license_key,
            'site_url'    => home_url(),
        ];

        $response = $this->do_request('POST', '/wp-json/oneclick/v1/auth/validate-license', ['json' => $payload]);

        if (is_wp_error($response)) {
            return [
                'name' => $testName,
                'passed' => false,
                'details' => 'Connection Error: ' . $response->get_error_message(),
                'response_body' => []
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        $passed = ($status === 200 && !empty($body['status']) && $body['status'] === 'success');
        $details = "Status: {$status}, Response: " . print_r($body, true);

        return [
            'name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'response_body' => $body
        ];
    }

    public function test_subscriber_check_usage(): array {
        $testName = 'Step 1: Subscriber Check Usage';
        $payload = [
            'license_key'  => $this->license_key,
            'origin_url'   => home_url(),
            'product_slug' => self::OCC_IMAGES_PRODUCT_SLUG,
        ];
        
        $response = $this->do_request('POST', '/wp-json/subscriber/v1/check-usage', ['json' => $payload]);

        if (is_wp_error($response)) {
            return [
                'name' => $testName,
                'passed' => false,
                'details' => 'Connection Error: ' . $response->get_error_message(),
                'response_body' => []
            ];
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);
        
        $passed = ($status === 200 && !empty($body['success']));
        $details = "Status: {$status}, Response: " . print_r($body, true);
        
        return [
            'name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'response_body' => $body
        ];
    }

    public function test_subscriber_generate_meta(): array {
        $testName = 'Step 2: Subscriber Generate Meta';
        $payload = [
            'license_key'   => $this->license_key,
            'origin_url'    => home_url(),
            'product_slug'  => self::OCC_IMAGES_PRODUCT_SLUG,
            'messages'      => $this->makeImageMessage(),
            'functions'     => [$this->get_function_definition()],
            'function_call' => ['name' => 'generate_image_metadata'],
            'max_tokens'    => 500,
        ];

        $response = $this->do_request('POST', '/wp-json/subscriber/v1/generate-meta', ['json' => $payload]);

        if (is_wp_error($response)) {
            return [
                'name' => $testName,
                'passed' => false,
                'details' => 'Connection Error: ' . $response->get_error_message(),
                'response_body' => []
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        $passed = ($status === 200 && !empty($body['success']));
        $details = "Status: {$status}, Response: " . print_r($body, true);
        
        return [
            'name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'response_body' => $body
        ];
    }

    public function test_free_trial_generate_meta(): array {
        $testName = 'Step 3: Free Trial Generate Meta';
        $origin_url = home_url();
        $timestamp  = time();
        $hmac_hash  = $this->generate_hmac_hash($origin_url, $timestamp);
        
        $payload = [
            'origin_url'   => $origin_url,
            'product_slug' => self::OCC_IMAGES_PRODUCT_SLUG,
            'messages'     => $this->makeImageMessage(),
            'functions'     => [$this->get_function_definition()],
            'function_call' => ['name' => 'generate_image_metadata'],
            'max_tokens'    => 500,
        ];
        
        $args = [
            'headers' => [
                'X-Free-Trial-Hash' => $hmac_hash,
                'X-Timestamp'       => $timestamp,
            ],
            'json' => $payload,
        ];
        
        $response = $this->do_request('POST', '/wp-json/free-trial/v1/generate-meta', $args);

        if (is_wp_error($response)) {
            return [
                'name' => $testName,
                'passed' => false,
                'details' => 'Connection Error: ' . $response->get_error_message(),
                'response_body' => []
            ];
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        $limit_reached = ($status === 403 && isset($body['error']) && strpos($body['error'], 'limit reached') !== false);
        $passed = (($status === 200 && !empty($body['success'])) || $limit_reached);
        $details = "Status: {$status}, Response: " . print_r($body, true);

        return [
            'name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'response_body' => $body
        ];
    }
}
?>