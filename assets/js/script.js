// Detect if activity log has scrollbar and adjust padding
document.addEventListener('DOMContentLoaded', function() {
  const activityLogs = document.querySelectorAll('.activity-log');
  
  function checkScroll(element) {
    // Check if element has scrollbar (scrollHeight > clientHeight)
    if (element.scrollHeight > element.clientHeight) {
      element.classList.add('has-scroll');
    } else {
      element.classList.remove('has-scroll');
    }
  }
  
  // Check on load
  activityLogs.forEach(log => checkScroll(log));
  
  // Check on scroll event
  activityLogs.forEach(log => {
    log.addEventListener('scroll', function() {
      checkScroll(this);
    });
  });
  
  // Check on window resize
  window.addEventListener('resize', function() {
    activityLogs.forEach(log => checkScroll(log));
  });
});
