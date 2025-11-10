(function($) {
	'use strict';

	const EventRSVP = {
		init: function() {
			this.initQRScanner();
			this.initManualCheckin();
			this.initAttendeeSearch();
			this.initEventForm();
			this.loadCheckinStats();
			this.initAdClickTracking();
			this.loadCheckedInAttendees();
			this.initRefreshButtons();
		},

		initQRScanner: function() {
			const qrReaderDiv = document.getElementById('qr-reader');
			
			if (!qrReaderDiv) {
				return;
			}

			if (typeof Html5Qrcode !== 'undefined') {
				const html5QrCode = new Html5Qrcode("qr-reader");
				
				html5QrCode.start(
					{ facingMode: "environment" },
					{ fps: 10, qrbox: 250 },
					this.onScanSuccess.bind(this),
					this.onScanError.bind(this)
				).catch(err => {
					console.log('Error starting QR scanner:', err);
					this.showFallbackScanner();
				});
			} else {
				this.showFallbackScanner();
			}
		},

		showFallbackScanner: function() {
			const qrReaderDiv = document.getElementById('qr-reader');
			if (qrReaderDiv) {
				qrReaderDiv.innerHTML = '<p style="padding: 2rem; text-align: center;">QR scanner not available. Please use manual entry below.</p>';
			}
		},

		onScanSuccess: function(decodedText, decodedResult) {
			this.processCheckin(decodedText);
		},

		onScanError: function(errorMessage) {
			console.log('QR scan error:', errorMessage);
		},

		initManualCheckin: function() {
			const form = document.getElementById('manual-checkin-form');
			
			if (!form) {
				return;
			}

			form.addEventListener('submit', (e) => {
				e.preventDefault();
				const qrData = document.getElementById('qr-data-input').value;
				
				if (qrData) {
					this.processCheckin(qrData);
				}
			});
			
			const toggleButton = document.getElementById('toggle-manual');
			if (toggleButton) {
				toggleButton.addEventListener('click', function() {
					const manualForm = document.getElementById('manual-entry-form');
					const toggleText = this.querySelector('.toggle-text');
					
					if (manualForm.style.display === 'none') {
						manualForm.style.display = 'block';
						toggleText.textContent = 'Switch to Camera Scanner';
					} else {
						manualForm.style.display = 'none';
						toggleText.textContent = 'Switch to Manual Entry';
					}
				});
			}
		},

		processCheckin: function(qrData) {
			if (typeof eventRsvpData === 'undefined') {
				this.showMessage('Configuration error: eventRsvpData is not defined', 'error');
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_checkin',
					qr_data: qrData,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success) {
						this.showMessage(response.data.message, 'success');
						this.addRecentCheckin(response.data.attendee_name);
						this.loadCheckinStats();
						const input = document.getElementById('qr-data-input');
						if (input) {
							input.value = '';
						}
					} else {
						this.showMessage(response.data || 'Check-in failed', 'error');
					}
				},
				error: (xhr, status, error) => {
					this.showMessage('Error processing check-in: ' + error, 'error');
				}
			});
		},

		loadCheckinStats: function() {
			const eventId = this.getEventIdFromURL();
			
			if (!eventId) {
				return;
			}

			if (typeof eventRsvpData === 'undefined') {
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_stats',
					event_id: eventId,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success) {
						this.updateStats(response.data);
					}
				}
			});
		},

		updateStats: function(stats) {
			const totalEl = document.getElementById('total-attendees');
			const checkedInEl = document.getElementById('checked-in');
			const notCheckedInEl = document.getElementById('not-checked-in');
			const percentageEl = document.getElementById('checkin-percentage');
			
			if (totalEl) totalEl.textContent = stats.total || 0;
			if (checkedInEl) checkedInEl.textContent = stats.checked_in || 0;
			if (notCheckedInEl) notCheckedInEl.textContent = stats.not_checked_in || 0;
			if (percentageEl) percentageEl.textContent = (stats.percentage || 0) + '%';
		},

		addRecentCheckin: function(name) {
			const list = document.getElementById('recent-checkins-list');
			
			if (!list) {
				return;
			}

			const emptyState = list.querySelector('.empty-state');
			if (emptyState) {
				emptyState.remove();
			}

			const item = document.createElement('div');
			item.className = 'checkin-item';
			
			const now = new Date();
			const timeString = now.toLocaleTimeString();
			
			item.innerHTML = `
				<span class="checkin-item-name">${this.escapeHtml(name)}</span>
				<span class="checkin-item-time">${timeString}</span>
			`;
			
			list.insertBefore(item, list.firstChild);
			
			if (list.children.length > 10) {
				list.removeChild(list.lastChild);
			}
		},

		showMessage: function(message, type) {
			const messageEl = document.getElementById('status-message');
			
			if (!messageEl) {
				return;
			}

			messageEl.textContent = message;
			messageEl.className = 'status-message show ' + type;
			
			setTimeout(() => {
				messageEl.classList.remove('show');
			}, 5000);
		},

		initEventForm: function() {
			const rsvpForm = document.querySelector('.event-rsvp-form');
			
			if (!rsvpForm) {
				return;
			}

			const eventId = this.getEventIdFromURL();
			const eventIdInput = document.getElementById('event-id');
			
			if (eventIdInput && eventId) {
				eventIdInput.value = eventId;
			}

			rsvpForm.addEventListener('submit', (e) => {
				const name = document.getElementById('attendee-name');
				const email = document.getElementById('attendee-email');
				
				if (!name || !name.value || !email || !email.value) {
					e.preventDefault();
					this.showMessage('Please fill in all required fields', 'error');
					return false;
				}
			});
		},

		getEventIdFromURL: function() {
			const urlParams = new URLSearchParams(window.location.search);
			const eventIdFromParam = urlParams.get('event_id');
			
			if (eventIdFromParam) {
				return eventIdFromParam;
			}
			
			const bodyElement = document.querySelector('[data-event-id]');
			if (bodyElement) {
				return bodyElement.dataset.eventId;
			}
			
			return 0;
		},

		escapeHtml: function(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, m => map[m]);
		},

		initAttendeeSearch: function() {
			const searchInput = document.getElementById('attendee-search-input');
			const searchResults = document.getElementById('search-results-dropdown');
			const clearButton = document.getElementById('clear-search');
			let searchTimeout;

			if (!searchInput || !searchResults) {
				return;
			}

			searchInput.addEventListener('input', (e) => {
				const query = e.target.value.trim();

				clearTimeout(searchTimeout);

				if (query.length < 2) {
					searchResults.classList.remove('show');
					searchResults.innerHTML = '';
					if (clearButton) clearButton.style.display = 'none';
					return;
				}

				if (clearButton) clearButton.style.display = 'block';

				searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
				searchResults.classList.add('show');

				searchTimeout = setTimeout(() => {
					this.performAttendeeSearch(query);
				}, 300);
			});

			if (clearButton) {
				clearButton.addEventListener('click', () => {
					searchInput.value = '';
					searchResults.classList.remove('show');
					searchResults.innerHTML = '';
					clearButton.style.display = 'none';
					searchInput.focus();
				});
			}

			document.addEventListener('click', (e) => {
				if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
					searchResults.classList.remove('show');
				}
			});
		},

		performAttendeeSearch: function(query) {
			const eventId = this.getEventIdFromURL();
			const searchResults = document.getElementById('search-results-dropdown');

			if (!searchResults || typeof eventRsvpData === 'undefined') {
				return;
			}

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_search_attendees',
					query: query,
					event_id: eventId,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success && response.data.attendees) {
						this.displaySearchResults(response.data.attendees);
					} else {
						searchResults.innerHTML = '<div class="search-no-results">No attendees found</div>';
					}
				},
				error: () => {
					searchResults.innerHTML = '<div class="search-no-results">Error searching attendees</div>';
				}
			});
		},

		displaySearchResults: function(attendees) {
			const searchResults = document.getElementById('search-results-dropdown');

			if (!searchResults || !attendees || attendees.length === 0) {
				searchResults.innerHTML = '<div class="search-no-results">No attendees found</div>';
				return;
			}

			let html = '';
			attendees.forEach(attendee => {
				const statusClass = attendee.checked_in ? 'checked-in' : 'pending';
				const statusText = attendee.checked_in ? '✓ Checked In' : '⏳ Pending';

				html += `
					<div class="search-result-item" data-qr="${this.escapeHtml(attendee.qr_data)}" data-attendee-id="${attendee.id}">
						<div class="search-result-name">${this.escapeHtml(attendee.name)}</div>
						<div class="search-result-email">${this.escapeHtml(attendee.email)}</div>
						<span class="search-result-status ${statusClass}">${statusText}</span>
					</div>
				`;
			});

			searchResults.innerHTML = html;

			const resultItems = searchResults.querySelectorAll('.search-result-item');
			resultItems.forEach(item => {
				item.addEventListener('click', () => {
					const qrData = item.dataset.qr;
					const attendeeId = item.dataset.attendeeId;

					if (qrData) {
						searchResults.classList.remove('show');
						document.getElementById('attendee-search-input').value = '';
						document.getElementById('clear-search').style.display = 'none';
						this.processCheckin(qrData);
					}
				});
			});
		},

		initAdClickTracking: function() {
			$(document).on('click', '.vendor-ad-link', function(e) {
				const adId = $(this).data('ad-id');

				if (!adId || typeof eventRsvpData === 'undefined') {
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
						console.log('Ad click tracked:', response);
					},
					error: function(xhr, status, error) {
						console.log('Error tracking ad click:', error);
					}
				});
			});
		},

		loadCheckedInAttendees: function() {
			const checkinListEl = document.getElementById('checked-in-attendees-list');
			const eventCheckinEl = document.getElementById('event-checked-in-attendees');

			if (!checkinListEl && !eventCheckinEl) {
				return;
			}

			if (typeof eventRsvpData === 'undefined') {
				return;
			}

			const eventId = this.getEventIdFromURL();

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_checked_in_attendees',
					event_id: eventId,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success && response.data.attendees) {
						if (checkinListEl) {
							this.displayCheckedInAttendees(response.data.attendees, checkinListEl);
						}
						if (eventCheckinEl) {
							this.displayCheckedInAttendeesSimple(response.data.attendees, eventCheckinEl);
						}
					}
				},
				error: () => {
					if (checkinListEl) {
						checkinListEl.innerHTML = '<div class="error-state"><p>Failed to load checked-in attendees</p></div>';
					}
					if (eventCheckinEl) {
						eventCheckinEl.innerHTML = '<div class="error-mini">Failed to load</div>';
					}
				}
			});
		},

		displayCheckedInAttendees: function(attendees, container) {
			if (!attendees || attendees.length === 0) {
				container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>No checked-in attendees yet</p></div>';
				return;
			}

			let html = '<table class="attendees-table"><thead><tr>';
			html += '<th>Name</th>';
			html += '<th>Email</th>';
			html += '<th>Phone</th>';
			html += '<th>Check-In Time</th>';
			html += '<th>Event</th>';
			html += '</tr></thead><tbody>';

			attendees.forEach(attendee => {
				html += '<tr>';
				html += '<td class="attendee-name">' + this.escapeHtml(attendee.name) + '</td>';
				html += '<td class="attendee-email">' + this.escapeHtml(attendee.email) + '</td>';
				html += '<td class="attendee-phone">' + this.escapeHtml(attendee.phone || 'N/A') + '</td>';
				html += '<td class="attendee-checkin-time">' + this.escapeHtml(attendee.checkin_time) + '</td>';
				html += '<td class="attendee-event">' + this.escapeHtml(attendee.event_title) + '</td>';
				html += '</tr>';
			});

			html += '</tbody></table>';
			container.innerHTML = html;
		},

		displayCheckedInAttendeesSimple: function(attendees, container) {
			const eventId = container.dataset.eventId || 0;

			const filteredAttendees = eventId > 0
				? attendees.filter(a => a.event_id == eventId)
				: attendees;

			if (!filteredAttendees || filteredAttendees.length === 0) {
				container.innerHTML = '<div class="empty-mini">No check-ins yet</div>';
				return;
			}

			let html = '<ul class="checked-in-mini-list">';
			filteredAttendees.slice(0, 10).forEach(attendee => {
				html += '<li class="checked-in-mini-item">';
				html += '<span class="mini-name">' + this.escapeHtml(attendee.name) + '</span>';
				html += '<span class="mini-time">' + this.escapeHtml(attendee.checkin_time) + '</span>';
				html += '</li>';
			});
			html += '</ul>';

			if (filteredAttendees.length > 10) {
				html += '<p class="mini-count">+' + (filteredAttendees.length - 10) + ' more</p>';
			}

			container.innerHTML = html;
		},

		initRefreshButtons: function() {
			const refreshAttendeesBtn = document.getElementById('refresh-attendees');
			const refreshCheckedInBtn = document.getElementById('refresh-checked-in');

			if (refreshAttendeesBtn) {
				refreshAttendeesBtn.addEventListener('click', () => {
					this.loadCheckedInAttendees();
					this.loadCheckinStats();
				});
			}

			if (refreshCheckedInBtn) {
				refreshCheckedInBtn.addEventListener('click', () => {
					this.loadCheckedInAttendees();
				});
			}
		}
	};

	$(document).ready(function() {
		EventRSVP.init();
	});

})(jQuery);
