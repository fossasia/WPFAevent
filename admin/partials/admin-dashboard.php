<?php
/**
 * Template Name: FOSSASIA Admin Dashboard (Plugin)
 * Description: A template for the admin dashboard to manage speaker submissions.
 */
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have sufficient permissions to access this page.', 'Access Denied', array( 'response' => 403 ) );
}

$event_id    = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
$event_title = $event_id ? get_the_title( $event_id ) : '';

$upload_dir = wp_upload_dir();
$data_dir   = $upload_dir['basedir'] . '/fossasia-data';

// Define file paths based on event ID
$sponsors_file       = $event_id ? $data_dir . '/sponsors-' . $event_id . '.json' : '';
$settings_file       = $event_id ? $data_dir . '/site-settings-' . $event_id . '.json' : '';
$speakers_file       = $event_id ? $data_dir . '/speakers-' . $event_id . '.json' : '';
$schedule_file       = $event_id ? $data_dir . '/schedule-' . $event_id . '.json' : '';
$theme_settings_file = $event_id ? $data_dir . '/theme-settings-' . $event_id . '.json' : '';

// Global files that are not event-specific
$global_settings_file = $data_dir . '/site-settings.json';
if ( $event_id ) {
	$sections_file = $data_dir . '/custom-sections-' . $event_id . '.json';
} else {
	$sections_file = $data_dir . '/custom-sections.json'; // This remains the "global" file for the Events Listing page.
}
$navigation_file  = $data_dir . '/navigation.json'; // Assuming navigation is global
$coc_content_file = $data_dir . '/coc-content.json';

