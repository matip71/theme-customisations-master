/**
 * Shop scripts — Mobile Off-Canvas Filters
 */
jQuery(function ($) {
    'use strict';

    var $body = $('body');
    var $sidebar = $('#secondary.widget-area');
    var $toggleBtn = $('#tf-mobile-filters-toggle');

    if (!$sidebar.length || !$toggleBtn.length) {
        return;
    }

    // 1. Add close button to sidebar - done here to avoid conflicts with widgets
    if (!$('#tf-close-filters').length) {
        $sidebar.prepend(
            '<button type="button" id="tf-close-filters" aria-label="Cerrar filtros">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
            '</button>'
        );
    }

    // 2. Add backdrop overlay
    if (!$('.tf-filter-overlay').length) {
        $body.append('<div class="tf-filter-overlay"></div>');
    }

    var $overlay = $('.tf-filter-overlay');
    var $closeBtn = $('#tf-close-filters');

    // 3. Toggle functions
    function openFilters() {
        $body.addClass('tf-filters-open');
    }

    function closeFilters() {
        $body.removeClass('tf-filters-open');
    }

    $toggleBtn.on('click', openFilters);
    $closeBtn.on('click', closeFilters);
    $overlay.on('click', closeFilters);

    // 4. Move sidebar to body on mobile to fix position: fixed issues caused by parent transforms
    var mobileQuery = window.matchMedia('(max-width: 767px)');
    var $originalParent = $sidebar.parent();
    
    function handleSidebarPosition(e) {
        if (e.matches) {
            $body.append($sidebar);
        } else {
            $originalParent.append($sidebar);
        }
    }
    // Listen for resize changes
    if (mobileQuery.addEventListener) {
        mobileQuery.addEventListener('change', handleSidebarPosition);
    } else {
        mobileQuery.addListener(handleSidebarPosition); // Fallback for older browsers
    }
    // Run once on load
    handleSidebarPosition(mobileQuery);
});
