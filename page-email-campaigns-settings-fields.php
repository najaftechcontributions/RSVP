<div class="form-group">
					<label for="manageCampaignName">Campaign Name</label>
					<input type="text" id="manageCampaignName" class="form-input" placeholder="Campaign Name" required>
					<small class="form-help">Give your campaign a descriptive name</small>
				</div>

				<div class="form-group">
					<label for="manageCampaignEvent">Select Event</label>
					<select id="manageCampaignEvent" class="form-input" required>
						<option value="">Choose an event...</option>
						<?php
						$user_events = event_rsvp_get_user_events($user_id);
						foreach ($user_events as $event) {
							echo '<option value="' . $event->ID . '">' . esc_html(get_the_title($event->ID)) . '</option>';
						}
						?>
					</select>
					<small class="form-help">Select which event this campaign is for</small>
				</div>

				<div class="form-group">
					<label for="manageCampaignSubject">Email Subject</label>
					<input type="text" id="manageCampaignSubject" class="form-input" placeholder="Email Subject" required>
					<small class="form-help">Use {{event_name}}, {{event_date}}, {{host_name}} as placeholders</small>
				</div>
