// Prevent page scroll to top when clicking buttons
// This script prevents default behavior for buttons at the bottom of the page

document.addEventListener('DOMContentLoaded', function() {
    // Restore scroll position if saved
    const savedScroll = localStorage.getItem('scrollPosition');
    if (savedScroll) {
        window.scrollTo(0, parseInt(savedScroll, 10));
        localStorage.removeItem('scrollPosition');
    }

    // Save scroll position before form submit
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            localStorage.setItem('scrollPosition', window.scrollY);
        });
    });

    // Prevent default for non-submit buttons
    const buttons = document.querySelectorAll('button');
    buttons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            if (!button.type || button.type !== 'submit') {
                event.preventDefault();
            }
        });
    });

    // Prevent default ONLY for anchors styled as buttons with href="#" or javascript:void(0)
    const anchors = document.querySelectorAll('a.button, a.btn');
    anchors.forEach(function(anchor) {
        anchor.addEventListener('click', function(event) {
            const href = anchor.getAttribute('href');
            if (href === '#' || href === 'javascript:void(0)') {
                event.preventDefault();
            }
        });
    });
});
