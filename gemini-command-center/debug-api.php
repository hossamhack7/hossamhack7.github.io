<?php
/**
 * Enhanced Debug script to test API endpoints
 * 
 * Place this file in your WordPress root directory and visit it in a browser
 * to test if the Gemini CC API routes are working correctly.
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You need admin privileges to run this test.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Gemini Command Center - Enhanced API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #155724; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 5px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 5px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border: 1px solid #ffeeba; border-radius: 4px; margin: 5px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; border-radius: 4px; margin: 5px 0; }
        pre { background: #f8f9fa; padding: 10px; border: 1px solid #e9ecef; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .btn { display: inline-block; padding: 8px 12px; margin: 5px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .section { margin: 20px 0; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Gemini Command Center - Enhanced API Test</h1>
        <p><strong>Test Time:</strong> <?php echo current_time('mysql'); ?></p>

<?php
// Test 1: System Information
echo '<div class="section">';
echo '<h2>1. System Information</h2>';
echo '<div class="info">';
echo '<strong>WordPress Version:</strong> ' . get_bloginfo('version') . '<br>';
echo '<strong>PHP Version:</strong> ' . PHP_VERSION . '<br>';
echo '<strong>Plugin Active:</strong> ' . (is_plugin_active('gemini-command-center/gemini-command-center.php') ? 'Yes' : 'No') . '<br>';
echo '<strong>WP_DEBUG:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . '<br>';
echo '<strong>Current User:</strong> ' . wp_get_current_user()->display_name . ' (ID: ' . get_current_user_id() . ')<br>';
echo '</div>';
echo '</div>';

// Test 2: Class Loading
echo '<div class="section">';
echo '<h2>2. Plugin Class Loading Test</h2>';
if (class_exists('Gemini_Command_Center')) {
    echo '<div class="success">✓ Gemini_Command_Center class loaded</div>';
    if (class_exists('Gemini_CC_API_Registrar')) {
        echo '<div class="success">✓ Gemini_CC_API_Registrar class loaded</div>';
    } else {
        echo '<div class="error">✗ Gemini_CC_API_Registrar class NOT loaded</div>';
    }
} else {
    echo '<div class="error">✗ Gemini_Command_Center class NOT loaded</div>';
}
echo '</div>';

// Test 3: Route Registration
echo '<div class="section">';
echo '<h2>3. Route Registration Test</h2>';
$server = rest_get_server();
$routes = $server->get_routes();

$status_route = '/gemini-cc/v1/status';
if (isset($routes[$status_route])) {
    echo '<div class="success">✓ Status route is registered: ' . $status_route . '</div>';
    echo '<pre>' . print_r($routes[$status_route], true) . '</pre>';
} else {
    echo '<div class="error">✗ Status route NOT found: ' . $status_route . '</div>';
}

// Show available gemini-cc routes
$gemini_routes = array_filter($routes, function($key) {
    return strpos($key, 'gemini-cc') !== false;
}, ARRAY_FILTER_USE_KEY);

if (!empty($gemini_routes)) {
    echo '<div class="info"><strong>Found ' . count($gemini_routes) . ' Gemini CC routes:</strong></div>';
    echo '<ul>';
    foreach (array_keys($gemini_routes) as $route) {
        echo '<li>' . $route . '</li>';
    }
    echo '</ul>';
} else {
    echo '<div class="error">✗ No Gemini CC routes found!</div>';
}
echo '</div>';

// Test 4: URL Format Test
echo '<div class="section">';
echo '<h2>4. URL Format Test</h2>';
$rest_url_format = rest_url('gemini-cc/v1/status');
$wp_json_format = home_url('/wp-json/gemini-cc/v1/status');

echo '<div class="info">';
echo '<strong>rest_url() format:</strong> ' . $rest_url_format . '<br>';
echo '<strong>home_url() format:</strong> ' . $wp_json_format . '<br>';
echo '</div>';
echo '<div class="warning">Recommended: Use home_url() format for frontend API calls to avoid routing issues</div>';
echo '</div>';

// Test 5: Direct API Request
echo '<div class="section">';
echo '<h2>5. Direct API Request Test</h2>';

echo '<h3>Testing home_url() format:</h3>';
$response = wp_remote_get($wp_json_format, array(
    'headers' => array(
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    ),
    'timeout' => 30
));

if (is_wp_error($response)) {
    echo '<div class="error">Request failed: ' . $response->get_error_message() . '</div>';
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo '<div class="info"><strong>Status Code:</strong> ' . $status_code . '</div>';
    
    if ($status_code == 200) {
        echo '<div class="success">✓ API request successful!</div>';
        $data = json_decode($body, true);
        if ($data) {
            echo '<pre>' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
        } else {
            echo '<pre>' . esc_html($body) . '</pre>';
        }
    } else {
        echo '<div class="error">✗ API request failed</div>';
        echo '<pre>' . esc_html($body) . '</pre>';
    }
}
echo '</div>';

// Test 6: Debug Information
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<div class="section">';
    echo '<h2>6. Debug Information</h2>';
    
    $api_log = get_option('gemini_cc_api_log', array());
    $error_log = get_option('gemini_cc_error_log', array());
    $routes_registered = get_option('gemini_cc_routes_registered', 'Unknown');
    $route_check_time = get_option('gemini_cc_route_check_time', 'Never');
    
    echo '<div class="info">';
    echo '<strong>Routes Registered:</strong> ' . ($routes_registered ? 'Yes' : 'No') . '<br>';
    echo '<strong>Last Route Check:</strong> ' . $route_check_time . '<br>';
    echo '<strong>API Calls Logged:</strong> ' . count($api_log) . '<br>';
    echo '<strong>Errors Logged:</strong> ' . count($error_log) . '<br>';
    echo '</div>';
    
    if (!empty($error_log)) {
        echo '<h3>Recent Errors (Last 3):</h3>';
        $recent_errors = array_slice($error_log, -3);
        foreach ($recent_errors as $error) {
            echo '<div class="error">';
            echo '<strong>Time:</strong> ' . esc_html($error['time'] ?? 'Unknown') . '<br>';
            echo '<strong>Message:</strong> ' . esc_html($error['message'] ?? 'No message') . '<br>';
            if (!empty($error['context'])) {
                echo '<strong>Context:</strong><br>';
                echo '<pre>' . esc_html(wp_json_encode($error['context'], JSON_PRETTY_PRINT)) . '</pre>';
            }
            echo '</div>';
        }
    }
    echo '</div>';
}

// Test 7: JavaScript test
echo '<div class="section">';
echo '<h2>7. Frontend JavaScript Test</h2>';
echo '<button class="btn" onclick="runJSTest()">Run JavaScript API Test</button>';
echo '<div id="js-test-result"></div>';
echo '</div>';

// Action buttons
echo '<div class="section">';
echo '<h2>Actions</h2>';
echo '<a href="' . $_SERVER['REQUEST_URI'] . '" class="btn">🔄 Refresh Tests</a>';
if (current_user_can('manage_options')) {
    echo '<a href="' . admin_url('admin.php?page=gemini-debug') . '" class="btn">🐛 Admin Debug Page</a>';
    echo '<a href="' . admin_url('admin.php?page=gemini-command-center') . '" class="btn">🚀 Gemini CC Dashboard</a>';
}
echo '</div>';

?>

<script>
function runJSTest() {
    const resultDiv = document.getElementById('js-test-result');
    resultDiv.innerHTML = '<div class="info">Running JavaScript API test...</div>';
    
    const testData = {
        api_url: '<?php echo esc_js(home_url('/wp-json/gemini-cc/v1/')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>',
        status_url: '<?php echo esc_js($wp_json_format); ?>'
    };
    
    console.log('Testing API with configuration:', testData);
    
    fetch(testData.status_url, {
        method: 'GET',
        headers: {
            'X-WP-Nonce': testData.nonce,
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    })
    .then(data => {
        resultDiv.innerHTML = '<div class="success">✓ JavaScript API test successful!</div><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        console.log('✓ API test successful:', data);
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="error">✗ JavaScript API test failed: ' + error.message + '</div>';
        console.error('✗ API test failed:', error);
    });
}

// Auto-run test when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Gemini CC Enhanced API Debug Test ===');
    console.log('Page loaded, you can run the JavaScript test using the button above');
});
</script>

    </div>
</body>
</html>