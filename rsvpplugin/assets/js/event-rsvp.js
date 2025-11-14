(function($) {
	'use strict';

	const EventRSVP = {
		html5QrCode: null,
		isScannerActive: false,

		init: function() {
			this.initScannerModeTabs();
			this.initQRScanner();
			this.initQRUpload();
			this.initManualCheckin();
			this.initAttendeeSearch();
			this.initAttendeeTabs();
			this.initEventForm();
			this.loadCheckinStats();
			this.initAdClickTracking();
			this.loadAllAttendees();
			this.loadCheckedInAttendees();
			this.loadPendingAttendees();
			this.initRefreshButtons();
		},

		initScannerModeTabs: function() {
			const modeBtns = document.querySelectorAll('.scanner-mode-btn');
			const modeContents = document.querySelectorAll('.scanner-mode-content');

			if (!modeBtns.length) {
				return;
			}

			modeBtns.forEach(btn => {
				btn.addEventListener('click', () => {
					const mode = btn.dataset.mode;

					modeBtns.forEach(b => b.classList.remove('active'));
					modeContents.forEach(c => c.classList.remove('active'));

					btn.classList.add('active');
					const targetContent = document.getElementById('scanner-' + mode + '-mode');
					if (targetContent) {
						targetContent.classList.add('active');
					}

					if (mode === 'camera') {
						this.startCameraScanner();
					} else {
						this.stopCameraScanner();
					}
				});
			});
		},

		initQRScanner: function() {
			const qrReaderDiv = document.getElementById('qr-reader');
			
			if (!qrReaderDiv) {
				return;
			}

			if (typeof Html5Qrcode !== 'undefined') {
				this.startCameraScanner();
			} else {
				this.showFallbackScanner();
			}
		},

		startCameraScanner: function() {
			const qrReaderDiv = document.getElementById('qr-reader');
			
			if (!qrReaderDiv || this.isScannerActive) {
				return;
			}

			if (typeof Html5Qrcode === 'undefined') {
				return;
			}

			if (this.html5QrCode) {
				try {
					this.html5QrCode.stop();
				} catch (e) {
					console.log('Error stopping scanner:', e);
				}
			}

			this.html5QrCode = new Html5Qrcode("qr-reader");
			
			this.html5QrCode.start(
				{ facingMode: "environment" },
				{ fps: 10, qrbox: 250 },
				this.onScanSuccess.bind(this),
				this.onScanError.bind(this)
			).then(() => {
				this.isScannerActive = true;
			}).catch(err => {
				console.log('Error starting QR scanner:', err);
				this.showFallbackScanner();
			});
		},

		stopCameraScanner: function() {
			if (this.html5QrCode && this.isScannerActive) {
				try {
					this.html5QrCode.stop().then(() => {
						this.isScannerActive = false;
					}).catch(err => {
						console.log('Error stopping scanner:', err);
						this.isScannerActive = false;
					});
				} catch (e) {
					console.log('Error stopping scanner:', e);
					this.isScannerActive = false;
				}
			}
		},

		showFallbackScanner: function() {
			const qrReaderDiv = document.getElementById('qr-reader');
			if (qrReaderDiv) {
				qrReaderDiv.innerHTML = '<p style="padding: 2rem; text-align: center;">QR scanner not available. Please use upload or manual entry.</p>';
			}
		},

		onScanSuccess: function(decodedText, decodedResult) {
			this.processCheckin(decodedText);
		},

		onScanError: function(errorMessage) {
		},

		initQRUpload: function() {
			const fileInput = document.getElementById('qr-file-input');
			const browseBtn = document.getElementById('browse-qr-file');
			const dropzone = document.getElementById('qr-dropzone');
			const uploadAnother = document.getElementById('upload-another');

			if (!fileInput) {
				return;
			}

			if (browseBtn) {
				browseBtn.addEventListener('click', () => {
					fileInput.click();
				});
			}

			if (dropzone) {
				dropzone.addEventListener('click', (e) => {
					if (e.target === dropzone || e.target.closest('.upload-dropzone')) {
						fileInput.click();
					}
				});

				dropzone.addEventListener('dragover', (e) => {
					e.preventDefault();
					dropzone.classList.add('drag-over');
				});

				dropzone.addEventListener('dragleave', () => {
					dropzone.classList.remove('drag-over');
				});

				dropzone.addEventListener('drop', (e) => {
					e.preventDefault();
					dropzone.classList.remove('drag-over');
					const files = e.dataTransfer.files;
					if (files.length > 0) {
						this.processQRFile(files[0]);
					}
				});
			}

			fileInput.addEventListener('change', (e) => {
				const file = e.target.files[0];
				if (file) {
					this.processQRFile(file);
				}
			});

			if (uploadAnother) {
				uploadAnother.addEventListener('click', () => {
					const preview = document.getElementById('qr-upload-preview');
					const dropzone = document.getElementById('qr-dropzone');
					if (preview && dropzone) {
						preview.style.display = 'none';
						dropzone.style.display = 'block';
					}
					fileInput.value = '';
				});
			}
		},

		processQRFile: function(file) {
			if (!file.type.match('image.*')) {
				this.showMessage('Please upload an image file', 'error');
				return;
			}

			const dropzone = document.getElementById('qr-dropzone');
			const preview = document.getElementById('qr-upload-preview');
			const previewImage = document.getElementById('qr-preview-image');
			const uploadStatus = document.getElementById('upload-status');

			if (dropzone) dropzone.style.display = 'none';
			if (preview) preview.style.display = 'block';
			if (uploadStatus) uploadStatus.textContent = 'Processing...';

			const reader = new FileReader();
			reader.onload = (e) => {
				if (previewImage) {
					previewImage.src = e.target.result;
				}

				if (typeof Html5Qrcode !== 'undefined') {
					const html5QrCode = new Html5Qrcode("qr-upload-scanner-temp");
					
					html5QrCode.scanFile(file, true)
						.then(decodedText => {
							if (uploadStatus) {
								uploadStatus.textContent = '✓ QR Code Detected!';
								uploadStatus.style.color = 'var(--event-success)';
							}
							this.processCheckin(decodedText);
							
							setTimeout(() => {
								const uploadAnother = document.getElementById('upload-another');
								if (uploadAnother) {
									uploadAnother.click();
								}
							}, 2000);
						})
						.catch(err => {
							console.log('QR scan error:', err);
							if (uploadStatus) {
								uploadStatus.textContent = '✗ No QR code found in image';
								uploadStatus.style.color = 'var(--event-error)';
							}
						});
				} else {
					if (uploadStatus) {
						uploadStatus.textContent = 'QR scanner library not available';
						uploadStatus.style.color = 'var(--event-error)';
					}
				}
			};
			reader.readAsDataURL(file);
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
						this.refreshCurrentTab();
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
						const clearBtn = document.getElementById('clear-search');
						if (clearBtn) clearBtn.style.display = 'none';
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
					}
				});
			});
		},

		loadAllAttendees: function() {
			const allListEl = document.getElementById('all-attendees-list');

			if (!allListEl || typeof eventRsvpData === 'undefined') {
				return;
			}

			const eventId = this.getEventIdFromURL();

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_all_attendees',
					event_id: eventId,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success && response.data.attendees) {
						this.displayAllAttendees(response.data.attendees, allListEl);
					}
				},
				error: () => {
					allListEl.innerHTML = '<div class="error-state"><p>Failed to load attendees</p></div>';
				}
			});
		},

		displayAllAttendees: function(attendees, container) {
			if (!attendees || attendees.length === 0) {
				container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>No attendees yet</p></div>';
				return;
			}

			let html = '<table class="attendees-table"><thead><tr>';
			html += '<th>Name</th>';
			html += '<th>Email</th>';
			html += '<th>Phone</th>';
			html += '<th>Status</th>';
			html += '<th>Check-In Time</th>';
			html += '<th>Event</th>';
			html += '<th>Action</th>';
			html += '</tr></thead><tbody>';

			attendees.forEach(attendee => {
				const statusBadge = attendee.checked_in 
					? '<span class="status-badge status-checked-in">✓ Checked In</span>'
					: '<span class="status-badge status-not-checked-in">⏳ Pending</span>';

				const actionBtn = !attendee.checked_in && attendee.qr_data
					? `<button class="quick-checkin-btn" data-qr="${this.escapeHtml(attendee.qr_data)}">✓ Check In</button>`
					: '';

				html += '<tr>';
				html += '<td class="attendee-name">' + this.escapeHtml(attendee.name) + '</td>';
				html += '<td class="attendee-email">' + this.escapeHtml(attendee.email) + '</td>';
				html += '<td class="attendee-phone">' + this.escapeHtml(attendee.phone || 'N/A') + '</td>';
				html += '<td>' + statusBadge + '</td>';
				html += '<td class="attendee-checkin-time">' + this.escapeHtml(attendee.checkin_time || 'Not checked in') + '</td>';
				html += '<td class="attendee-event">' + this.escapeHtml(attendee.event_title) + '</td>';
				html += '<td>' + actionBtn + '</td>';
				html += '</tr>';
			});

			html += '</tbody></table>';
			container.innerHTML = html;

			const checkinBtns = container.querySelectorAll('.quick-checkin-btn');
			checkinBtns.forEach(btn => {
				btn.addEventListener('click', () => {
					const qrData = btn.dataset.qr;
					if (qrData) {
						this.processCheckin(qrData);
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

		loadPendingAttendees: function() {
			const pendingListEl = document.getElementById('pending-attendees-list');

			if (!pendingListEl || typeof eventRsvpData === 'undefined') {
				return;
			}

			const eventId = this.getEventIdFromURL();

			$.ajax({
				url: eventRsvpData.ajax_url,
				type: 'POST',
				data: {
					action: 'event_rsvp_get_pending_attendees',
					event_id: eventId,
					nonce: eventRsvpData.nonce
				},
				success: (response) => {
					if (response.success && response.data.attendees) {
						this.displayPendingAttendees(response.data.attendees, pendingListEl);
					}
				},
				error: () => {
					pendingListEl.innerHTML = '<div class="error-state"><p>Failed to load pending attendees</p></div>';
				}
			});
		},

		displayPendingAttendees: function(attendees, container) {
			if (!attendees || attendees.length === 0) {
				container.innerHTML = '<div class="empty-state"><div class="empty-icon">📋</div><p>No pending attendees</p></div>';
				return;
			}

			let html = '<table class="attendees-table"><thead><tr>';
			html += '<th>Name</th>';
			html += '<th>Email</th>';
			html += '<th>Phone</th>';
			html += '<th>Event</th>';
			html += '<th>Action</th>';
			html += '</tr></thead><tbody>';

			attendees.forEach(attendee => {
				const actionBtn = attendee.qr_data
					? `<button class="quick-checkin-btn" data-qr="${this.escapeHtml(attendee.qr_data)}">✓ Check In</button>`
					: '';

				html += '<tr>';
				html += '<td class="attendee-name">' + this.escapeHtml(attendee.name) + '</td>';
				html += '<td class="attendee-email">' + this.escapeHtml(attendee.email) + '</td>';
				html += '<td class="attendee-phone">' + this.escapeHtml(attendee.phone || 'N/A') + '</td>';
				html += '<td class="attendee-event">' + this.escapeHtml(attendee.event_title) + '</td>';
				html += '<td>' + actionBtn + '</td>';
				html += '</tr>';
			});

			html += '</tbody></table>';
			container.innerHTML = html;

			const checkinBtns = container.querySelectorAll('.quick-checkin-btn');
			checkinBtns.forEach(btn => {
				btn.addEventListener('click', () => {
					const qrData = btn.dataset.qr;
					if (qrData) {
						this.processCheckin(qrData);
					}
				});
			});
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

		refreshCurrentTab: function() {
			const activeTab = document.querySelector('.attendee-tab-btn.active');
			
			if (!activeTab) {
				this.loadAllAttendees();
				return;
			}

			const tab = activeTab.dataset.tab;
			
			if (tab === 'all') {
				this.loadAllAttendees();
			} else if (tab === 'checked-in') {
				this.loadCheckedInAttendees();
			} else if (tab === 'pending') {
				this.loadPendingAttendees();
			}
		},

		initRefreshButtons: function() {
			const refreshAttendeesBtn = document.getElementById('refresh-attendees');
			const refreshCheckedInBtn = document.getElementById('refresh-checked-in');

			if (refreshAttendeesBtn) {
				refreshAttendeesBtn.addEventListener('click', () => {
					this.loadAllAttendees();
					this.loadCheckedInAttendees();
					this.loadPendingAttendees();
					this.loadCheckinStats();
				});
			}

			if (refreshCheckedInBtn) {
				refreshCheckedInBtn.addEventListener('click', () => {
					this.loadCheckedInAttendees();
				});
			}
		},

		initAttendeeTabs: function() {
			const tabButtons = document.querySelectorAll('.attendee-tab-btn');
			const tabContents = document.querySelectorAll('.attendees-tab-content');

			if (!tabButtons.length || !tabContents.length) {
				return;
			}

			tabButtons.forEach(button => {
				button.addEventListener('click', function() {
					const targetTab = this.dataset.tab;

					if (!targetTab) return;

					tabButtons.forEach(btn => btn.classList.remove('active'));
					tabContents.forEach(content => content.classList.remove('active'));

					this.classList.add('active');

					const targetContent = document.getElementById('tab-' + targetTab);
					if (targetContent) {
						targetContent.classList.add('active');
					}
				});
			});
		}
	};

	$(document).ready(function() {
		EventRSVP.init();
	});

})(jQuery);
