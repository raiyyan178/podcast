jQuery(document).ready(function($) {
    // Skip in admin
    if ($('body').hasClass('wp-admin')) {
        return;
    }

    // Log initialization

    // Reusable date formatter for local timezone
    function formatScheduleDates() {
        document.querySelectorAll('.gogn-podcast-schedule').forEach(function(el) {
            try {
                const utcSchedule = el.getAttribute('data-utc-schedule');
                if (!utcSchedule) {
                    throw new Error('Missing UTC schedule');
                }

                const date = new Date(utcSchedule + 'Z');
                if (isNaN(date.getTime())) {
                    throw new Error('Invalid date format: ' + utcSchedule);
                }

                // Get user's local timezone
                const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                    timeZone: userTimezone
                };

                const isListing = el.closest('.gogn-podcast-listing') !== null;
                const isVideo = el.closest('p') && el.closest('p').textContent.includes('scheduled for release');
                let prefix = '';
                if (isListing) {
                    prefix = 'Scheduled for ';
                } else if (isVideo) {
                    prefix = '';
                }

                const formattedDate = date.toLocaleString('en-US', options);
                el.textContent = prefix + formattedDate;
            } catch (error) {
                el.textContent = 'Schedule unavailable';
            }
        });
    }

    // Initial formatting
    formatScheduleDates();

    // Check for gognPodcastVars
    if (typeof gognPodcastVars === 'undefined') {
        $('.gogn-podcast-listing').html('<p class="error">Plugin configuration error. Please contact support.</p>');
        return;
    }

    // AJAX category filtering
    $(document).on('click', '.gogn-podcast-category-link', function(e) {
        e.preventDefault();
        const $this = $(this);
        const category = $this.data('category');
        const $listing = $('.gogn-podcast-listing');

        // Update UI
        $('.gogn-podcast-categories li').removeClass('active');
        $this.parent().addClass('active');
        $listing.addClass('loading').html('<div class="loading-spinner">Loading podcasts...</div>');

        $.ajax({
            url: gognPodcastVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gogn_podcast_filter',
                nonce: gognPodcastVars.nonce,
                category: category
            },
            success: function(response) {
                $listing.removeClass('loading');
                if (response.success) {
                    $listing.html(response.data);
                    formatScheduleDates();
                } else {
                    $listing.html(`<p class="error">${response.data || 'Error loading podcasts'}</p>`);
                }
            },
            error: function(xhr, status, error) {
                $listing.removeClass('loading');
                $listing.html(`<p class="error">Request failed: ${error}</p>`);
            }
        });
    });

    // AJAX search
    $(document).on('click', '.gogn-podcast-search-button', function(e) {
        e.preventDefault();
        const $input = $('.gogn-podcast-search-input');
        const searchQuery = $input.val().trim();
        const $listing = $('.gogn-podcast-listing');

        // Guess if search is for title or tag (heuristic)
        const isLikelyTag = searchQuery.length < 5 || !searchQuery.includes(' ');

        $listing.addClass('loading').html('<div class="loading-spinner">' + (searchQuery ? 'Searching podcasts...' : 'Loading all podcasts...') + '</div>');

        $.ajax({
            url: gognPodcastVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gogn_podcast_search',
                nonce: gognPodcastVars.nonce,
                search: searchQuery
            },
            beforeSend: function() {
            },
            success: function(response) {
                $listing.removeClass('loading');
                if (response.success) {
                    $listing.html(response.data);
                    formatScheduleDates();
                } else {     
                    $listing.html(`<p class="error">${response.data || 'Error searching podcasts'}</p>`);
                }
            },
            error: function(xhr, status, error) {
                $listing.removeClass('loading');         
                $listing.html(`<p class="error">Request failed: ${error}</p>`);
            }
        });
    });

    // Trigger search on Enter key
    $(document).on('keypress', '.gogn-podcast-search-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('.gogn-podcast-search-button').trigger('click');
        }
    });
});