# 🚨 Gemini Command Center - Quick Troubleshooting Reference

## Common Error Messages & Solutions

### ❌ "PHP Parse error: syntax error, unexpected token 'private'"
**Status**: ✅ FIXED  
**Solution**: This was caused by an extra closing brace in line 570 of `class-assets-loader.php`. The fix has been applied.

### ❌ "No route was found matching the URL and request method"
**Status**: ✅ FIXED  
**Solution**: This issue was caused by API URL format incompatibility between WordPress REST API and frontend JavaScript. The fix ensures consistent `/wp-json/` URL format.

**If you're still experiencing this issue**:
1. **Verify the fix is applied**: Check that API URLs use `/wp-json/` format instead of `?rest_route=`
2. **Test API connectivity**: Upload `debug-api.php` to your WordPress root and visit it
3. **Manual test**: In browser console on admin page:
   ```javascript
   fetch('/wp-json/gemini-cc/v1/status', {
     headers: { 'X-WP-Nonce': window.gemini_cc_data.nonce }
   }).then(r => r.json()).then(console.log);
   ```

**Possible Causes** (if issue persists):
- WordPress REST API disabled
- Plugin not properly activated  
- Nonce verification failed

**Additional Solutions**:
1. Check if plugin is activated in WordPress admin
2. Go to Settings → Debug Center → Export Logs for details
3. Ensure pretty permalinks are enabled in WordPress settings

### ❌ "Invalid API Key" or "Authentication Failed (401)"
**Solutions**:
1. Verify API key is correctly copied from [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Check if Gemini API is enabled in your Google Cloud project
3. Test connection: Settings → API Configuration → Test Connection

### ❌ "Rate Limit Exceeded (429)"
**Solutions**:
1. Wait 1-2 minutes before trying again
2. Reduce request frequency
3. Check your API quotas in Google Cloud Console

### ❌ "Model not found (404)"
**Solutions**:
1. Switch to a supported model: gemini-2.0-flash, gemini-1.5-pro, or gemini-1.5-flash
2. Check if your API key has access to the selected model

## 🔧 Quick Diagnostic Steps

### 1. PHP Syntax Check
```bash
php -l wp-content/plugins/gemini-command-center/includes/class-assets-loader.php
php -l wp-content/plugins/gemini-command-center/includes/class-api-registrar.php
```
**Expected Result**: "No syntax errors detected"

### 2. WordPress REST API Test
```bash
curl "https://yoursite.com/wp-json/gemini-cc/v1/status" \
  -H "X-WP-Nonce: YOUR_NONCE"
```

### 3. Gemini API Direct Test
```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent" \
  -H "Content-Type: application/json" \
  -H "X-goog-api-key: YOUR_API_KEY" \
  -d '{"contents":[{"parts":[{"text":"Hello"}]}]}'
```

## 📊 Debug Information Collection

### Access Debug Panel
1. WordPress Admin → Gemini Command Center → Settings
2. Click "Debug Center" tab
3. Click "Export Debug Logs"

### Browser Console Errors
1. Open browser Developer Tools (F12)
2. Go to Console tab
3. Look for errors containing "Gemini CC" or "gemini-cc"

### WordPress Debug Log
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```
Check `/wp-content/debug.log` for errors

## 🔍 Configuration Checklist

### ✅ Requirements Check
- [ ] WordPress 6.0 or higher
- [ ] PHP 8.0 or higher
- [ ] HTTPS enabled (required for API calls)
- [ ] `manage_options` capability for current user

### ✅ Plugin Setup
- [ ] Plugin activated successfully
- [ ] No PHP syntax errors
- [ ] WordPress REST API working
- [ ] Plugin menu visible in admin

### ✅ API Configuration
- [ ] Valid Gemini API key entered
- [ ] Model selected (gemini-2.0-flash recommended)
- [ ] Temperature between 0.1-1.0
- [ ] Max tokens between 50-8192

### ✅ Permissions & Security
- [ ] Current user has `manage_options` capability
- [ ] Nonce verification working
- [ ] HTTPS certificate valid
- [ ] Firewall allows outbound HTTPS to googleapis.com

## 🛠️ Emergency Recovery

### Reset Plugin Settings
```sql
DELETE FROM wp_options WHERE option_name LIKE 'gemini_cc_%';
```

### Clear Error Logs
WordPress Admin → Gemini Command Center → Settings → Debug Center → Clear Logs

### Deactivate/Reactivate Plugin
1. WordPress Admin → Plugins
2. Deactivate "Gemini Command Center"
3. Reactivate plugin
4. Reconfigure API key

## 📞 Getting Help

### Information to Provide
1. **Error Message**: Exact error text
2. **Debug Logs**: Exported from Debug Center
3. **WordPress Version**: Check Admin → Dashboard
4. **PHP Version**: Check Settings → Debug Center
5. **Browser Console**: Any JavaScript errors

### Useful Commands
```bash
# Check WordPress version
wp core version

# Check plugin status
wp plugin status gemini-command-center

# Test WordPress REST API
wp rest version

# Check PHP errors
tail -f /path/to/php-error.log
```

## 🎯 Performance Optimization

### Reduce API Calls
- Increase response token limit for fewer requests
- Use caching for repeated queries
- Batch similar requests when possible

### Optimize Response Time
- Use gemini-2.0-flash for fastest responses
- Lower temperature (0.3-0.5) for quicker processing
- Keep conversation history reasonable (last 10 exchanges)

### Monitor Usage
- Check Google Cloud Console for API usage
- Monitor WordPress debug logs for errors
- Export plugin logs regularly for analysis

---

## ✅ Status: All Issues Resolved

The following problems mentioned in the original issue have been **completely fixed**:

1. **PHP Syntax Error** - ✅ Fixed by removing extra closing brace
2. **Gemini API Integration** - ✅ Implemented with correct endpoint
3. **Error Detection** - ✅ Enhanced with comprehensive logging
4. **API Pass Configuration** - ✅ Proper X-goog-api-key header implementation

**The plugin is now fully operational and ready for production use!** 🚀