// Ensure files exist
if ( $event_id ) {
	if ( ! file_exists( $sponsors_file ) ) {
		file_put_contents( $sponsors_file, '[]' ); }
	if ( ! file_exists( $settings_file ) ) {
		file_put_contents( $settings_file, '{"about_section_content": "", "section_visibility": {"about": true, "speakers": true, "schedule": true, "sponsors": true}}' ); }
	if ( ! file_exists( $speakers_file ) ) {
		file_put_contents( $speakers_file, '[]' ); }
	if ( ! file_exists( $schedule_file ) ) {
		file_put_contents( $schedule_file, '{}' ); }
	if ( ! file_exists( $theme_settings_file ) ) {
		file_put_contents( $theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}' ); }
}
if ( ! file_exists( $sections_file ) ) {
	file_put_contents( $sections_file, '[]' ); } // This now correctly handles both global and event-specific files.
if ( ! file_exists( $navigation_file ) ) {
	file_put_contents( $navigation_file, '[]' ); }
if ( ! file_exists( $global_settings_file ) ) {
	file_put_contents( $global_settings_file, '{"hero_image_url": "", "footer_text": ""}' ); }
if ( ! file_exists( $coc_content_file ) ) {
	file_put_contents( $coc_content_file, '{"content": "<p>Placeholder CoC content.</p>"}' ); }

// Speaker data is needed for the new "Manage Speakers" tab
$speakers_data        = $event_id && file_exists( $speakers_file ) ? json_decode( file_get_contents( $speakers_file ), true ) : array();
$sponsors_data        = $event_id && file_exists( $sponsors_file ) ? json_decode( file_get_contents( $sponsors_file ), true ) : array();
$site_settings_data   = $event_id && file_exists( $settings_file ) ? json_decode( file_get_contents( $settings_file ), true ) : array();
$custom_sections_data = json_decode( file_get_contents( $sections_file ), true );
$schedule_data        = $event_id && file_exists( $schedule_file ) ? json_decode( file_get_contents( $schedule_file ), true ) : array();
$navigation_data      = json_decode( file_get_contents( $navigation_file ), true );
$theme_settings_data  = $event_id && file_exists( $theme_settings_file ) ? json_decode( file_get_contents( $theme_settings_file ), true ) : array();
$coc_content_data     = json_decode( file_get_contents( $coc_content_file ), true );
$global_settings_data = json_decode( file_get_contents( $global_settings_file ), true );

// Determine the correct "View Site" URL.
$view_site_url = esc_url( home_url( '/fossasia-summit/' ) ); // Default URL.

if ( isset( $_GET['return_to'] ) ) {
	// Priority 1: Use the 'return_to' query parameter if it exists.
	$view_site_url = esc_url( urldecode( $_GET['return_to'] ) ); // This is now the event-specific page
} else {
	// Priority 2 (Fallback): Use the HTTP referer if it's a valid internal URL.
	$referer_url = wp_get_referer();
	if ( $referer_url && strpos( $referer_url, home_url() ) === 0 && strpos( $referer_url, 'admin-dashboard' ) === false ) {
		$view_site_url = esc_url( $referer_url );
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		:root {
			--brand: #D51007; --bg: #f8f9fa; --text: #0b0b0b; --muted: #5b636a;
			--card-radius: 16px; --shadow: 0 10px 30px rgba(11,11,11,.08); --container: 1150px;
		}
		html, body { margin: 0; background: var(--bg); color: var(--text); font-family: Inter, system-ui, sans-serif; }
		* { box-sizing: border-box; }
		.dashboard-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
		.dashboard-header h1 { margin: 0; }
		.dashboard-header .header-actions { display: flex; gap: 10px; }
		.header-actions .btn { text-decoration: none; }
		.header-actions .btn-secondary { background: #6c757d; color: #fff; }
		.container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 24px; box-sizing: border-box; }
		h1, h2 { color: var(--brand); }
		.dashboard-section { background: #fff; padding: 20px; border-radius: var(--card-radius); box-shadow: var(--shadow); margin-bottom: 30px; }
		.dashboard-tabs { display: flex; gap: 5px; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
		.dashboard-tab { padding: 10px 15px; cursor: pointer; font-weight: 600; color: var(--muted); border-bottom: 3px solid transparent; }
		.dashboard-tab.active { color: var(--brand); border-bottom-color: var(--brand); }
		.dashboard-panel { display: none; }
		.dashboard-panel.active { display: block; }
		.speaker-request-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
		.speaker-info { flex: 1 1 400px; }
		.speaker-info h3 { margin: 0 0 5px; }
		.speaker-info p { margin: 0; color: var(--muted); font-size: 14px; }
		.speaker-actions { display: flex; gap: 10px; }
		.speaker-card-admin { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
		.speaker-card-admin img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
		.speaker-card-info { flex: 1 1 300px; }
		.speaker-card-info h3 { margin: 0 0 5px; }
		.speaker-card-info p { margin: 0; color: var(--muted); font-size: 14px; }
		.speaker-card-controls { display: flex; align-items: center; gap: 20px; }
		.switch { position: relative; display: inline-block; width: 50px; height: 24px; }
		.switch input { opacity: 0; width: 0; height: 0; }
		.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
		.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
		input:checked + .slider { background-color: var(--brand); }
		input:checked + .slider:before { transform: translateX(26px); }
		.speaker-controls-header { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; }
		.sponsor-group-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
		.sponsor-group-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
		.sponsor-group-header h3 { margin: 0; color: var(--brand); }
		.sponsor-group-controls { display: flex; align-items: center; gap: 15px; }
		.sponsor-item { display: flex; align-items: center; gap: 15px; padding: 10px; border-bottom: 1px solid #eee; }
		.sponsor-item:last-child { border-bottom: none; }
		.sponsor-item img { width: 100px; height: auto; object-fit: contain; background: #f8f9fa; border-radius: 4px; }
		.btn { display: inline-flex; gap: .6rem; align-items: center; padding: .6rem 1rem; border-radius: 999px; font-weight: 700; border: 2px solid transparent; cursor: pointer; }
		.btn-secondary { background: #6c757d; color: #fff; }
		.btn-accept { background: #28a745; color: #fff; }
		.btn-reject { background: #dc3545; color: #fff; }
		.btn-edit { background: #ffc107; color: #212529; }
		#no-requests { color: var(--muted); text-align: center; padding: 20px; }
		/* Edit Modal styles */
		.modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
		.modal-content { background-color: #fefefe; margin: auto; padding: 20px 30px 30px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: var(--card-radius); position: relative; max-height: 90vh; overflow-y: auto; }
		.close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
		#editSpeakerForm { display: flex; flex-direction: column; gap: 10px; }
		#editSpeakerForm h2 { margin-top: 0; }
		#editSpeakerForm label { font-weight: 600; margin-top: 5px; }
		#editSpeakerForm input, #editSpeakerForm textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px; }
		#addSponsorGroupForm .dynamic-sponsor-form { border-top: 1px solid #ccc; margin-top: 15px; padding-top: 15px; }
		.section-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
		.section-info { flex: 1 1 400px; }
		.section-info h3 { margin: 0 0 5px; }
		.section-info p { margin: 0; color: var(--muted); font-size: 14px; }
		.nav-item { display: flex; align-items: center; gap: 15px; padding: 10px; border-bottom: 1px solid #eee; }
		.nav-item:last-child { border-bottom: none; }
		.nav-item-info { flex-grow: 1; }
		.nav-item-sub-items {
			padding-left: 30px;
			border-left: 2px solid #eee;
			margin-left: 10px;
			margin-top: 10px;
		}
		.nav-item-sub-items .nav-item { background-color: #f8f9fa; }
		#addNavItemForm { margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
		#editSpeakerForm .form-section-heading { font-weight: bold; font-size: 1.1em; margin-top: 20px; margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #eee; color: var(--brand); }
		#sectionForm fieldset { border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
		#sectionForm legend { font-weight: bold; color: var(--brand); padding: 0 5px; }
		#editSpeakerForm button { margin-top: 15px; align-self: flex-start; }
		.mini-editor { border: 1px solid #ddd; border-radius: 4px; }
		.mini-editor .toolbar { background: #f8f9fa; padding: 5px; border-bottom: 1px solid #ddd; }
		.mini-editor .toolbar button {
			border: 1px solid transparent;
			background: transparent;
			padding: 4px 8px;
			cursor: pointer;
			margin-right: 2px;
			border-radius: 3px;
			font-size: 14px;
			min-width: 30px;
		}
		.mini-editor .toolbar button:hover { background: #e9ecef; border-color: #ddd; }
		.mini-editor .editor-content {
			min-height: 150px;
			padding: 8px;
			outline: none;
			line-height: 1.6;
		}
		.mini-editor .editor-content[contenteditable="true"]:focus {
			box-shadow: 0 0 0 2px var(--brand);
			border-radius: 2px;
			outline: none;
			line-height: 1.6;
		}
		#sectionForm p.description {
			font-size: 12px;
			color: var(--muted);
			margin-top: 5px;
			margin-bottom: 10px;
		}
		/* Schedule Table Builder Styles */
		#schedule-table-builder { display: grid; gap: 10px; margin-top: 15px; }
		#schedule-table-builder input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
		.schedule-table-card { border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
		.schedule-table-info h3 { margin: 0; }
		#schedule-table-grid {
			display: grid;
			gap: 5px;
			overflow-x: auto;
			margin-bottom: 10px;
		}
	</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="container">
	<div class="dashboard-header">
		<div>
			<h1>Admin Dashboard</h1>
			<?php if ( ! $event_id ) : ?>
				<h2 style="margin-top: 5px; color: var(--muted);">Editing: Global Site Content (Events Page)</h2>
			<?php elseif ( $event_id && $event_title ) : ?>
				<h2 style="margin-top: 5px; color: var(--muted);">Editing: <?php echo esc_html( $event_title ); ?></h2>
			<?php endif; ?>
		</div>
		<div class="header-actions">
			<a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn btn-secondary">← Back to Events</a>
			<?php if ( $event_id ) : ?>
				<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>" class="btn btn-secondary">View Event Page</a>
			<?php endif; ?>
			<a href="<?php echo wp_logout_url( home_url( '/fossasia-summit/' ) ); ?>" class="btn btn-reject">Logout</a>
		</div>
	</div>
	
	<?php if ( ! $event_id ) : ?>
	<section class="dashboard-section">
		<h2>No Event Selected</h2>
		<p>Please go to the <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Events page</a> and click "Edit Content" on an event card to manage its specific content.</p>
		<p>You can still manage global site settings below.</p>
	</section>
	<?php endif; ?>

	<section class="dashboard-section" id="main-dashboard-content">
		<div class="dashboard-tabs">
			<?php if ( $event_id ) : ?>
				<div class="dashboard-tab active" data-panel="sync">Data Sync</div>
				<div class="dashboard-tab" data-panel="speakers">Manage Speakers</div>
				<div class="dashboard-tab" data-panel="schedule">Manage Schedule</div>
				<div class="dashboard-tab" data-panel="about">About Section</div>
				<div class="dashboard-tab" data-panel="visibility">Section Visibility</div>
				<div class="dashboard-tab" data-panel="sponsors">Manage Sponsors</div>
				<div class="dashboard-tab" data-panel="settings">Site Settings</div>
				<div class="dashboard-tab" data-panel="theme">Theme</div>
				<div class="dashboard-tab" data-panel="sections">Custom Sections</div>
			<?php else : ?>
				<!-- Global tabs that are always visible -->
				<div class="dashboard-tab active" data-panel="global-settings">Global Site Settings</div>
				<div class="dashboard-tab" data-panel="sections">Custom Sections</div>
			<?php endif; ?>
			<div class="dashboard-tab" data-panel="navigation">Manage Navigation</div>
			<!-- CoC is only on the global dashboard -->
			<?php
			if ( ! $event_id ) :
				?>
				<div class="dashboard-tab" data-panel="coc">Code of Conduct</div><?php endif; ?>
		</div>

		<?php if ( $event_id ) : ?>
		<!-- Event-Specific Panels -->
		<div id="panel-sync" class="dashboard-panel active">
			<form id="eventyaySyncForm">
				<h2>Sync with Eventyay</h2>
				<p>Paste the full Eventyay API URL for sessions below to sync speakers. You can get this from the Eventyay API by inspecting network requests or from the event's API documentation.</p>
				<label for="eventyayApiUrl" style="font-weight: bold;">Eventyay Sessions API URL:</label>
				<input type="url" id="eventyayApiUrl" name="eventyay_api_url" style="width: 100%; padding: 8px; margin-top: 5px;" placeholder="e.g., https://api.eventyay.com/v1/events/.../sessions?include=speakers,track&page[size]=200">
				<div style="display: flex; gap: 10px; margin-top: 15px;">
					<button type="submit" class="btn btn-accept">Save URL</button>
					<button type="button" id="syncEventyayBtn" class="btn btn-secondary">Save & Sync Speakers</button>
				</div>
				<p id="syncStatus" style="margin-top: 15px; font-weight: bold;"></p>
			</form>
			<hr style="margin: 20px 0;">
			<h2 style="margin-top: 30px;">Import Sample Data</h2>
			<p>Click this button to populate the current event with sample speakers, sponsors, and settings. <strong>This will overwrite existing data for this event.</strong></p>
			<button id="importSampleDataBtn" class="btn btn-secondary">Import Sample Data</button>
			<p id="syncStatus" style="margin-top: 15px; font-weight: bold;"></p>
		</div>

		<div id="panel-speakers" class="dashboard-panel">
			<h2>Manage Speakers</h2>
			<div class="speaker-controls-header">
				<div>
					<label for="sortSpeakers" style="font-weight:bold;">Sort by:</label>
					<select id="sortSpeakers">
						<option value="name_asc">Name (A-Z)</option>
						<option value="name_desc">Name (Z-A)</option>
						<option value="category_asc">Category (A-Z)</option>
					</select>
				</div>
				<div style="flex-grow: 1;">
					<label for="adminSpeakerSearch" style="font-weight:bold;">Search:</label>
					<input type="text" id="adminSpeakerSearch" placeholder="Search by name, title, category..." style="width: 100%; padding: 6px;">
				</div>
			</div>
			<div class="header-actions" style="margin-top: 20px; justify-content: flex-start;">
				<button id="addNewSpeakerBtn" class="btn btn-accept">Add New Speaker</button>
			</div>
			<div id="speakers-list-admin"></div>
		</div>

		<div id="panel-schedule" class="dashboard-panel">
			<h2>Manage Schedule Table</h2>
			<p>Create or edit the single schedule table for the "Full Schedule" page. Only one table can exist at a time.</p>
			<div id="schedule-table-controls">
				<!-- Controls will be rendered here by JS -->
			</div>
			<div id="schedule-table-preview" style="margin-top: 20px; overflow-x: auto;"></div>
		</div>

		<div id="panel-about" class="dashboard-panel">
			<h2>Edit "About" Section Content</h2>
			<p>Use the editor below to change the content of the "About" section on the main summit page. You can use formatting like bold, italics, lists, and links.</p>
			<form id="aboutSectionForm" style="width: 100%;">
				<?php
				wp_editor(
					$site_settings_data['about_section_content'] ?? '',
					'about_section_content',
					array(
						'textarea_name' => 'about_section_content',
						'media_buttons' => false,
						'textarea_rows' => 20, // Fallback for when JS is disabled
						'tinymce'       => array(
							'height' => 450, // Set the editor height in pixels
						),
					)
				);
				?>
				<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save About Section</button>
			</form>
		</div>

		<div id="panel-visibility" class="dashboard-panel">
			<h2>Manage Section Visibility</h2>
			<p>Use these toggles to show or hide the default sections on the event page.</p>
			<div id="section-visibility-list" style="display: flex; flex-direction: column; gap: 15px;">
				<!-- Visibility toggles will be rendered here by JS -->
			</div>
			<button id="saveVisibilityBtn" class="btn btn-accept" style="margin-top: 20px;">Save Visibility Settings</button>
		</div>

		<div id="panel-sponsors" class="dashboard-panel">
			<div id="sponsors-list">
				<!-- Sponsor groups will be rendered here -->
			</div>
			<div class="header-actions" style="margin-top: 20px;">
				<button id="addSponsorGroupBtn" class="btn btn-accept">Add Sponsor Group</button>
			</div>
		</div>

		<div id="panel-settings" class="dashboard-panel">
			<form id="siteSettingsForm">
				<h3>Event-Specific Logo</h3>
				<p>Override the global site logo for this event. If no logo is set here, the global logo will be used.</p>
				<label for="eventLogo">Logo Image (Recommended: Transparent PNG, max height 36px)</label>
				<p>Current Logo for this Event:</p>
				<img id="currentEventLogo" src="<?php echo esc_url( $site_settings_data['event_logo_url'] ?? ( $global_settings_data['site_logo_url'] ?: plugins_url( '../assets/images/logo.png', __DIR__ . '/../includes/class-wpfaevent-landing.php' ) ) ); ?>" alt="Current Event Logo" style="max-height: 36px; height: auto; background: #eee; padding: 5px; border-radius: 4px; margin-bottom: 10px;">
				<br>
				<label>Update Logo (URL or Upload):</label>
				<input type="text" name="eventLogoURL" placeholder="Enter image URL to override global logo">
				<input type="file" name="eventLogoFile" accept="image/*">
				<br>
				<h3>Hero Section</h3>
				<label for="heroImage">Hero Image (Recommended: 1200x800px)</label>
				<p>Current Image:</p>
				<img id="currentHeroImage" src="" alt="Current Hero Image" style="max-width: 300px; height: auto; border-radius: 8px; margin-bottom: 10px;">
				<br>
				<label>Update Image (URL or Upload):</label>
				<input type="text" name="heroImageURL" placeholder="Enter image URL">
				<input type="file" name="heroImageFile" accept="image/*">
				<br>

				<h3 style="margin-top: 20px;">Call for Speakers Button</h3>
				<label for="cfsButtonText">Button Text:</label>
				<input type="text" name="cfsButtonText" placeholder="e.g., Call for Speakers">
				<label for="cfsButtonLink">Button Link:</label>
				<input type="url" name="cfsButtonLink" placeholder="https://eventyay.com/e/.../cfs">
				<br>

				<h3 style="margin-top: 20px;">Registration Button</h3>
				<label for="regButtonText">Button Text:</label>
				<input type="text" name="regButtonText" placeholder="e.g., Get Tickets">
				<label for="regButtonLink">Button Link:</label>
				<input type="url" name="regButtonLink" placeholder="https://eventyay.com/e/...">

				<h3 style="margin-top: 20px;">Footer</h3>
				<label for="footerText">Footer Text:</label>
				<textarea name="footerText" placeholder="e.g., © FOSSASIA 2025" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px;"></textarea>
				




				<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Settings</button>
			</form>
		</div>
		<div id="panel-theme" class="dashboard-panel">
			<h2>Theme Settings</h2>
			<p>Customize the color palette for <strong>this specific event</strong>. These settings will override the global defaults.</p>
			<form id="themeSettingsForm">
				<label for="brandColor">Brand Color:</label>
				<input type="color" id="brandColor" name="brand_color">
				<label for="backgroundColor">Background Color:</label>
				<input type="color" id="backgroundColor" name="background_color">
				<label for="textColor">Text Color:</label>
				<input type="color" id="textColor" name="text_color">
				<label for="navbarColor">Navbar Background Color:</label>
				<input type="color" id="navbarColor" name="navbar_color">
				<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Theme</button>
			</form>
		</div>
		<?php endif; ?>

		<!-- Global Panels -->
		<?php if ( ! $event_id ) : ?>
		<div id="panel-global-settings" class="dashboard-panel active">
			<form id="globalSiteSettingsForm">
				<h2>Global Site Settings</h2>
				
				<h3 style="margin-top: 20px;">Site Logo</h3>
				<label for="siteLogo">Logo Image (Recommended: Transparent PNG, max height 36px)</label>
				<p>Current Logo:</p>
				<img id="currentSiteLogo" src="<?php echo esc_url( $global_settings_data['site_logo_url'] ?? plugins_url( '../assets/images/logo.png', __DIR__ . '/../includes/class-wpfaevent-landing.php' ) ); ?>" alt="Current Site Logo" style="max-height: 36px; height: auto; background: #eee; padding: 5px; border-radius: 4px; margin-bottom: 10px;">
				<br>
				<label>Update Logo (URL or Upload):</label>
				<input type="text" name="siteLogoURL" placeholder="Enter image URL">
				<input type="file" name="siteLogoFile" accept="image/*">
				<br>

				<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Global Settings</button>
			</form>
		</div>
		<?php endif; ?>
		<div id="panel-sections" class="dashboard-panel <?php echo $event_id ? '' : ''; ?>">
			<div id="sections-list">
				<!-- Custom sections will be rendered here -->
			</div>
			<div class="header-actions" style="margin-top: 20px;">
				<button id="addMediaSectionBtn" class="btn btn-accept">Add New Media Section</button>
				<button id="addSectionBtn" class="btn btn-accept">Add New Text/Layout Section</button>
			</div>
		</div>

		<div id="panel-navigation" class="dashboard-panel">
			<div id="nav-items-list">
				<!-- Navigation items will be rendered here -->
			</div>
			<form id="addNavItemForm" style="display: none; flex-wrap: wrap;">
				<h3 id="navFormTitle">Add New Item</h3>
				<input type="hidden" id="navParentId" name="navParentId">
				<input type="hidden" id="navItemId" name="navItemId">
				<div id="navItemTypeToggle" style="display: flex; gap: 20px; margin-bottom: 15px;">
					<label><input type="radio" name="navItemType" value="link" checked> Direct Link</label>
					<label><input type="radio" name="navItemType" value="dropdown"> Dropdown Menu</label>
				</div>
				<input type="text" id="navText" name="navText" placeholder="Item Text (e.g., About)" required style="margin-right: 10px;">
				<select id="navHref" name="navHref" required style="margin-right: 10px;"></select>
				<button type="submit" class="btn btn-accept">Add Item</button>
				<button type="button" class="btn btn-secondary" id="cancelNavEditBtn" style="display: none;">Cancel Edit</button>
			</form>
		</div>

		<?php if ( ! $event_id ) : ?>
		<div id="panel-coc" class="dashboard-panel">
			<h2>Edit Code of Conduct</h2>
			<p>Use the editor below to change the content of the Code of Conduct page. This content is global and applies to all events.</p>
			<form id="cocForm" style="width: 100%;">
				<?php
				wp_editor(
					$coc_content_data['content'] ?? '',
					'coc_content_editor',
					array(
						'textarea_name' => 'coc_content',
						'media_buttons' => false,
						'textarea_rows' => 20,
						'tinymce'       => array(
							'height' => 450,
						),
					)
				);
				?>
				<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Code of Conduct</button>
			</form>
		</div>
		<?php endif; ?>

	</section>
</div>

<!-- Edit Speaker Modal (for Admin Panel) -->
<div id="editSpeakerModal" class="modal" style="display: <?php echo $event_id ? 'none' : 'none'; ?>;">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="editSpeakerForm">
			<h2 id="editSpeakerModalTitle">Edit Speaker</h2>
			<input type="hidden" name="speakerId">
			<div class="form-section-heading">Speaker Info</div>
			<label>Name:</label><input type="text" name="name" required>
			<label>Title (e.g., "Developer at Company"):</label><input type="text" name="title" required>
			<label>Category/Track:</label><input type="text" name="category" required>
			<label>Image:</label>
			<div class="image-option-toggle">
				<label><input type="radio" name="image_source" value="url" checked> URL</label>
				<label><input type="radio" name="image_source" value="upload"> Upload</label>
			</div>
			<input type="url" name="image_url" placeholder="https://example.com/photo.jpg" required>
			<input type="file" name="image_upload" accept="image/*" style="display:none;">
			<label>Bio:</label><textarea name="bio" rows="4" required></textarea>
			
			<div class="form-section-heading">Session Info</div>
			<label>Talk Title:</label><input type="text" name="talkTitle" >
			<label>Date:</label><input type="date" name="talkDate" required>
			<label>Start Time:</label><input type="time" name="talkTime" required>
			<label>End Time:</label><input type="time" name="talkEndTime" required>
			
			<div class="form-section-heading">Social Links (Optional)</div>
			<input type="url" name="linkedin" placeholder="LinkedIn URL"><input type="url" name="twitter" placeholder="Twitter URL"><input type="url" name="github" placeholder="GitHub URL"><input type="url" name="website" placeholder="Website URL">
			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Changes</button>
		</div>
	</div>
</div>

<!-- Add Sponsor Group Modal -->
<div id="addSponsorGroupModal" class="modal" style="display: <?php echo $event_id ? 'none' : 'none'; ?>;">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="addSponsorGroupForm">
			<h2>Add New Sponsor Group</h2>
			<label for="groupName">Group Name:</label>
			<input type="text" id="groupName" name="groupName" required>
			
			<label for="sponsorCount">Number of Sponsors:</label>
			<input type="number" id="sponsorCount" name="sponsorCount" min="1" value="1" required>

			<div id="sponsor-fields-container">
				<!-- Dynamic fields will be inserted here -->
			</div>

			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Add Group</button>
		</form>
	</div>
</div>

<!-- Add/Edit Schedule Table Modal -->
<div id="scheduleTableModal" class="modal" style="display: <?php echo $event_id ? 'none' : 'none'; ?>;">
	<div class="modal-content" style="max-width: 90vw; width: 1200px;">
		<span class="close-btn">&times;</span>
		<form id="scheduleTableForm">
			<h2 id="scheduleTableModalTitle">Add New Schedule Table</h2>
			<input type="hidden" name="tableId">

			<fieldset>
				<legend>Table Settings</legend>
				<label>Table Name (e.g., "Day 1 - Main Track"):</label>
				<input type="text" name="tableName" required>
				<div id="table-dimensions" style="display: flex; gap: 20px; margin-top: 10px;">
					<div><label>Rows:</label><input type="number" name="tableRows" min="1" value="3" required></div>
					<div><label>Columns:</label><input type="number" name="tableCols" min="1" value="4" required></div>
				</div>
			</fieldset>

			<fieldset><legend>Table Content</legend><div id="schedule-table-grid"></div></fieldset>

			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Table</button>
		</form>
	</div>
</div>

<!-- Add New Speaker Modal -->
<div id="newSpeakerModal" class="modal" style="display: <?php echo $event_id ? 'none' : 'none'; ?>;">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="newSpeakerForm">
			<h2>Add New Speaker</h2>
			<div class="form-section-heading">Speaker Info</div>
			<label>Name:</label><input type="text" name="name" required>
			<label>Title (e.g., "Developer at Company"):</label><input type="text" name="title" required>
			<label>Category/Track:</label><input type="text" name="category" required>
			<label>Image:</label>
			<div class="image-option-toggle">
				<label><input type="radio" name="image_source" value="url" checked> URL</label>
				<label><input type="radio" name="image_source" value="upload"> Upload</label>
			</div>
			<input type="url" name="image_url" placeholder="https://example.com/photo.jpg" required>
			<input type="file" name="image_upload" accept="image/*" style="display:none;">
			<label>Bio:</label><textarea name="bio" rows="4" required></textarea>
			
			<div class="form-section-heading">Session Info</div>
			<label>Talk Title:</label><input type="text" name="talkTitle" required>
			<label>Date:</label><input type="date" name="talkDate" required>
			<label>Start Time:</label><input type="time" name="talkTime" required>
			<label>End Time:</label><input type="time" name="talkEndTime" required>
			
			<div class="form-section-heading">Social Links (Optional)</div>
			<input type="url" name="linkedin" placeholder="LinkedIn URL"><input type="url" name="twitter" placeholder="Twitter URL"><input type="url" name="github" placeholder="GitHub URL"><input type="url" name="website" placeholder="Website URL">
			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Add Speaker</button>
		</form>
	</div>
</div>

<!-- Add Sponsor to Group Modal -->
<div id="addSponsorModal" class="modal" style="display: <?php echo $event_id ? 'none' : 'none'; ?>;">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="addSponsorForm">
			<h2>Add Sponsor to Group</h2>
			<label>Sponsor Name:</label>
			<input type="text" name="sponsorName" required>
			<label>Sponsor Link:</label>
			<input type="url" name="sponsorLink" required>
			<label>Sponsor Image (URL or Upload):</label>
			<input type="text" name="sponsorImageURL" placeholder="Enter image URL">
			<input type="file" name="sponsorImageFile" accept="image/*">
			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Add Sponsor</button>
		</form>
	</div>
</div>

<!-- Add/Edit Section Modal -->
<div id="sectionModal" class="modal">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="sectionForm">
			<h2 id="sectionModalTitle">Add New Section</h2>
			<input type="hidden" name="sectionId">

			<fieldset>
				<legend>General Settings</legend>
				<label>Section Title (e.g., "Venue & Travel"):</label>
				<input type="text" name="sectionTitle">
				<label>Section Subtitle (small text next to the title):</label>
				<input type="text" name="sectionSubtitle" placeholder="e.g., In-person & hybrid">
				<label>Position on Page:</label>
				<select name="sectionPosition" required></select>
				<label>Display Order (lower numbers appear first):</label>
				<input type="number" name="sectionOrder" value="10" required>
				<label><input type="checkbox" name="sectionIsActive" checked> Active</label>
			</fieldset>

			<fieldset>
				<legend>Layout & Content</legend>
				<label>Layout Style:</label>
				<select name="layoutStyle">
					<option value="two_col_media_left">Two Column - Media Left</option>
					<option value="two_col_media_right">Two Column - Media Right</option>
					<option value="full_width">Full Width Text</option>
				</select>

				<div id="content-column-fields">
					<label>Content Title (optional H3 heading):</label>
					<input type="text" name="contentTitle">
					<label for="sectionContentBody">Content Body:</label>
					<?php
					wp_editor(
						'',
						'sectionContentBody',
						array(
							'textarea_name' => 'sectionContentBody',
							'media_buttons' => true, // Enable the "Add Media" button
							'textarea_rows' => 15,
							'tinymce'       => array(
								'height' => 350,
							),
						)
					);
					?>
					<label>Button Text (optional):</label>
					<input type="text" name="buttonText" placeholder="e.g., Learn More">
					<label>Button Link (optional):</label>
					<input type="url" name="buttonLink" placeholder="https://example.com">
				</div>
			</fieldset>

			<fieldset id="media-column-fields">
				<legend>Media Column</legend>
				<label>Media Title (optional H3 heading):</label>
				<input type="text" name="mediaTitle">
				<label>Media Type: 
					<label><input type="radio" name="mediaType" value="photo" checked> Photo</label> 
					<label><input type="radio" name="mediaType" value="map"> Map</label>
				</label>
				<div id="photo-fields"><label>Photo (URL or Upload):</label><input type="text" name="photoUrl" placeholder="Enter image URL"><input type="file" name="photoUpload" accept="image/*"></div>
				<div id="map-fields" style="display: none;">
					<label>Map Embed URL (the 'src' from an iframe):</label><input type="url" name="mapEmbedUrl" placeholder="https://www.google.com/maps/embed?pb=...">
					<p class="description">From Google Maps, click "Share", then "Embed a map", and copy the URL from the `src="..."` attribute in the iframe code. Pasting the full iframe code also works.</p>
				</div>
				<div id="video-fields" style="display: none;"><label>Video Embed URL (YouTube, Vimeo, etc.):</label><input type="url" name="videoEmbedUrl" placeholder="https://www.youtube.com/watch?v=..."></div>
				<div id="carousel-fields" style="display: none;">
					<label>Number of Slides:</label><input type="number" name="carouselSlidesCount" min="1" value="3">
					<div class="carousel-uploads"></div>
					<label>Slide Duration (seconds):</label><input type="number" name="carouselTimer" value="5" min="1"></div>
			</fieldset>

			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Section</button>
				</div>
			</fieldset>

			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Section</button>
		</form>
	</div>
</div>

<!-- Add/Edit Media Section Modal -->
<div id="mediaSectionModal" class="modal">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="mediaSectionForm">
			<h2 id="mediaSectionModalTitle">Add New Media Section</h2>
			<input type="hidden" name="sectionId">

			<fieldset>
				<legend>General Settings</legend>
				<label>Section Title (optional, e.g., "Event Highlights"):</label>
				<input type="text" name="sectionTitle">
				<label>Position on Page:</label>
				<select name="sectionPosition" required></select>
				<label>Display Order (lower numbers appear first):</label>
				<input type="number" name="sectionOrder" value="10" required>
				<label><input type="checkbox" name="sectionIsActive" checked> Active</label>
			</fieldset>

			<fieldset>
				<legend>Media Content</legend>
				<label>Media Type: 
					<label><input type="radio" name="mediaType" value="photo" checked> Single Photo</label> 
					<label><input type="radio" name="mediaType" value="video"> Video</label>
					<label><input type="radio" name="mediaType" value="carousel"> Image Carousel</label>
				</label>
				<div id="ms-photo-fields"><label>Photo (URL or Upload):</label><input type="text" name="photoUrl" placeholder="Enter image URL"><input type="file" name="photoUpload" accept="image/*"></div>
				<div id="ms-video-fields" style="display: none;"><label>Video Embed URL (YouTube, Vimeo, etc.):</label><input type="url" name="videoEmbedUrl" placeholder="https://www.youtube.com/watch?v=..."></div>
				<div id="ms-carousel-fields" style="display: none;">
					<label>Number of Slides:</label><input type="number" name="carouselSlidesCount" min="1" value="3">
					<div class="carousel-uploads"></div>
					<label>Slide Duration (seconds):</label><input type="number" name="carouselTimer" value="5" min="1">
				</div>
			</fieldset>

			<button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Media Section</button>
		</form>
	</div>
</div>

<!-- Custom Alert/Confirm Modal -->
<div id="customAlertModal" class="modal">
	<div class="modal-content">
		<h2 id="customAlertTitle">Confirmation</h2>
		<p id="customAlertMessage">Are you sure?</p>
		<div id="customAlertButtons">
			<button id="customAlertCancel" class="btn btn-secondary">Cancel</button>
			<button id="customAlertOk" class="btn btn-primary">OK</button>
		</div>
	</div>
</div>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const adminNonce = '<?php echo wp_create_nonce( 'fossasia_admin_nonce' ); ?>';
	const ajaxUrl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	const eventId = <?php echo $event_id; ?>;
	
	// --- Data Store ---
	const store = {
		speakers: <?php echo json_encode( $speakers_data ); ?>,
		sponsors: <?php echo json_encode( $sponsors_data ); ?>,
		settings: <?php echo json_encode( $site_settings_data ); ?>,
		sections: <?php echo json_encode( $custom_sections_data ); ?>,
		navigation: <?php echo json_encode( $navigation_data ); ?>,
		schedule: <?php echo json_encode( $schedule_data ); ?>,
		theme: <?php echo json_encode( $theme_settings_data ); ?>
	};

	// --- Utility Functions ---
	const escapeHTML = (str) => {
		if (typeof str !== 'string') return '';
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	};

	const getElement = (id) => document.getElementById(id);

	const saveStore = async (key, actionName) => {
		const formData = new FormData();
		formData.append('action', actionName);
		formData.append('nonce', adminNonce);
		formData.append('task', 'save_all');
		formData.append(key, JSON.stringify(store[key]));
		if (eventId) {
			formData.append('event_id', eventId);
		}

		try {
			const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const data = await response.json();
			if (!data.success) {
				alert(`Error saving ${key}: ${data.data}`);
			}
			return data;
		} catch (error) {
			console.error(`Error saving ${key}:`, error);
			alert(`An unexpected error occurred while saving ${key}.`);
			return { success: false };
		}
	};

	// A more direct save function for specific cases.
	// This is defined in the higher scope so multiple modules can use it.
	const directSave = async (key, dataObject, actionName) => {
		const formData = new FormData(); // The key is 'settings', the dataObject is what we want to save.
		formData.append('action', actionName);
		formData.append('nonce', adminNonce);
		formData.append('task', 'save_all');
		// The bug is here. It should be sending dataObject, not the entire store[key].
		formData.append(key, JSON.stringify(dataObject));
		if (eventId) {
			formData.append('event_id', eventId);
		}

		try {
			const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const data = await response.json();
			if (!data.success) {
				alert(`Error saving ${key}: ${data.data}`);
			}
			return data;
		} catch (error) {
			console.error(`Error saving ${key}:`, error);
			alert(`An unexpected error occurred while saving ${key}.`);
			return { success: false };
		}
	};

	// --- Custom Alert/Confirm Modal Logic ---
	const customAlertModal = getElement('customAlertModal');
	const customAlert = {
		title: customAlertModal.querySelector('#customAlertTitle'),
		message: customAlertModal.querySelector('#customAlertMessage'),
		okBtn: customAlertModal.querySelector('#customAlertOk'),
		cancelBtn: customAlertModal.querySelector('#customAlertCancel'),
		show(title, message, onConfirm, showCancel = false) {
			this.title.textContent = title;
			this.message.innerHTML = message; // Use innerHTML to allow for simple formatting
			this.cancelBtn.style.display = showCancel ? 'inline-flex' : 'none';
			customAlertModal.style.display = 'flex';

			return new Promise((resolve) => {
				this.okBtn.onclick = () => {
					customAlertModal.style.display = 'none';
					if (typeof onConfirm === 'function') onConfirm();
					resolve(true);
				};
				this.cancelBtn.onclick = () => {
					customAlertModal.style.display = 'none';
					resolve(false);
				};
				// Also close on background click
				window.onclick = (event) => {
					if (event.target == customAlertModal) {
						customAlertModal.style.display = 'none';
						resolve(false); // Treat background click as cancel
					}
				};
			});
		},
		alert(message, title = 'Alert') {
			return this.show(title, message, null, false);
		}
	};

	// --- Tab Management ---
	(() => {
		const tabs = document.querySelectorAll('.dashboard-tab');
		const panels = document.querySelectorAll('.dashboard-panel');
		tabs.forEach(tab => {
			tab.addEventListener('click', () => {
				const panelId = 'panel-' + tab.dataset.panel;
				tabs.forEach(t => t.classList.remove('active'));
				tab.classList.add('active');
				panels.forEach(p => p.classList.toggle('active', p.id === panelId));
			});
		});
	})();

	// --- Event-Specific Logic ---
	if (eventId > 0) {

	// --- Site Settings Logic (Event Specific) ---
	(() => {
		const form = getElement('siteSettingsForm');
		const heroImagePreview = getElement('currentHeroImage');
		const eventLogoPreview = getElement('currentEventLogo');
		if (!form || !heroImagePreview) return;

		const render = () => {
			// All settings in this form are now event-specific.
			heroImagePreview.src = store.settings.hero_image_url || '<?php echo esc_url( get_the_post_thumbnail_url( $event_id, 'large' ) ); ?>' || '';
			form.querySelector('[name="cfsButtonText"]').value = store.settings.cfs_button_text || 'Call for Speakers';
			form.querySelector('[name="cfsButtonLink"]').value = store.settings.cfs_button_link || '';
			form.querySelector('[name="regButtonText"]').value = store.settings.reg_button_text || 'Get Tickets';
			form.querySelector('[name="regButtonLink"]').value = store.settings.reg_button_link || '';
			form.querySelector('[name="footerText"]').value = store.settings.footer_text || '';
			// The event logo preview needs to check the event-specific setting first. The path was corrected from a non-existent fossasia-landing.php.
			eventLogoPreview.src = store.settings.event_logo_url || '<?php echo esc_url( $global_settings_data['site_logo_url'] ?: plugins_url( '../assets/images/logo.png', __DIR__ . '/../includes/class-wpfaevent-landing.php' ) ); ?>';
		};

		const handleSettingsSubmit = async (e) => {
			e.preventDefault();
			const formData = new FormData(form);
			
			// Prepare settings data from form fields
			const settingsToSave = {
				hero_image_url: formData.get('heroImageURL') || store.settings.hero_image_url,
				event_logo_url: formData.get('eventLogoURL') || store.settings.event_logo_url,
				cfs_button_text: formData.get('cfsButtonText'),
				cfs_button_link: formData.get('cfsButtonLink'),
				reg_button_text: formData.get('regButtonText'),
				reg_button_link: formData.get('regButtonLink'),
				footer_text: formData.get('footerText'),
			};

			const ajaxFormData = new FormData();
			ajaxFormData.append('action', 'fossasia_manage_site_settings');
			ajaxFormData.append('nonce', adminNonce);
			ajaxFormData.append('event_id', eventId);
			ajaxFormData.append('settings', JSON.stringify(settingsToSave));

			// Append files if they exist
			const eventLogoFile = formData.get('eventLogoFile');
			if (eventLogoFile && eventLogoFile.size > 0) {
				ajaxFormData.append('eventLogoFile', eventLogoFile);
			}

			// Note: Hero image is handled via post thumbnail, this form part seems to be for overriding it with a URL.
			// The logic for hero image file upload can be added here if needed, similar to the logo.

			const response = await fetch(ajaxUrl, { method: 'POST', body: ajaxFormData });
			const result = await response.json();

			if (result.success) {
				// The backend now returns the updated settings, including new logo URL
				store.settings = { ...store.settings, ...result.data.settings };
				customAlert.alert('Settings saved successfully.', 'Success');
				render(); // Re-render to show new logo and text
			} else {
				customAlert.alert('Error saving settings: ' + (result.data || 'Unknown error'), 'Error');
			}
		};

		form.addEventListener('submit', handleSettingsSubmit);

		render();
	})();
	// --- About Section Logic ---
	(() => {
		const form = getElement('aboutSectionForm');
		if (!form) return;

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';

			const content = tinymce.get('about_section_content') ? tinymce.get('about_section_content').getContent() : getElement('about_section_content').value;
			// We no longer use the global store for this, we send it directly.
			const settingsToSave = { about_section_content: content };
			
			// We use a more direct save method here instead of the generic saveStore
			const result = await directSave('settings', settingsToSave, 'fossasia_manage_site_settings');

			if (result.success) {
				customAlert.alert('About section content saved successfully.', 'Success');
			}
			submitBtn.disabled = false;
			submitBtn.textContent = 'Save About Section';
		});
	})();

	// --- Section Visibility Logic ---
	(() => {
		const container = getElement('section-visibility-list');
		const saveBtn = getElement('saveVisibilityBtn');
		if (!container || !saveBtn) return;

		const defaultSections = [
			{ id: 'about', name: 'About Section' },
			{ id: 'speakers', name: 'Speakers Section' },
			{ id: 'schedule', name: 'Schedule Overview' },
			{ id: 'sponsors', name: 'Sponsors Section' }
		];

		const render = () => {
			container.innerHTML = '';
			const visibilitySettings = store.settings.section_visibility || {};

			defaultSections.forEach(section => {
				const isVisible = visibilitySettings[section.id] !== false; // Default to true if not set
				const item = document.createElement('div');
				item.style.display = 'flex';
				item.style.alignItems = 'center';
				item.style.justifyContent = 'space-between';
				item.style.padding = '10px';
				item.style.border = '1px solid #eee';
				item.style.borderRadius = '8px';
				item.innerHTML = `
					<span style="font-weight: 600;">${section.name}</span>
					<label class="switch">
						<input type="checkbox" class="visibility-toggle" data-section-id="${section.id}" ${isVisible ? 'checked' : ''}>
						<span class="slider"></span>
					</label>
				`;
				container.appendChild(item);
			});
		};

		saveBtn.addEventListener('click', async () => {
			const newVisibility = {};
			container.querySelectorAll('.visibility-toggle').forEach(toggle => {
				newVisibility[toggle.dataset.sectionId] = toggle.checked;
			});
			store.settings.section_visibility = newVisibility;
			await saveStore('settings', 'fossasia_manage_site_settings');
			customAlert.alert('Visibility settings saved!', 'Success');
		});

		render();
	})();
	// --- Schedule Table Management, Data Sync, Speaker Management, etc. would go here inside the if(eventId > 0) block ---
	}

	// --- Global Site Settings Logic ---
	(() => {
		const form = getElement('globalSiteSettingsForm');
		if (!form) return;

		// This function now sends files directly to the backend
		const handleGlobalSettingsSubmit = async (e) => {
			e.preventDefault();
			const formData = new FormData(form);
			const settingsToSave = {
				site_logo_url: formData.get('siteLogoURL') || '<?php echo esc_url( $global_settings_data['site_logo_url'] ); ?>'
			};

			const ajaxFormData = new FormData();
			ajaxFormData.append('action', 'fossasia_manage_site_settings');
			ajaxFormData.append('nonce', adminNonce);
			ajaxFormData.append('settings', JSON.stringify(settingsToSave));

			const siteLogoFile = formData.get('siteLogoFile');
			if (siteLogoFile && siteLogoFile.size > 0) {
				ajaxFormData.append('siteLogoFile', siteLogoFile);
			}

			const response = await fetch(ajaxUrl, { method: 'POST', body: ajaxFormData });
			const result = await response.json();

			if (result.success) {
				customAlert.alert('Global settings saved successfully.', 'Success');
				getElement('currentSiteLogo').src = result.data.settings.site_logo_url;
			} else {
				customAlert.alert('Error saving settings: ' + (result.data || 'Unknown error'), 'Error');
			}
		};
		form.addEventListener('submit', handleGlobalSettingsSubmit);
	})();
	<?php if ( ! $event_id ) : ?>
	// --- Code of Conduct Logic ---
	(() => {
		const form = getElement('cocForm');
		if (!form) return;

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';

			const content = tinymce.get('coc_content_editor') ? tinymce.get('coc_content_editor').getContent() : getElement('coc_content_editor').value;
			
			const formData = new FormData();
			formData.append('action', 'fossasia_manage_coc');
			formData.append('nonce', adminNonce);
			formData.append('coc_content', content);

			const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const data = await response.json();

			if (data.success) {
				customAlert.alert(data.data.message, 'Success');
			} else {
				customAlert.alert('Error: ' + data.data, 'Error');
			}
			submitBtn.disabled = false;
			submitBtn.textContent = 'Save Code of Conduct';
		});
	})();
	<?php endif; ?>

	if (eventId > 0) {
	// --- Schedule Table Management ---
	(() => {
		const controlsContainer = getElement('schedule-table-controls');
		const previewContainer = getElement('schedule-table-preview');
		const modal = getElement('scheduleTableModal');
		const form = getElement('scheduleTableForm');
		const gridContainer = getElement('schedule-table-grid');
		const closeBtn = modal.querySelector('.close-btn');
		let isEditing = false;

		if (!controlsContainer || !modal) return;

		const render = () => {
			controlsContainer.innerHTML = '';
			previewContainer.innerHTML = '';
			const table = store.schedule;

			if (table && table.name) {
				// Table exists
				controlsContainer.innerHTML = `
					<button id="editScheduleTableBtn" class="btn btn-edit">Edit Table</button>
					<button id="deleteScheduleTableBtn" class="btn btn-reject">Delete Table</button>
				`;
				previewContainer.innerHTML = `<h4>Preview: ${escapeHTML(table.name)}</h4><p>${table.rows} rows &times; ${table.cols} columns</p>`;
				getElement('editScheduleTableBtn').addEventListener('click', () => openModal(true));
				getElement('deleteScheduleTableBtn').addEventListener('click', async () => {
					if (!await customAlert.show('Delete Schedule', 'Are you sure you want to delete the schedule table?', null, true)) return;
					store.schedule = {};
					await saveStore('schedule', 'fossasia_manage_schedule');
					render();
				});
			} else {
				// No table exists
				controlsContainer.innerHTML = `
					<p>No schedule table exists.</p>
					<button id="createScheduleTableBtn" class="btn btn-accept">Create Table</button>
				`;
				getElement('createScheduleTableBtn').addEventListener('click', () => openModal(false));
			}
		};

		const generateGrid = (rows, cols, data = []) => {
			gridContainer.innerHTML = '';
			gridContainer.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
			for (let r = 0; r < rows; r++) {
				for (let c = 0; c < cols; c++) {
					const cellContent = (data[r] && typeof data[r][c] !== 'undefined') ? data[r][c] : '';
					const input = document.createElement('textarea');
					input.rows = 4;
					input.placeholder = `Row ${r+1}, Col ${c+1}`;
					input.dataset.row = r;
					input.dataset.col = c;
					input.value = cellContent; // Use value for textarea
					gridContainer.appendChild(input);
				}
			}
		};

		const openModal = (isEditingMode) => {
			isEditing = isEditingMode;
			form.reset();
			const rowsInput = form.querySelector('[name="tableRows"]');
			const colsInput = form.querySelector('[name="tableCols"]');
			const dimensionsDiv = getElement('table-dimensions');

			if (isEditing) {
				const table = store.schedule;
				getElement('scheduleTableModalTitle').textContent = 'Edit Schedule Table';
				form.querySelector('[name="tableName"]').value = table.name;
				rowsInput.value = table.rows;
				colsInput.value = table.cols;
				dimensionsDiv.style.display = 'none'; // Don't allow changing dimensions on edit
				generateGrid(table.rows, table.cols, table.data);
			} else {
				getElement('scheduleTableModalTitle').textContent = 'Add New Schedule Table';
				dimensionsDiv.style.display = 'flex';
				generateGrid(parseInt(rowsInput.value), parseInt(colsInput.value));
			}
			modal.style.display = 'flex';
		};

		closeBtn.addEventListener('click', () => modal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

		form.querySelector('[name="tableRows"]').addEventListener('change', () => {
			if (!isEditing) generateGrid(parseInt(form.querySelector('[name="tableRows"]').value), parseInt(form.querySelector('[name="tableCols"]').value));
		});
		form.querySelector('[name="tableCols"]').addEventListener('change', () => {
			if (!isEditing) generateGrid(parseInt(form.querySelector('[name="tableRows"]').value), parseInt(form.querySelector('[name="tableCols"]').value));
		});

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';

			const rows = parseInt(form.querySelector('[name="tableRows"]').value);
			const cols = parseInt(form.querySelector('[name="tableCols"]').value);
			const tableData = [];
			for (let r = 0; r < rows; r++) {
				const rowData = [];
				for (let c = 0; c < cols; c++) {
					const cell = gridContainer.querySelector(`textarea[data-row="${r}"][data-col="${c}"]`);
					rowData.push(cell.value);
				}
				tableData.push(rowData);
			}

			store.schedule = {
				name: form.querySelector('[name="tableName"]').value,
				rows: rows, cols: cols, data: tableData
			};

			const result = await saveStore('schedule', 'fossasia_manage_schedule');
			if (result.success) {
				customAlert.alert('Schedule table saved successfully.', 'Success');
			}

			submitBtn.disabled = false;
			submitBtn.textContent = 'Save Table';
			modal.style.display = 'none';
			render();
		});

		render();
	})();

	// --- Sample Data Import Logic ---
	(() => {
		const importBtn = getElement('importSampleDataBtn');
		const syncStatus = getElement('syncStatus'); // Reuse the status paragraph
		if (!importBtn) return;

		importBtn.addEventListener('click', async () => {
			if (!await customAlert.show('Import Sample Data', 'Are you sure you want to import sample data? This will overwrite all existing speakers, sponsors, and settings for this event.', null, true)) return;

			syncStatus.textContent = 'Importing sample data...';
			syncStatus.style.color = 'orange';
			importBtn.disabled = true;

			const formData = new FormData();
			formData.append('action', 'fossasia_import_sample_data');
			formData.append('nonce', adminNonce);
			formData.append('event_id', eventId);

			const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
			const data = await response.json();

			if (data.success) {
				await customAlert.alert(data.data.message + '<br><br>The page will now reload to reflect the changes.', 'Success');
				window.location.reload();
			} else {
				customAlert.alert('Error: ' + data.data, 'Import Failed');
				importBtn.disabled = false;
			}
		});
	})();

	// --- Data Sync Logic ---
	(() => {
		const syncBtn = getElement('syncEventyayBtn');
		const syncForm = getElement('eventyaySyncForm');
		const syncStatus = getElement('syncStatus');
		if (!syncForm || !syncBtn) return;

		// Populate the input with the saved value
		const apiUrlInput = getElement('eventyayApiUrl');
		apiUrlInput.value = store.settings.eventyay_api_url || '';

		const saveUrl = async () => {
			const newUrl = apiUrlInput.value;
			// Use the directSave utility to update just this one setting
			const result = await directSave('settings', { eventyay_api_url: newUrl }, 'fossasia_manage_site_settings');
			if (result.success) {
				store.settings.eventyay_api_url = newUrl; // Update local store
			}
			return result.success;
		};

		const runSync = async () => {
			syncStatus.textContent = 'Syncing... Please wait.';
			syncStatus.style.color = 'orange';
			syncBtn.disabled = true;
			syncForm.querySelector('button[type="submit"]').disabled = true;

			const formData = new FormData();
			formData.append('action', 'fossasia_sync_eventyay');
			formData.append('nonce', adminNonce);
			formData.append('event_id', eventId);

			// The PHP backend will now read the saved URL
			try {
				const response = await fetch(ajaxUrl, { method: 'POST', body: formData });
				const data = await response.json();
				if (data.success) {
					syncStatus.textContent = data.data.message + ' The page will now reload.';
					syncStatus.style.color = 'green';
					setTimeout(() => window.location.reload(), 2000);
				} else {
					syncStatus.textContent = 'Error: ' + data.data;
					syncStatus.style.color = 'red';
					syncBtn.disabled = false;
					syncForm.querySelector('button[type="submit"]').disabled = false;
				}
			} catch (err) {
				syncStatus.textContent = 'An unexpected error occurred. Check the console.';
				syncStatus.style.color = 'red';
				syncBtn.disabled = false;
				syncForm.querySelector('button[type="submit"]').disabled = false;
				console.error(err);
			}
		};

		syncForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			if (await saveUrl()) {
				customAlert.alert('Eventyay API URL saved successfully.', 'Success');
			}
		});

		syncBtn.addEventListener('click', async () => {
			if (await saveUrl()) {
				runSync();
			} else {
				customAlert.alert('Could not save the URL. Please try again.', 'Error');
			}
		});
	})();

	// --- Speaker Management Logic (Admin Panel) ---
	(() => {
		const container = getElement('speakers-list-admin');
		const sortSelect = getElement('sortSpeakers');
		const searchInput = getElement('adminSpeakerSearch');
		const addNewSpeakerBtn = getElement('addNewSpeakerBtn');
		if (!container || !sortSelect || !searchInput) return;

		const render = () => {
			container.innerHTML = '';
			const searchTerm = searchInput.value.toLowerCase();
			const sortValue = sortSelect.value;
			
			const featuredSpeakers = store.speakers.filter(s => s.featured).sort((a, b) => (a.featured_order || 0) - (b.featured_order || 0));
			let nonFeaturedSpeakers = store.speakers.filter(s => !s.featured);

			nonFeaturedSpeakers.sort((a, b) => {
				if (sortValue === 'name_desc') return (b.name || '').localeCompare(a.name || '');
				if (sortValue === 'category_asc') return (a.category || '').localeCompare(b.category || '') || (a.name || '').localeCompare(b.name || '');
				return (a.name || '').localeCompare(b.name || '');
			});

			let speakersToRender = [...featuredSpeakers, ...nonFeaturedSpeakers].filter(s => 
				(s.name && s.name.toLowerCase().includes(searchTerm)) ||
				(s.title && s.title.toLowerCase().includes(searchTerm)) ||
				(s.category && s.category.toLowerCase().includes(searchTerm))
			);

			if (speakersToRender.length === 0) {
				container.innerHTML = '<p>No speakers found.</p>';
				return;
			}

			const featuredCount = featuredSpeakers.length;

			speakersToRender.forEach(speaker => {
				const card = document.createElement('div');
				card.className = 'speaker-card-admin';
				
				let orderControls = '';
				if (speaker.featured) {
					const speakerIndex = featuredSpeakers.findIndex(s => s.id === speaker.id);
					const upDisabled = speakerIndex === 0 ? 'disabled' : '';
					const downDisabled = speakerIndex === featuredSpeakers.length - 1 ? 'disabled' : '';
					orderControls = `
						<button class="btn btn-secondary btn-order-up" data-id="${speaker.id}" ${upDisabled}>↑</button>
						<button class="btn btn-secondary btn-order-down" data-id="${speaker.id}" ${downDisabled}>↓</button>
					`;
				}

				card.innerHTML = `
					<img src="${escapeHTML(speaker.image)}" alt="${escapeHTML(speaker.name)}">
					<div class="speaker-card-info">
						<h3>${escapeHTML(speaker.name)}</h3>
						<p>${escapeHTML(speaker.title)}<br><strong>Category:</strong> ${escapeHTML(speaker.category)}</p>
					</div>
					<div class="speaker-card-controls">
						<label style="font-weight:600; margin-right: 5px;">Featured 
							<span style="color:green; font-weight:bold;">(${featuredCount})</span>:
						</label>
						<label class="switch">
							<input type="checkbox" class="featured-toggle" data-id="${speaker.id}" ${speaker.featured ? 'checked' : ''}>
							<span class="slider"></span>
						</label>
						${orderControls}
						<button class="btn btn-edit btn-edit-speaker" data-id="${speaker.id}">Edit</button>
						<button class="btn btn-reject btn-delete-speaker" data-id="${speaker.id}" data-name="${speaker.name}">Delete</button>
					</div>
				`;
				container.appendChild(card);
			});
		};

		sortSelect.addEventListener('change', render);
		searchInput.addEventListener('keyup', render);

		container.addEventListener('change', async (e) => {
			if (e.target.matches('.featured-toggle')) {
				const speakerId = e.target.dataset.id;
				const speaker = store.speakers.find(s => s.id === speakerId);
				if (speaker) {
					speaker.featured = e.target.checked;
					if (speaker.featured) {
						// Assign a new order number when featured
						const maxOrder = Math.max(0, ...store.speakers.filter(s => s.featured).map(s => s.featured_order || 0));
						speaker.featured_order = maxOrder + 1;
					} else {
						// Remove order number when un-featured
						delete speaker.featured_order;
					}
					await saveStore('speakers', 'fossasia_manage_speakers');
					render();
				}
			}
		});

		container.addEventListener('click', async (e) => {
			if (e.target.matches('.btn-order-up') || e.target.matches('.btn-order-down')) {
				const speakerId = e.target.dataset.id;
				const direction = e.target.matches('.btn-order-up') ? -1 : 1;
				const featuredSpeakers = store.speakers.filter(s => s.featured).sort((a, b) => (a.featured_order || 0) - (b.featured_order || 0));
				const currentIndex = featuredSpeakers.findIndex(s => s.id === speakerId);
				const otherIndex = currentIndex + direction;

				if (currentIndex > -1 && otherIndex > -1 && otherIndex < featuredSpeakers.length) {
					// Swap order values
					[featuredSpeakers[currentIndex].featured_order, featuredSpeakers[otherIndex].featured_order] = [featuredSpeakers[otherIndex].featured_order, featuredSpeakers[currentIndex].featured_order];
					await saveStore('speakers', 'fossasia_manage_speakers');
					render();
				}
			}
			if (e.target.matches('.btn-delete-speaker')) {
				const speakerId = e.target.dataset.id;
				const speakerName = e.target.dataset.name;
				if (await customAlert.show('Delete Speaker', `Are you sure you want to delete "${escapeHTML(speakerName)}"? This action cannot be undone.`, null, true)) {
					store.speakers = store.speakers.filter(s => s.id !== speakerId);
					await saveStore('speakers', 'fossasia_manage_speakers');
					render();
				}
			}
		});
		const editModal = getElement('editSpeakerModal');
		const editForm = getElement('editSpeakerForm');
		const closeBtn = editModal.querySelector('.close-btn');
		let currentlyEditingId = null;

		const openEditModal = (speakerId) => {
			const speaker = store.speakers.find(s => s.id === speakerId);
			if (!speaker) return;
			currentlyEditingId = speakerId;
			const session = speaker.sessions && speaker.sessions[0] ? speaker.sessions[0] : {};
			
			editForm.querySelector('[name="name"]').value = speaker.name || '';
			editForm.querySelector('[name="title"]').value = speaker.title || '';
			editForm.querySelector('[name="category"]').value = speaker.category || '';
			
			// Handle image inputs
			const imageUrlInput = editForm.querySelector('[name="image_url"]');
			const imageUploadInput = editForm.querySelector('[name="image_upload"]');
			const urlRadio = editForm.querySelector('[name="image_source"][value="url"]');
			
			if (speaker.image && speaker.image.startsWith('data:image')) {
				// Cannot pre-fill file input, so just reset and default to URL
				imageUrlInput.value = '';
				imageUrlInput.placeholder = 'Image is currently a local upload. Replace by URL or new upload.';
			} else {
				imageUrlInput.value = speaker.image || '';
				imageUrlInput.placeholder = 'https://example.com/photo.jpg';
			}
			imageUploadInput.value = ''; // Reset file input
			urlRadio.checked = true;
			toggleImageInputs(editForm);

			editForm.querySelector('[name="bio"]').value = speaker.bio || '';
			editForm.querySelector('[name="talkTitle"]').value = session.title || '';
			editForm.querySelector('[name="talkTime"]').value = session.time || '';
			editForm.querySelector('[name="talkEndTime"]').value = session.end_time || '';
			editForm.querySelector('[name="linkedin"]').value = speaker.social?.linkedin || '';
			editForm.querySelector('[name="twitter"]').value = speaker.social?.twitter || '';
			editForm.querySelector('[name="github"]').value = speaker.social?.github || '';
			editForm.querySelector('[name="website"]').value = speaker.social?.website || '';
			editModal.style.display = 'flex';
		};

		container.addEventListener('click', (e) => {
			if (e.target.matches('.btn-edit-speaker')) {
				openEditModal(e.target.dataset.id);
			}
		});

		closeBtn.addEventListener('click', () => editModal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === editModal) editModal.style.display = 'none'; });

		editForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = editForm.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';

			const formData = new FormData(editForm);
			const speakerIndex = store.speakers.findIndex(s => s.id === currentlyEditingId);
			if (speakerIndex === -1) return;

			const processSubmit = async (imageUrl) => {
				store.speakers[speakerIndex] = {
					...store.speakers[speakerIndex], // Preserve featured status and other non-form fields
					name: formData.get('name'), title: formData.get('title'), category: formData.get('category'),
					image: imageUrl, bio: formData.get('bio'),
					social: { linkedin: formData.get('linkedin'), twitter: formData.get('twitter'), github: formData.get('github'), website: formData.get('website') },
					sessions: [{ title: formData.get('talkTitle'), date: formData.get('talkDate'), time: formData.get('talkTime'), end_time: formData.get('talkEndTime') }]
				};

				await saveStore('speakers', 'fossasia_manage_speakers');
				editModal.style.display = 'none';
				render();
				submitBtn.disabled = false;
				submitBtn.textContent = 'Save Changes';
			};

			const imageSource = formData.get('image_source');
			if (imageSource === 'upload' && formData.get('image_upload').size > 0) {
				const reader = new FileReader();
				reader.onload = (event) => processSubmit(event.target.result);
				reader.onerror = () => { alert('Error reading file.'); submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; };
				reader.readAsDataURL(formData.get('image_upload'));
			} else {
				const imageUrl = formData.get('image_url');
				if (!imageUrl && store.speakers[speakerIndex].image.startsWith('data:image')) {
					// If URL is empty but original was an upload, keep the original upload
					processSubmit(store.speakers[speakerIndex].image);
				} else {
					processSubmit(imageUrl);
				}
			}
		});

		render();

		// --- New Speaker Modal Logic ---
		const newSpeakerModal = getElement('newSpeakerModal');
		const newSpeakerForm = getElement('newSpeakerForm');
		const closeNewSpeakerBtn = newSpeakerModal.querySelector('.close-btn');
		
		const toggleImageInputs = (form) => {
			const source = form.querySelector('[name="image_source"]:checked').value;
			const urlInput = form.querySelector('[name="image_url"]');
			const uploadInput = form.querySelector('[name="image_upload"]');
			if (source === 'url') {
				urlInput.style.display = 'block';
				urlInput.required = true;
				uploadInput.style.display = 'none';
				uploadInput.required = false;
			} else {
				urlInput.style.display = 'none';
				urlInput.required = false;
				uploadInput.style.display = 'block';
				uploadInput.required = true;
			}
		};

		[newSpeakerForm, editForm].forEach(form => {
			form.querySelectorAll('[name="image_source"]').forEach(radio => {
				radio.addEventListener('change', () => toggleImageInputs(form));
			});
		});

		addNewSpeakerBtn.addEventListener('click', () => {
			newSpeakerForm.reset();
			toggleImageInputs(newSpeakerForm);
			newSpeakerModal.style.display = 'flex';
		});

		closeNewSpeakerBtn.addEventListener('click', () => newSpeakerModal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === newSpeakerModal) newSpeakerModal.style.display = 'none'; });


		newSpeakerForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			const submitBtn = newSpeakerForm.querySelector('button[type="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = 'Adding...';

			const formData = new FormData(newSpeakerForm);
			
			const processNewSpeaker = async (imageUrl) => {
				const newSpeaker = {
					id: `manual-${Date.now()}`, name: formData.get('name'), title: formData.get('title'), category: formData.get('category'),
					image: imageUrl, bio: formData.get('bio'), featured: false,
					social: { linkedin: formData.get('linkedin'), twitter: formData.get('twitter'), github: formData.get('github'), website: formData.get('website') },
					sessions: [{ title: formData.get('talkTitle'), date: formData.get('talkDate'), time: formData.get('talkTime'), end_time: formData.get('talkEndTime') }]
				};
				store.speakers.unshift(newSpeaker);
				await saveStore('speakers', 'fossasia_manage_speakers');
				newSpeakerModal.style.display = 'none';
				render();
				submitBtn.disabled = false;
				submitBtn.textContent = 'Add Speaker';
			};

			const imageSource = formData.get('image_source');
			if (imageSource === 'upload' && formData.get('image_upload').size > 0) {
				const reader = new FileReader();
				reader.onload = (event) => processNewSpeaker(event.target.result);
				reader.onerror = () => { customAlert.alert('Error reading file.'); submitBtn.disabled = false; submitBtn.textContent = 'Add Speaker'; };
				reader.readAsDataURL(formData.get('image_upload'));
			} else {
				processNewSpeaker(formData.get('image_url'));
			}
		});
	})();

	// --- Sponsor Management Logic ---
	(() => {
		const container = getElement('sponsors-list');
		const addGroupBtn = getElement('addSponsorGroupBtn');
		const addSponsorModal = getElement('addSponsorModal');
		const addSponsorForm = getElement('addSponsorForm');
		const closeSponsorModalBtn = addSponsorModal.querySelector('.close-btn');
		let currentlyAddingToGroupIndex = -1;
		if (!container || !addGroupBtn) return;

		const render = () => {
			container.innerHTML = '';
			if (!store.sponsors || store.sponsors.length === 0) {
				container.innerHTML = '<p>No sponsor groups found. Add one to get started.</p>';
				return;
			}

			store.sponsors.forEach((group, groupIndex) => {
				const groupCard = document.createElement('div');
				groupCard.className = 'sponsor-group-card';

				const sponsorsHTML = group.sponsors.map((sponsor, sponsorIndex) => `
					<div class="sponsor-item"> <img src="${sponsor.image}" alt="${escapeHTML(sponsor.name)}"> <div class="sponsor-item-info"> <strong>${escapeHTML(sponsor.name)}</strong><br> <a href="${escapeHTML(sponsor.link)}" target="_blank" rel="noopener">${escapeHTML(sponsor.link)}</a> </div> <div class="speaker-actions"> <button class="btn btn-reject btn-delete-sponsor" data-group-index="${groupIndex}" data-sponsor-index="${sponsorIndex}">Delete</button> </div> </div>
				`).join('');

				groupCard.innerHTML = `
					<div class="sponsor-group-header">
						<h3>${escapeHTML(group.group_name)}</h3>
						<div class="sponsor-group-controls">
							<label style="display:flex; align-items:center; gap: 5px; font-weight: normal; cursor: pointer;">
								<input type="checkbox" class="center-sponsors-toggle" data-group-index="${groupIndex}" ${group.centered ? 'checked' : ''}>
								Center Logos
							</label>
							<div style="display:flex; align-items:center; gap: 8px;">
								<label for="logoSize-${groupIndex}">Logo Size:</label>
								<input type="range" class="logo-size-slider" id="logoSize-${groupIndex}" data-group-index="${groupIndex}" min="50" max="300" step="10" value="${group.logo_size || 160}">
								<span id="logoSizeValue-${groupIndex}">${group.logo_size || 160}px</span>
							</div>
							<div class="speaker-actions">
								<button class="btn btn-accept btn-add-sponsor" data-group-index="${groupIndex}">Add Sponsor</button>
								<button class="btn btn-reject btn-delete-group" data-group-index="${groupIndex}">Delete Group</button>
							</div>
						</div>
					</div>
					<div class="sponsors-in-group">${sponsorsHTML}</div>
				`;
				container.appendChild(groupCard);
			});

			// Add listeners for the new sliders
			document.querySelectorAll('.logo-size-slider').forEach(slider => {
				slider.addEventListener('input', (e) => {
					document.getElementById(`logoSizeValue-${e.target.dataset.groupIndex}`).textContent = `${e.target.value}px`;
				});
			});
		};

		container.addEventListener('click', async (e) => {
			if (e.target.matches('.btn-delete-group')) {
				if (!await customAlert.show('Delete Group', 'Are you sure you want to delete this entire sponsor group?', null, true)) return;
				store.sponsors.splice(e.target.dataset.groupIndex, 1);
				await saveStore('sponsors', 'fossasia_manage_sponsors');
				render();
			}
			if (e.target.matches('.btn-delete-sponsor')) {
				if (!await customAlert.show('Delete Sponsor', 'Are you sure you want to delete this sponsor?', null, true)) return;
				store.sponsors[e.target.dataset.groupIndex].sponsors.splice(e.target.dataset.sponsorIndex, 1);
				await saveStore('sponsors', 'fossasia_manage_sponsors');
				render();
			}
			if (e.target.matches('.btn-add-sponsor')) {
				currentlyAddingToGroupIndex = parseInt(e.target.dataset.groupIndex, 10);
				addSponsorForm.reset();
				addSponsorModal.style.display = 'flex';
			}
		});

		container.addEventListener('change', async (e) => {
			const target = e.target;
			if (target.matches('.center-sponsors-toggle')) {
				const groupIndex = target.dataset.groupIndex;
				store.sponsors[groupIndex].centered = target.checked;
				await saveStore('sponsors', 'fossasia_manage_sponsors');
				// No re-render needed, just confirmation
			}
			if (target.matches('.logo-size-slider')) {
				const groupIndex = target.dataset.groupIndex;
				const newSize = parseInt(target.value, 10);
				store.sponsors[groupIndex].logo_size = newSize;
				// Debounce saving? For now, save on change.
				await saveStore('sponsors', 'fossasia_manage_sponsors');
			}
		});

		addGroupBtn.addEventListener('click', () => {
			const groupName = prompt("Enter new sponsor group name:");
			if (groupName && groupName.trim()) {
				// Add new properties with defaults
				store.sponsors.push({ group_name: groupName.trim(), sponsors: [], centered: false, logo_size: 160 });
				saveStore('sponsors', 'fossasia_manage_sponsors').then(render);
			}
		});

		closeSponsorModalBtn.addEventListener('click', () => addSponsorModal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === addSponsorModal) addSponsorModal.style.display = 'none'; });

		addSponsorForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			if (currentlyAddingToGroupIndex === -1) return;

			const formData = new FormData(addSponsorForm);
			const imageFile = formData.get('sponsorImageFile');
			const imageURL = formData.get('sponsorImageURL');

			const newSponsor = {
				name: formData.get('sponsorName'),
				link: formData.get('sponsorLink'),
				image: ''
			};

			const processSponsor = async (imageUrl) => {
				newSponsor.image = imageUrl;
				if (!newSponsor.image) { customAlert.alert('Please provide an image URL or upload a file.'); return; }
				store.sponsors[currentlyAddingToGroupIndex].sponsors.push(newSponsor);
				await saveStore('sponsors', 'fossasia_manage_sponsors');
				addSponsorModal.style.display = 'none';
				render();
			};

			if (imageURL.trim()) {
				processSponsor(imageURL);
			} else if (imageFile && imageFile.size > 0) {
				const reader = new FileReader();
				reader.onload = (event) => processSponsor(event.target.result);
				reader.onerror = () => customAlert.alert('Error reading file.');
				reader.readAsDataURL(imageFile);
			} else { customAlert.alert('Please provide an image URL or upload a file.'); }
		});

		render();
	})();

	// --- Theme Settings Logic ---
	(() => {
		const form = getElement('themeSettingsForm');
		if (!form) return;

		const render = () => {
			form.querySelector('[name="brand_color"]').value = store.theme.brand_color || '#D51007';
			form.querySelector('[name="background_color"]').value = store.theme.background_color || '#f8f9fa';
			form.querySelector('[name="text_color"]').value = store.theme.text_color || '#0b0b0b';
			form.querySelector('[name="navbar_color"]').value = store.theme.navbar_color || '#ffffff';
		};

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const formData = new FormData(form);
			store.theme.brand_color = formData.get('brand_color');
			store.theme.navbar_color = formData.get('navbar_color');
			store.theme.background_color = formData.get('background_color');
			store.theme.text_color = formData.get('text_color');

			const result = await saveStore('theme', 'fossasia_manage_theme_settings');
			if (result.success) customAlert.alert('Theme settings saved successfully.', 'Success');
		});

		render();
	})();

	// --- Custom Sections Logic ---
	(() => {
		const container = getElement('sections-list');
		const addBtn = getElement('addSectionBtn');
		const addMediaBtn = getElement('addMediaSectionBtn');
		if (!container || !addBtn) return;

		const getSectionPositions = () => {
			const singleEventPositions = [
				{ value: 'after_hero', label: 'Single Event Page - After Hero' },
				{ value: 'after_about', label: 'Single Event Page - After About' },
				{ value: 'after_speakers', label: 'Single Event Page - After Speakers' },
				{ value: 'after_schedule', label: 'Single Event Page - After Schedule' },
				{ value: 'after_sponsors', label: 'Single Event Page - After Sponsors' },
				{ value: 'after_venue', label: 'Single Event Page - After Venue' }
			];
			const globalEventListPositions = [
				{ value: 'events_after_hero', label: 'Events Page - After Hero' },
				{ value: 'events_after_cards', label: 'Events Page - After Cards' },
				{ value: 'events_before_footer', label: 'Events Page - Before Footer' }
			];

			return eventId ? singleEventPositions : [...globalEventListPositions, ...singleEventPositions];
		};
		const render = () => {
			container.innerHTML = '';
			if (!store.sections || store.sections.length === 0) {
				container.innerHTML = '<p>No custom sections found. Add one to get started.</p>';
				return;
			}

			const sorted = [...store.sections].sort((a, b) => (a.order || 10) - (b.order || 10));
			sorted.forEach(section => {
				const sectionType = section.type || 'content';
				const card = document.createElement('div');
				card.className = 'section-card';
				const posLabel = getSectionPositions().find(p => p.value === section.position)?.label || section.position;
				card.innerHTML = `
					<div class="section-info" style="flex-grow: 1;">
						<h3>${escapeHTML(section.title)} <span class="pill" style="font-size: .7rem; vertical-align: middle;">${sectionType === 'media' ? 'Media' : 'Layout'}</span></h3>
						<p><strong>Position:</strong> ${escapeHTML(posLabel)} | <strong>Order:</strong> ${section.order || 10}</p>
					</div>
					<div class="speaker-actions">
						<button class="btn-visibility" data-id="${section.id}" title="${section.is_active ? 'Visible' : 'Hidden'}" style="background: none; border: none; cursor: pointer; font-size: 24px; color: ${section.is_active ? 'var(--brand)' : '#ccc'};">${section.is_active ? '👁️' : '👁️‍🗨️'}</button>
						<button class="btn btn-edit btn-edit-section" data-id="${section.id}">Edit</button>
						<button class="btn btn-reject btn-delete-section" data-id="${section.id}">Delete</button>
					</div>
				`;
				container.appendChild(card);
			});
		};

		container.addEventListener('click', async (e) => {
			const target = e.target.closest('button'); // Ensure we get the button if an icon inside is clicked
			if (!target) return;

			if (target.matches('.btn-delete-section')) {
				if (!await customAlert.show('Delete Section', 'Are you sure you want to delete this section?', null, true)) return;
				store.sections = store.sections.filter(s => s.id !== target.dataset.id);
				await saveStore('sections', 'fossasia_manage_sections').then(render);
			} else if (target.matches('.btn-visibility')) {
				const sectionId = target.dataset.id;
				const section = store.sections.find(s => s.id === sectionId);
				if (section) {
					section.is_active = !section.is_active;
					await saveStore('sections', 'fossasia_manage_sections').then(render);
				}
			} else if (target.matches('.btn-edit-section')) {
				const sectionId = target.dataset.id;
				const section = store.sections.find(s => s.id === sectionId);
				if (!section) return;

				// Check the section type and open the correct modal.
				if (section.type === 'media') {
					openMediaSectionModal(sectionId);
				} else {
					openSectionModal(sectionId);
				}
			}
		});

		const mediaModal = getElement('mediaSectionModal');
		const modal = getElement('sectionModal');
		const closeBtn = modal.querySelector('.close-btn');
		const closeMediaBtn = mediaModal.querySelector('.close-btn');
		let currentlyEditingId = null;

		const layoutStyleSelect = modal.querySelector('[name="layoutStyle"]');
		const mediaColumnFields = getElement('media-column-fields');
		const mediaTypeRadios = mediaColumnFields.querySelectorAll('[name="mediaType"]');
		const photoFields = mediaColumnFields.querySelector('#photo-fields');
		const videoFields = mediaColumnFields.querySelector('#video-fields');
		const carouselFields = mediaColumnFields.querySelector('#carousel-fields');
		const mapFields = mediaColumnFields.querySelector('#map-fields');

		layoutStyleSelect.addEventListener('change', () => { 
			const isFullWidth = layoutStyleSelect.value === 'full_width';
			mediaColumnFields.style.display = isFullWidth ? 'none' : 'block'; 
		});
		mediaTypeRadios.forEach(radio => radio.addEventListener('change', () => {
			const selectedType = radio.value;
			photoFields.style.display = selectedType === 'photo' ? 'block' : 'none';
			mapFields.style.display = selectedType === 'map' ? 'block' : 'none';
			videoFields.style.display = selectedType === 'video' ? 'block' : 'none';
			carouselFields.style.display = selectedType === 'carousel' ? 'block' : 'none';
		}));

		const setupCarouselInputs = (form) => {
			const slidesCountInput = form.querySelector('[name="carouselSlidesCount"]');
			const uploadsContainer = form.querySelector('.carousel-uploads');
			if (!slidesCountInput || !uploadsContainer) return;

			const generateUploads = () => {
				const count = parseInt(slidesCountInput.value, 10) || 0;
				uploadsContainer.innerHTML = Array.from({ length: count }, (_, i) =>
					`<label style="font-weight: normal; margin-top: 5px;">Slide ${i + 1}:</label><input type="file" name="carouselUpload[]" accept="image/*">`
				).join('');
			};
			slidesCountInput.addEventListener('change', generateUploads);
			generateUploads(); // Initial call
		};

		const form = getElement('sectionForm');
		const openSectionModal = (sectionId = null) => {
			form.reset();
			const positionSelect = form.querySelector('[name="sectionPosition"]');
			positionSelect.innerHTML = getSectionPositions().map(p => `<option value="${p.value}">${p.label}</option>`).join('');

			if (sectionId) {
				currentlyEditingId = sectionId;
				const section = store.sections.find(s => s.id === sectionId && s.type !== 'media');
				getElement('sectionModalTitle').textContent = 'Edit Layout Section';
				form.querySelector('[name="sectionId"]').value = section.id;
				form.querySelector('[name="sectionTitle"]').value = section.title || '';
				form.querySelector('[name="sectionSubtitle"]').value = section.subtitle || '';
				form.querySelector('[name="sectionPosition"]').value = section.position || 'after_hero';
				form.querySelector('[name="sectionOrder"]').value = section.order || 10;
				form.querySelector('[name="sectionIsActive"]').checked = section.is_active;
				form.querySelector('[name="layoutStyle"]').value = section.layout || 'full_width';
				form.querySelector('[name="contentTitle"]').value = section.contentTitle || ''; 
				// Use wp.editor.setContent for the rich editor
				if (tinymce.get('sectionContentBody')) { tinymce.get('sectionContentBody').setContent(section.contentBody || ''); }
				form.querySelector('[name="buttonText"]').value = section.buttonText || ''; form.querySelector('[name="buttonLink"]').value = section.buttonLink || '';
				
				if (section.layout !== 'full_width') {
					const mediaType = section.mediaType || 'photo';
					const mediaTypeRadio = form.querySelector(`[name="mediaType"][value="${mediaType}"]`);
					if (mediaTypeRadio) mediaTypeRadio.checked = true;

					form.querySelector('[name="mediaTitle"]').value = section.mediaTitle || '';
					if (section.photo_src && !section.photo_src.startsWith('data:image')) { form.querySelector('[name="photoUrl"]').value = section.photo_src; }
					form.querySelector('[name="mapEmbedUrl"]').value = section.map_embed_src || '';
					form.querySelector('[name="videoEmbedUrl"]').value = section.video_embed_src || '';
					form.querySelector('[name="carouselTimer"]').value = section.carousel_timer || 5;
					form.querySelector('[name="carouselSlidesCount"]').value = section.carousel_images?.length || 3;
				} else {
					// Hide media column for full-width
					mediaColumnFields.style.display = 'none';
				}
			} else {
				currentlyEditingId = null;
				getElement('sectionModalTitle').textContent = 'Add New Layout Section';
				// Clear the editor for a new section
				if (tinymce.get('sectionContentBody')) { tinymce.get('sectionContentBody').setContent(''); }
			}
			layoutStyleSelect.dispatchEvent(new Event('change'));
			const checkedMediaType = form.querySelector('[name="mediaType"]:checked');
			if (checkedMediaType) {
				checkedMediaType.dispatchEvent(new Event('change'));
			}
			setupCarouselInputs(form);
			modal.style.display = 'flex';
		};

		addBtn.addEventListener('click', () => openSectionModal(null));
		closeBtn.addEventListener('click', () => modal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });


		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const formData = new FormData(form);
			const existingSection = currentlyEditingId ? store.sections.find(s => s.id === currentlyEditingId && s.type !== 'media') : null;
			const layout = formData.get('layoutStyle');
			const mediaType = layout !== 'full_width' ? formData.get('mediaType') : '';
			let photoSrc = '';
			let carouselImages = [];
			const submitBtn = form.querySelector('button[type="submit"]');

			if (mediaType === 'photo') {
				const photoUrl = formData.get('photoUrl');
				const photoFile = formData.get('photoUpload');
				if (photoUrl) { photoSrc = photoUrl; }
				else if (photoFile && photoFile.size > 0) { photoSrc = await new Promise(resolve => { const reader = new FileReader(); reader.onload = e => resolve(e.target.result); reader.readAsDataURL(photoFile); }); } 
				else if (existingSection && existingSection.photo_src) { photoSrc = existingSection.photo_src; }
			} else if (mediaType === 'carousel') {
				const carouselFiles = formData.getAll('carouselUpload[]');
				if (carouselFiles.length > 0 && carouselFiles[0].size > 0) {
					for (const file of carouselFiles) {
						const dataUrl = await new Promise(resolve => { const reader = new FileReader(); reader.onload = e => resolve(e.target.result); reader.readAsDataURL(file); });
						carouselImages.push(dataUrl);
					}
				} else if (existingSection) {
					carouselImages = existingSection.carousel_images || [];
				}
			}

			const sectionData = {
				type: 'content', // Identify this as a content section
				id: formData.get('sectionId') || Date.now().toString(),
				title: formData.get('sectionTitle'), subtitle: formData.get('sectionSubtitle'), position: formData.get('sectionPosition'),
				order: parseInt(formData.get('sectionOrder'), 10), is_active: formData.get('sectionIsActive') === 'on',
				layout: layout, contentTitle: formData.get('contentTitle'),
				contentBody: tinymce.get('sectionContentBody') ? tinymce.get('sectionContentBody').getContent() : '',
				buttonText: formData.get('buttonText'), buttonLink: formData.get('buttonLink'),
				mediaTitle: layout !== 'full_width' ? formData.get('mediaTitle') : '',
				mediaType: mediaType,
				photo_src: photoSrc,
				map_embed_src: layout !== 'full_width' ? formData.get('mapEmbedUrl') : '',
				video_embed_src: layout !== 'full_width' ? formData.get('videoEmbedUrl') : '',
				carousel_images: carouselImages,
				carousel_timer: layout !== 'full_width' ? formData.get('carouselTimer') : 5,
			};

			if (currentlyEditingId) {
				store.sections = store.sections.map(s => s.id === currentlyEditingId ? sectionData : s);
			} else {
				store.sections.push(sectionData);
			}
			submitBtn.disabled = true;
			submitBtn.textContent = 'Saving...';
			await saveStore('sections', 'fossasia_manage_sections');
			modal.style.display = 'none';
			render();
		});

		render();

		// --- Media Section Logic ---
		const mediaForm = getElement('mediaSectionForm');
		const openMediaSectionModal = (sectionId = null) => {
			mediaForm.reset();
			const positionSelect = mediaForm.querySelector('[name="sectionPosition"]');
			positionSelect.innerHTML = getSectionPositions().map(p => `<option value="${p.value}">${p.label}</option>`).join('');

			if (sectionId) {
				currentlyEditingId = sectionId;
				const section = store.sections.find(s => s.id === sectionId && s.type === 'media');
				getElement('mediaSectionModalTitle').textContent = 'Edit Media Section';
				mediaForm.querySelector('[name="sectionId"]').value = section.id;
				mediaForm.querySelector('[name="sectionTitle"]').value = section.title || '';
				mediaForm.querySelector('[name="sectionPosition"]').value = section.position || 'after_hero';
				mediaForm.querySelector('[name="sectionOrder"]').value = section.order || 10;
				mediaForm.querySelector('[name="sectionIsActive"]').checked = section.is_active;
				
				const mediaType = section.mediaType || 'photo';
				const mediaTypeRadio = mediaForm.querySelector(`[name="mediaType"][value="${mediaType}"]`);
				if (mediaTypeRadio) mediaTypeRadio.checked = true;

				if (section.photo_src && !section.photo_src.startsWith('data:image')) { mediaForm.querySelector('[name="photoUrl"]').value = section.photo_src; }
				mediaForm.querySelector('[name="videoEmbedUrl"]').value = section.video_embed_src || '';
				mediaForm.querySelector('[name="carouselTimer"]').value = section.carousel_timer || 5;
			} else {
				setupCarouselInputs(mediaForm);
				currentlyEditingId = null;
				getElement('mediaSectionModalTitle').textContent = 'Add New Media Section';
			}

			const checkedMediaType = mediaForm.querySelector('[name="mediaType"]:checked');
			if (checkedMediaType) {
				checkedMediaType.dispatchEvent(new Event('change'));
			}
			setupCarouselInputs(mediaForm);
			mediaModal.style.display = 'flex';
		};

		addMediaBtn.addEventListener('click', () => openMediaSectionModal(null));
		closeMediaBtn.addEventListener('click', () => mediaModal.style.display = 'none');
		window.addEventListener('click', (e) => { if (e.target === mediaModal) mediaModal.style.display = 'none'; });

		mediaForm.querySelectorAll('[name="mediaType"]').forEach(radio => radio.addEventListener('change', () => {
			const selectedType = radio.value;
			mediaForm.querySelector('#ms-photo-fields').style.display = selectedType === 'photo' ? 'block' : 'none';
			mediaForm.querySelector('#ms-video-fields').style.display = selectedType === 'video' ? 'block' : 'none';
			mediaForm.querySelector('#ms-carousel-fields').style.display = selectedType === 'carousel' ? 'block' : 'none';
		}));

		mediaForm.addEventListener('submit', async (e) => {
			e.preventDefault();
			const formData = new FormData(mediaForm);
			const existingSection = currentlyEditingId ? store.sections.find(s => s.id === currentlyEditingId && s.type === 'media') : null;
			const mediaType = formData.get('mediaType');
			let photoSrc = '';
			let carouselImages = [];

			if (mediaType === 'photo') {
				const photoUrl = formData.get('photoUrl');
				const photoFile = formData.get('photoUpload');
				if (photoUrl) { photoSrc = photoUrl; }
				else if (photoFile && photoFile.size > 0) { photoSrc = await new Promise(resolve => { const reader = new FileReader(); reader.onload = e => resolve(e.target.result); reader.readAsDataURL(photoFile); }); } 
				else if (existingSection) { photoSrc = existingSection.photo_src || ''; }
			} else if (mediaType === 'carousel') {
				const carouselFiles = formData.getAll('carouselUpload[]');
				if (carouselFiles.length > 0 && carouselFiles[0].size > 0) {
					for (const file of carouselFiles) {
						const dataUrl = await new Promise(resolve => { const reader = new FileReader(); reader.onload = e => resolve(e.target.result); reader.readAsDataURL(file); });
						carouselImages.push(dataUrl);
					}
				} else if (existingSection) {
					carouselImages = existingSection.carousel_images || [];
				}
			}

			const sectionData = {
				type: 'media',
				id: formData.get('sectionId') || Date.now().toString(),
				title: formData.get('sectionTitle'), position: formData.get('sectionPosition'),
				order: parseInt(formData.get('sectionOrder'), 10), is_active: formData.get('sectionIsActive') === 'on',
				mediaType: mediaType,
				photo_src: photoSrc,
				video_embed_src: formData.get('videoEmbedUrl'),
				carousel_images: carouselImages,
				carousel_timer: formData.get('carouselTimer'),
			};

			if (currentlyEditingId) {
				store.sections = store.sections.map(s => s.id === currentlyEditingId ? sectionData : s);
			} else {
				store.sections.push(sectionData);
			}
			await saveStore('sections', 'fossasia_manage_sections');
			mediaModal.style.display = 'none';
			render();
		});
	})();

	// --- Navigation Management Logic ---
	(() => {
		const container = getElement('nav-items-list');
		const form = getElement('addNavItemForm'); // The form for adding/editing items
		const navHrefSelect = getElement('navHref');
		const navText = getElement('navText');
		const navItemIdInput = getElement('navItemId');
		const navParentIdInput = getElement('navParentId');
		const formTitle = getElement('navFormTitle');
		const submitBtn = form.querySelector('button[type="submit"]');
		const navItemTypeToggle = getElement('navItemTypeToggle');
		let currentlyEditingNavIndex = -1;
		if (!container || !form) return;

		const populateNavTargetSelect = () => {
			const defaultTargets = [
				{ value: '#about', label: 'About Section' }, { value: '#speakers', label: 'Speakers Section' },
				{ value: '#schedule-overview', label: 'Schedule Overview Section' }, { value: '#sponsors', label: 'Sponsors Section' },
				{ value: '#venue', label: 'Venue Section' }
			];
			const customTargets = (store.sections || [])
				.filter(s => s.is_active)
				.map(s => ({ value: `#custom-section-${s.id}`, label: `Custom: ${s.title}` }));

			navHrefSelect.innerHTML = [...defaultTargets, ...customTargets].map(t => `<option value="${t.value}">${t.label}</option>`).join('');
		};

		const resetAndHideForm = () => {
			form.reset();
			form.style.display = 'none';
			navItemIdInput.value = '';
			navParentIdInput.value = '';
			getElement('cancelNavEditBtn').style.display = 'none';
		};

		const openForm = ({ parentId = null, itemId = null } = {}) => {
			form.reset();
			form.style.display = 'flex';
			navParentIdInput.value = parentId || '';
			navItemIdInput.value = itemId || '';

			const navHref = getElement('navHref');

			if (itemId) { // Editing existing item
				const item = findNavItem(itemId);
				if (!item) { resetAndHideForm(); return; }
				formTitle.textContent = 'Edit Item';
				navText.value = item.text;
				form.querySelector(`[name="navItemType"][value="${item.type || 'link'}"]`).checked = true;
				navHref.value = item.href || '';
				submitBtn.textContent = 'Save Changes';
				getElement('cancelNavEditBtn').style.display = 'inline-flex';
			} else { // Adding new item
				formTitle.textContent = parentId ? 'Add Sub-Item' : 'Add New Top-Level Item';
				submitBtn.textContent = 'Add Item';
				getElement('cancelNavEditBtn').style.display = 'inline-flex';
			}

			// A sub-item cannot be a dropdown itself
			navItemTypeToggle.style.display = parentId ? 'none' : 'flex';
			if (parentId) form.querySelector('[name="navItemType"][value="link"]').checked = true;

			// Toggle href select based on type
			const type = form.querySelector('[name="navItemType"]:checked').value;
			navHref.style.display = type === 'link' ? 'block' : 'none';
			navHref.required = type === 'link' && !parentId;
		};

		const findNavItem = (itemId, navArray = store.navigation) => {
			for (const item of navArray) {
				if (item.id === itemId) return item;
				if (item.items) {
					const found = findNavItem(itemId, item.items);
					if (found) return found;
				}
			}
			return null;
		};

		const render = () => {
			container.innerHTML = '';
			populateNavTargetSelect();
			
			const addButton = document.createElement('button');
			addButton.textContent = 'Add New Top-Level Item';
			addButton.className = 'btn btn-accept';
			addButton.style.marginBottom = '20px';
			addButton.addEventListener('click', () => openForm());
			container.appendChild(addButton);

			if (!store.navigation || store.navigation.length === 0) {
				const p = document.createElement('p');
				p.textContent = 'No navigation items found. Click the button above to add one.';
				container.appendChild(p);
				return;
			}

			const findNavItem = (itemId, navArray = store.navigation) => {
				for (const item of navArray) {
					if (item.id === itemId) return item;
					if (item.items) {
						const found = findNavItem(itemId, item.items);
						if (found) return found;
					}
				}
				return null;
			};

			const renderItems = (items, parentElement, isSub = false) => {
				items.forEach(item => {
					if (!item.id) item.id = `nav-${Date.now()}-${Math.random()}`; // Ensure ID exists

					const div = document.createElement('div');
					div.className = 'nav-item';

					let itemHTML = `
						<div class="nav-item-info">
							<strong>${escapeHTML(item.text)}</strong>
							${item.type === 'link' ? `&rarr; <code>${escapeHTML(item.href)}</code>` : `<em>(Dropdown)</em>`}
						</div>
						<div class="speaker-actions">
							${item.type === 'dropdown' ? `<button class="btn btn-accept btn-add-sub-item" data-id="${item.id}">Add Sub-Item</button>` : ''}
							<button class="btn btn-edit btn-edit-nav" data-id="${item.id}">Edit</button>
							<button class="btn btn-reject btn-delete-nav" data-id="${item.id}">Delete</button>
						</div>
					`;
					div.innerHTML = itemHTML;

					if (item.items && item.items.length > 0) {
						const subItemsContainer = document.createElement('div');
						subItemsContainer.className = 'nav-item-sub-items';
						renderItems(item.items, subItemsContainer, true);
						div.appendChild(subItemsContainer);
					}
					parentElement.appendChild(div);
				});
			};

			renderItems(store.navigation, container);
		};

		const deleteNavItem = (itemId, navArray = store.navigation) => {
			for (let i = 0; i < navArray.length; i++) {
				if (navArray[i].id === itemId) {
					navArray.splice(i, 1);
					return true;
				}
				if (navArray[i].items) {
					if (deleteNavItem(itemId, navArray[i].items)) return true;
				}
			}
			return false;
		};

		container.addEventListener('click', async (e) => {
			const btn = e.target.closest('button');
			if (!btn) return;

			const id = btn.dataset.id;
			if (btn.matches('.btn-edit-nav')) {
				openForm({ itemId: id });
			} else if (btn.matches('.btn-delete-nav')) {
				if (!await customAlert.show('Delete Navigation Item', 'Are you sure you want to remove this navigation item and all its sub-items?', null, true)) return;
				deleteNavItem(id);
				await saveStore('navigation', 'fossasia_manage_navigation');
				render();
			} else if (btn.matches('.btn-add-sub-item')) {
				openForm({ parentId: id });
			}
		});

		getElement('cancelNavEditBtn').addEventListener('click', resetAndHideForm);

		form.querySelectorAll('[name="navItemType"]').forEach(radio => {
			radio.addEventListener('change', (e) => {
				const isLink = e.target.value === 'link';
				navHrefSelect.style.display = isLink ? 'block' : 'none';
				navHrefSelect.required = isLink;
			});
		});

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const formData = new FormData(form);
			const text = formData.get('navText');
			const type = formData.get('navItemType');
			const href = formData.get('navHref');
			const parentId = formData.get('navParentId');
			const itemId = formData.get('navItemId');

			const findNavItem = (itemId, navArray = store.navigation) => {
				for (const item of navArray) {
					if (item.id === itemId) return item;
					if (item.items) {
						const found = findNavItem(itemId, item.items);
						if (found) return found;
					}
				}
				return null;
			};

			if (!text) return;

			const newItemData = {
				id: itemId || `nav-${Date.now()}`,
				text: text,
				type: parentId ? 'link' : type, // Sub-items are always links
				href: (type === 'link') ? href : '',
			};
			if (type === 'dropdown' && !itemId) {
				newItemData.items = [];
			}

			if (itemId) { // Editing
				const itemToUpdate = findNavItem(itemId);
				if (itemToUpdate) {
					itemToUpdate.text = newItemData.text;
					itemToUpdate.type = newItemData.type;
					itemToUpdate.href = newItemData.href;
					if (newItemData.type === 'dropdown' && !itemToUpdate.items) {
						itemToUpdate.items = [];
					} else if (newItemData.type === 'link') {
						delete itemToUpdate.items;
					}
				}
			} else if (parentId) { // Adding sub-item
				const parentItem = findNavItem(parentId);
				if (parentItem && parentItem.items) {
					parentItem.items.push(newItemData);
				} else {
					console.error("Could not find parent to add sub-item to.");
				}
			} else { // Adding top-level item
				store.navigation.push(newItemData);
			}

			await saveStore('navigation', 'fossasia_manage_navigation');
			resetAndHideForm();
			render();
		});

		render();
	})();

	// --- Sample Event Creation (moved from events-listing-page) ---
	(() => {
		const addSampleBtn = getElement('addSampleEventBtn');
		if (!addSampleBtn) return;

		addSampleBtn.addEventListener('click', () => {
			if (!confirm('This will create a new event page pre-filled with sample data. Are you sure?')) {
				return;
			}

			addSampleBtn.disabled = true;
			addSampleBtn.textContent = 'Creating...';

			const formData = new FormData();
			formData.append('action', 'fossasia_add_sample_event');
			formData.append('nonce', adminNonce);

			fetch(ajaxUrl, { method: 'POST', body: formData })
				.then(res => res.json())
				.then(data => {
					if (data.success) {
						alert(data.data.message + ' The page will now reload.');
						window.location.href = '<?php echo esc_url( home_url( '/events/' ) ); ?>'; // Redirect to events page
					} else {
						alert('Error: ' + data.data);
						addSampleBtn.disabled = false;
						addSampleBtn.textContent = 'Add Sample Event';
					}
				});
		});
	})();

	} // End of if(eventId > 0) for event-specific JS
});

</script>

</body>
</html>