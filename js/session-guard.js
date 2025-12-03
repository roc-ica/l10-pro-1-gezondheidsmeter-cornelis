// Prevent going back to protected pages after logout
window.addEventListener('pageshow', function(event) {
    // Check if this page is cached (bfcache)
    if (event.persisted) {
        // Page was restored from bfcache (back button used)
        // Force a reload to re-check session
        window.location.reload();
    }
});

// Also prevent caching by setting proper headers
window.addEventListener('unload', function() {
    // This helps browsers understand not to cache
});
