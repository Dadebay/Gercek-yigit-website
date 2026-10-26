(function($) {
    "use strict";
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Spinner
    var spinner = function() {
        setTimeout(function() {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);


    // Initiate the wowjs
    if (!reducedMotion && typeof WOW !== 'undefined') new WOW().init();


    // Header carousel
    $(".header-carousel").owlCarousel({
        animateOut: 'fadeOut',
        items: 1,
        margin: 0,
        stagePadding: 0,
        autoplay: false,
        autoplayTimeout: 6000,
        smartSpeed: reducedMotion ? 0 : 420,
        dots: false,
        loop: $(".header-carousel .header-carousel-item").length > 1,
        nav: true,
        navText: [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
    });


    // Project-carousel: initialize per-carousel and disable loop when only one slide
    $(".project-carousel").each(function() {
        var $el = $(this);
        var slideCount = $el.find('img').length || 0;
        var enableLoop = slideCount > 1;
        $el.owlCarousel({
            autoplay: !reducedMotion && enableLoop,
            smartSpeed: reducedMotion ? 0 : 420,
            center: false,
            dots: false,
            loop: enableLoop,
            margin: 10,
            nav: true,
            navText: [
                '<i class="bi bi-arrow-left"></i>',
                '<i class="bi bi-arrow-right"></i>'
            ],
            items: 1
        });
    });


    // Service-carousel: initialize per-carousel and disable loop when only one slide
    $(".service-carousel").each(function() {
        var $el = $(this);
        var slideCount = $el.find('.service-item').length || 0;
        var enableLoop = slideCount > 1;
        $el.owlCarousel({
            autoplay: !reducedMotion && enableLoop,
            smartSpeed: reducedMotion ? 0 : 420,
            center: false,
            dots: false,
            loop: enableLoop,
            margin: 25,
            nav: true,
            navText: [
                '<i class="bi bi-arrow-left"></i>',
                '<i class="bi bi-arrow-right"></i>'
            ],
            responsiveClass: true,
            responsive: {
                0: { items: 1 },
                576: { items: 1 },
                768: { items: 2 },
                992: { items: 2 },
                1200: { items: 2 }
            }
        });
    });


    // testimonial carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: !reducedMotion,
        smartSpeed: reducedMotion ? 0 : 420,
        center: false,
        dots: true,
        loop: true,
        margin: 25,
        nav: false,
        navText: [
            '<i class="fa fa-angle-right"></i>',
            '<i class="fa fa-angle-left"></i>'
        ],
        responsiveClass: true,
        responsive: {
            0: {
                items: 1
            },
            576: {
                items: 1
            },
            768: {
                items: 1
            },
            992: {
                items: 1
            },
            1200: {
                items: 2
            }
        }
    });


    // Back to top button
    $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').stop(true, true).fadeIn(reducedMotion ? 0 : 160);
        } else {
            $('.back-to-top').stop(true, true).fadeOut(reducedMotion ? 0 : 160);
        }
    });
    $('.back-to-top').click(function() {
        if (reducedMotion) window.scrollTo(0, 0);
        else $('html, body').animate({ scrollTop: 0 }, 420);
        return false;
    });


})(jQuery);

// Navbar search toggle (outside jQuery IIFE to keep scope simple)
(function() {
    function closeSearch(e) {
        var containers = document.querySelectorAll('.navbar-search.open');
        containers.forEach(function(c) { c.classList.remove('open'); });
    }

    function initSearchToggle() {
        var containers = document.querySelectorAll('.navbar-search');
        containers.forEach(function(container) {
            var toggle = container.querySelector('.search-toggle');
            var form = container.querySelector('.search-form');
            var input = form ? form.querySelector('input[name="q"]') : null;
            if (!toggle || !form) return;
            toggle.addEventListener('click', function(ev) {
                ev.preventDefault();
                container.classList.toggle('open');
                if (container.classList.contains('open') && input) {
                    setTimeout(function() { input.focus(); }, 50);
                }
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            var target = e.target;
            var within = target.closest('.navbar-search');
            if (!within) closeSearch();
        });
        // Close on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSearch();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearchToggle);
    } else {
        initSearchToggle();
    }
})();
