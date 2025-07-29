<?php
/**
 * Debug script to test API endpoints
 * 
 * Place this file in your WordPress root directory and visit it in a browser
 * to test if the Gemini CC API routes are working correctly.
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You need admin privileges to run this test.');
}

echo "<h1>Gemini Command Center API Test</h1>";

// Test 1: Check if routes are registered
echo "<h2>1. Route Registration Test</h2>";
$server = rest_get_server();
$routes = $server->get_routes();

$status_route = '/gemini-cc/v1/status';
if (isset($routes[$status_route])) {
    echo "<p style='color: green;'>✓ Status route is registered: $status_route</p>";
    echo "<pre>" . print_r($routes[$status_route], true) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ Status route NOT found</p>";
    
    // Show available gemini-cc routes
    echo "<h3>Available Gemini CC routes:</h3>";
    $gemini_routes = array_filter($routes, function($key) {
        return strpos($key, 'gemini-cc') !== false;
    }, ARRAY_FILTER_USE_KEY);
    
    if (empty($gemini_routes)) {
        echo "<p style='color: red;'>No Gemini CC routes found!</p>";
    } else {
        echo "<ul>";
        foreach (array_keys($gemini_routes) as $route) {
            echo "<li>$route</li>";
        }
        echo "</ul>";
    }
}

// Test 2: Check URL formats
echo "<h2>2. URL Format Test</h2>";
$rest_url_format = rest_url('gemini-cc/v1/status');
$wp_json_format = home_url('/wp-json/gemini-cc/v1/status');

echo "<p><strong>rest_url() format:</strong> $rest_url_format</p>";
echo "<p><strong>home_url() format:</strong> $wp_json_format</p>";

echo "<p style='color: blue;'><strong>Recommended:</strong> Use home_url() format for frontend API calls</p>";

// Test 3: Make actual API request
echo "<h2>3. API Request Test</h2>";

echo "<h3>Testing /wp-json/ format:</h3>";
$response = wp_remote_get($wp_json_format, array(
    'headers' => array(
        'X-WP-Nonce' => wp_create_nonce('wp_rest')
    )
));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>Request failed: " . $response->get_error_message() . "</p>";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "<p><strong>Status Code:</strong> $status_code</p>";
    
    if ($status_code == 200) {
        echo "<p style='color: green;'>✓ API request successful!</p>";
        echo "<pre>" . print_r(json_decode($body, true), true) . "</pre>";
    } else {
        echo "<p style='color: red;'>✗ API request failed</p>";
        echo "<pre>$body</pre>";
    }
}

// Test 4: JavaScript test
echo "<h2>4. Frontend JavaScript Test</h2>";
echo "<p>Open browser console to see JavaScript API test results.</p>";

// Enqueue WordPress scripts for the test
wp_enqueue_script('wp-api-fetch');
wp_print_scripts('wp-api-fetch');

?>

<script>
// Test the API from JavaScript
console.log('=== Gemini CC API JavaScript Test ===');

// Test configuration data
const testData = {
    api_url: '<?php echo esc_js(home_url('/wp-json/gemini-cc/v1/')); ?>',
    nonce: '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>',
    alt_url: '<?php echo esc_js(rest_url('gemini-cc/v1/')); ?>'
};

console.log('Test configuration:', testData);

// Configure wp.apiFetch like the plugin does
if (window.wp && window.wp.apiFetch) {
    console.log('Configuring wp.apiFetch...');
    
    wp.apiFetch.use((options, next) => {
        options.headers = {
            ...options.headers,
            'X-WP-Nonce': testData.nonce,
        };
        return next(options);
    });
    
    wp.apiFetch.use(wp.apiFetch.createRootURLMiddleware(testData.api_url));
    
    console.log('Testing API call to status endpoint...');
    
    wp.apiFetch({
        path: 'status',
        method: 'GET',
    })
    .then(response => {
        console.log('✓ API test successful!', response);
    })
    .catch(error => {
        console.error('✗ API test failed:', error);
        
        // Try alternative URL format
        console.log('Trying alternative URL format...');
        fetch(testData.alt_url + 'status', {
            headers: {
                'X-WP-Nonce': testData.nonce
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('✓ Alternative URL format works:', data);
        })
        .catch(altError => {
            console.error('✗ Alternative URL also failed:', altError);
        });
    });
} else {
    console.error('wp.apiFetch not available');
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
h1, h2, h3 { color: #333; }
</style>

<?php
// Clean up
wp_reset_postdata();
?>