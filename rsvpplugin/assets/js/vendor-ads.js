/**
 * Vendor Ads Tracking and Interaction
 * Handles ad click tracking and interactive features
 */

(function($) {
	'use strict';

	const VendorAds = {
		init: function() {
			this.trackAdClicks();
			this.initAdSlider();
			this.trackAdImpressions();
		},

		trackAdClicks: function() {
			$(document).on('click', '.vendor-ad-link', function(e) {
				const adId = $(this).data('ad-id');
				
				if (!adId) {
					return;
				}

				$.ajax({
					url: eventRsvpData.ajax_url,
					type: 'POST',
					data: {
						action: 'event_rsvp_track_ad_click',
						ad_id: adId,
						nonce: eventRsvpData.nonce
					},
					success: function(response) {
						if (response.success) {
							console.log('Ad click tracked:', response.data.clicks);
						}
					},
					error: function(xhr, status, error) {
						console.error('Ad click tracking error:', error);
					}
				});
			});
		},

		trackAdImpressions: function() {
			const observerOptions = {
				root: null,
				rootMargin: '0px',
				threshold: 0.5
			};

			const trackedAds = new Set();

			const observer = new IntersectionObserver((entries) => {
				entries.forEach(entry => {
					if (entry.isIntersecting) {
						const adElement = entry.target;
						const adLink = adElement.querySelector('.vendor-ad-link');
						
						if (adLink) {
							const adId = adLink.getAttribute('data-ad-id');
							
							if (adId && !trackedAds.has(adId)) {
								trackedAds.add(adId);
								console.log('Ad impression for ID:', adId);
							}
						}
					}
				});
			}, observerOptions);

			document.querySelectorAll('.vendor-ad-wrapper, .vendor-ad-single').forEach(ad => {
				observer.observe(ad);
			});
		},

		initAdSlider: function() {
			$('.vendor-ad-slider').each(function() {
				const $slider = $(this);
				const $track = $slider.find('.vendor-ad-slider-track');
				const $slides = $track.find('.vendor-ad-wrapper');
				const slideCount = $slides.length;
				
				if (slideCount <= 1) {
					return;
				}

				let currentSlide = 0;
				const slideInterval = 5000;

				const createControls = function() {
					const $controls = $('<div class="vendor-ad-slider-controls"></div>');
					
					for (let i = 0; i < slideCount; i++) {
						const $dot = $('<button class="vendor-ad-slider-dot"></button>');
						if (i === 0) {
							$dot.addClass('active');
						}
						$dot.attr('data-slide', i);
						$controls.append($dot);
					}
					
					$slider.append($controls);

					const $prevArrow = $('<button class="vendor-ad-carousel-arrow prev">‹</button>');
					const $nextArrow = $('<button class="vendor-ad-carousel-arrow next">›</button>');
					
					$slider.append($prevArrow);
					$slider.append($nextArrow);
				};

				const goToSlide = function(index) {
					currentSlide = index;
					const offset = -currentSlide * 100;
					$track.css('transform', `translateX(${offset}%)`);
					
					$slider.find('.vendor-ad-slider-dot').removeClass('active');
					$slider.find(`.vendor-ad-slider-dot[data-slide="${currentSlide}"]`).addClass('active');
				};

				const nextSlide = function() {
					currentSlide = (currentSlide + 1) % slideCount;
					goToSlide(currentSlide);
				};

				const prevSlide = function() {
					currentSlide = (currentSlide - 1 + slideCount) % slideCount;
					goToSlide(currentSlide);
				};

				createControls();

				$slider.on('click', '.vendor-ad-slider-dot', function() {
					const slideIndex = parseInt($(this).attr('data-slide'));
					goToSlide(slideIndex);
				});

				$slider.on('click', '.vendor-ad-carousel-arrow.next', function(e) {
					e.preventDefault();
					nextSlide();
				});

				$slider.on('click', '.vendor-ad-carousel-arrow.prev', function(e) {
					e.preventDefault();
					prevSlide();
				});

				setInterval(nextSlide, slideInterval);
			});
		},

		createAdGrid: function(ads, containerId) {
			const $container = $(`#${containerId}`);
			
			if (!$container.length || !ads || ads.length === 0) {
				return;
			}

			const $grid = $('<div class="vendor-ad-grid"></div>');

			ads.forEach(ad => {
				const adHtml = this.renderAdCard(ad);
				$grid.append(adHtml);
			});

			$container.html($grid);
		},

		renderAdCard: function(ad) {
			const imageUrl = ad.image || '';
			const title = ad.title || 'Advertisement';
			const url = ad.url || '#';
			const id = ad.id || 0;

			return `
				<div class="vendor-ad-wrapper">
					<div class="vendor-ad-container">
						<a href="${url}" target="_blank" rel="noopener sponsored" class="vendor-ad-link" data-ad-id="${id}">
							<div class="vendor-ad-image">
								<img src="${imageUrl}" alt="${title}">
							</div>
							<div class="vendor-ad-overlay">
								<span class="vendor-ad-title">${title}</span>
								<span class="vendor-ad-cta">Learn More →</span>
							</div>
						</a>
					</div>
				</div>
			`;
		},

		addSponsoredLabel: function() {
			$('.vendor-ad-container').each(function() {
				if (!$(this).find('.vendor-ad-sponsored-label').length) {
					$(this).prepend('<div class="vendor-ad-sponsored-label">Sponsored</div>');
				}
			});
		}
	};

	$(document).ready(function() {
		VendorAds.init();
	});

	window.VendorAds = VendorAds;

})(jQuery);
