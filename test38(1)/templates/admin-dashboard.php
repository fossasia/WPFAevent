<?php
/**
 * Template Name: FOSSASIA Admin Dashboard (Plugin)
 * Description: A template for the admin dashboard to manage speaker submissions.
 */
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have sufficient permissions to access this page.', 'Access Denied', [ 'response' => 403 ] );
}

$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$event_title = $event_id ? get_the_title($event_id) : '';

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';

// Define file paths based on event ID
$sponsors_file = $event_id ? $data_dir . '/sponsors-' . $event_id . '.json' : '';
$settings_file = $event_id ? $data_dir . '/site-settings-' . $event_id . '.json' : '';
$speakers_file = $event_id ? $data_dir . '/speakers-' . $event_id . '.json' : '';
$schedule_file = $event_id ? $data_dir . '/schedule-' . $event_id . '.json' : '';
$theme_settings_file = $event_id ? $data_dir . '/theme-settings-' . $event_id . '.json' : '';

// Global files that are not event-specific
$global_settings_file = $data_dir . '/site-settings.json';
$sections_file = $data_dir . '/custom-sections.json'; // Assuming custom sections are global for now
$navigation_file = $data_dir . '/navigation.json'; // Assuming navigation is global
$coc_content_file = $data_dir . '/coc-content.json';

// Ensure files exist
if ($event_id) {
    if (!file_exists($sponsors_file)) { file_put_contents($sponsors_file, '[]'); }
    if (!file_exists($settings_file)) { file_put_contents($settings_file, '{"about_section_content": ""}'); }
    if (!file_exists($speakers_file)) { file_put_contents($speakers_file, '[]'); }
    if (!file_exists($schedule_file)) { file_put_contents($schedule_file, '{}'); }
    if (!file_exists($theme_settings_file)) { file_put_contents($theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
}
if (!file_exists($sections_file)) { file_put_contents($sections_file, '[]'); }
if (!file_exists($navigation_file)) { file_put_contents($navigation_file, '[]'); }
if (!file_exists($global_settings_file)) { file_put_contents($global_settings_file, '{"hero_image_url": "", "footer_text": ""}'); }
if (!file_exists($coc_content_file)) { file_put_contents($coc_content_file, '{"content": "<p>Placeholder CoC content.</p>"}'); }

// Speaker data is needed for the new "Manage Speakers" tab
$speakers_data = $event_id && file_exists($speakers_file) ? json_decode(file_get_contents($speakers_file), true) : [];
$sponsors_data = $event_id && file_exists($sponsors_file) ? json_decode(file_get_contents($sponsors_file), true) : [];
$site_settings_data = $event_id && file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$custom_sections_data = json_decode(file_get_contents($sections_file), true);
$schedule_data = $event_id && file_exists($schedule_file) ? json_decode(file_get_contents($schedule_file), true) : [];
$navigation_data = json_decode(file_get_contents($navigation_file), true);
$theme_settings_data = $event_id && file_exists($theme_settings_file) ? json_decode(file_get_contents($theme_settings_file), true) : [];
$coc_content_data = json_decode(file_get_contents($coc_content_file), true);

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
        .container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 24px; }
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
            <?php if (!$event_id): ?>
                <h2 style="margin-top: 5px; color: var(--muted);">Editing: Global Site Content (Events Page)</h2>
            <?php elseif ($event_id && $event_title): ?>
                <h2 style="margin-top: 5px; color: var(--muted);">Editing: <?php echo esc_html($event_title); ?></h2>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <a href="<?php echo esc_url(home_url('/events/')); ?>" class="btn btn-secondary">← Back to Events</a>
            <?php if ($event_id): ?>
                <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="btn btn-secondary">View Event Page</a>
            <?php endif; ?>
            <a href="<?php echo wp_logout_url( home_url( '/fossasia-summit/' ) ); ?>" class="btn btn-reject">Logout</a>
        </div>
    </div>
    
    <?php if (!$event_id): ?>
    <section class="dashboard-section">
        <h2>No Event Selected</h2>
        <p>Please go to the <a href="<?php echo esc_url(home_url('/events/')); ?>">Events page</a> and click "Edit Content" on an event card to manage its specific content.</p>
        <p>You can still manage global site settings below.</p>
    </section>
    <?php endif; ?>

    <section class="dashboard-section" id="main-dashboard-content">
        <div class="dashboard-tabs">
            <?php if ($event_id): ?>
                <div class="dashboard-tab active" data-panel="sync">Data Sync</div>
                <div class="dashboard-tab" data-panel="speakers">Manage Speakers</div>
                <div class="dashboard-tab" data-panel="schedule">Manage Schedule</div>
                <div class="dashboard-tab" data-panel="about">About Section</div>
                <div class="dashboard-tab" data-panel="sponsors">Manage Sponsors</div>
                <div class="dashboard-tab" data-panel="settings">Site Settings</div>
                <div class="dashboard-tab" data-panel="theme">Theme</div>
            <?php endif; ?>
            <!-- Global tabs that are always visible -->
            <div class="dashboard-tab <?php echo !$event_id ? 'active' : ''; ?>" data-panel="sections">Content Sections</div>
            <div class="dashboard-tab" data-panel="media-sections">Media Sections</div>
            <div class="dashboard-tab" data-panel="navigation">Manage Navigation</div>
            <!-- CoC is only on the global dashboard -->
            <?php if (!$event_id): ?><div class="dashboard-tab" data-panel="coc">Code of Conduct</div><?php endif; ?>
        </div>

        <?php if ($event_id): ?>
        <!-- Event-Specific Panels -->
        <div id="panel-sync" class="dashboard-panel active">
            <h2>Sync with Eventyay</h2>
            <p>Click the button below to fetch the latest speaker and session data directly from the Eventyay API. This will overwrite the current speaker data on your site.</p>
            <button id="syncEventyayBtn" class="btn btn-accept">Sync Speakers from Eventyay</button>
            <hr style="margin: 20px 0;">
            <h2>Import Sample Data</h2>
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
                <?php wp_editor( $site_settings_data['about_section_content'] ?? '', 'about_section_content', [
                    'textarea_name' => 'about_section_content',
                    'media_buttons' => false,
                    'textarea_rows' => 20, // Fallback for when JS is disabled
                    'tinymce'       => [
                        'height' => 450, // Set the editor height in pixels
                    ],
                ] ); ?>
                <button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save About Section</button>
            </form>
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
                <button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Theme</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Global Panels -->
        <div id="panel-sections" class="dashboard-panel <?php echo !$event_id ? 'active' : ''; ?>">
            <div id="sections-list">
                <!-- Custom sections will be rendered here -->
            </div>
            <div class="header-actions" style="margin-top: 20px;">
                <button id="addSectionBtn" class="btn btn-accept">Add New Section</button>
            </div>
        </div>

        <div id="panel-media-sections" class="dashboard-panel">
            <h2>Full-Width Media Sections</h2>
            <p>Add full-width media elements like a large photo, a video embed, or an image carousel between your content sections.</p>
            <div id="media-sections-list">
                <!-- Media sections will be rendered here -->
            </div>
            <div class="header-actions" style="margin-top: 20px;">
                <button id="addMediaSectionBtn" class="btn btn-accept">Add New Media Section</button>
            </div>
        </div>

        <div id="panel-navigation" class="dashboard-panel">
            <div id="nav-items-list">
                <!-- Navigation items will be rendered here -->
            </div>
            <form id="addNavItemForm" style="display: none;">
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

        <?php if (!$event_id): ?>
        <div id="panel-coc" class="dashboard-panel">
            <h2>Edit Code of Conduct</h2>
            <p>Use the editor below to change the content of the Code of Conduct page. This content is global and applies to all events.</p>
            <form id="cocForm" style="width: 100%;">
                <?php wp_editor( $coc_content_data['content'] ?? '', 'coc_content_editor', [
                    'textarea_name' => 'coc_content',
                    'media_buttons' => false,
                    'textarea_rows' => 20,
                    'tinymce'       => [
                        'height' => 450,
                    ],
                ] ); ?>
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
                    <label>Content Column Title (optional H3 heading):</label>
                    <input type="text" name="contentTitle">
                    <label for="sectionContentBody">Content Body:</label>
                    <textarea id="sectionContentBody" name="sectionContentBody" style="height: 250px;"></textarea>
                    <label>Button Text (optional):</label>
                    <input type="text" name="buttonText" placeholder="e.g., Learn More">
                    <label>Button Link (optional):</label>
                    <input type="url" name="buttonLink" placeholder="https://example.com">
                </div>
            </fieldset>

            <fieldset id="media-column-fields" style="display: none;">
                <legend>Media Column</legend>
                <label>Media Type: 
                    <label><input type="radio" name="mediaType" value="photo" checked> Photo</label> 
                    <label><input type="radio" name="mediaType" value="map"> Map</label>
                </label>
                <div id="photo-fields"><label>Photo (URL or Upload):</label><input type="text" name="photoUrl" placeholder="Enter image URL"><input type="file" name="photoUpload" accept="image/*"></div>
                <div id="map-fields" style="display: none;">
                    <label>Map Embed URL (the 'src' from an iframe):</label><input type="url" name="mapEmbedUrl" placeholder="https://www.google.com/maps/embed?pb=...">
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
                    <label>Carousel Images (Upload Multiple):</label><input type="file" name="carouselUpload[]" accept="image/*" multiple>
                    <label>Slide Duration (seconds):</label><input type="number" name="carouselTimer" value="5" min="1">
                </div>
            </fieldset>

            <button type="submit" class="btn btn-accept" style="margin-top: 20px;">Save Media Section</button>
        </form>
    </div>
</div>
<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminNonce = '<?php echo wp_create_nonce("fossasia_admin_nonce"); ?>';
    const ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
    const eventId = <?php echo $event_id; ?>;
    
    // --- Data Store ---
    const store = {
        speakers: <?php echo json_encode($speakers_data); ?>,
        sponsors: <?php echo json_encode($sponsors_data); ?>,
        settings: <?php echo json_encode($site_settings_data); ?>,
        media_sections: <?php echo json_encode(array_values(array_filter($custom_sections_data, fn($s) => $s['type'] === 'media'))); ?>,
        sections: <?php echo json_encode($custom_sections_data); ?>,
        navigation: <?php echo json_encode($navigation_data); ?>,
        schedule: <?php echo json_encode($schedule_data); ?>,
        theme: <?php echo json_encode($theme_settings_data); ?>
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
                alert('About section content saved successfully.');
            }
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save About Section';
        });

        // A more direct save function for specific cases like this
        const directSave = async (key, dataObject, actionName) => {
            const formData = new FormData();
            formData.append('action', actionName);
            formData.append('nonce', adminNonce);
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
                alert(`An unexpected error occurred while saving ${key}.`);
                return { success: false };
            }
        };
    })();
    // --- Schedule Table Management, Data Sync, Speaker Management, etc. would go here inside the if(eventId > 0) block ---
    }

    <?php if (!$event_id): ?>
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
                alert(data.data.message);
            } else {
                alert('Error: ' + data.data);
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
                    if (!confirm('Are you sure you want to delete the schedule table?')) return;
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
                alert('Schedule table saved successfully.');
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
            if (!confirm('Are you sure you want to import sample data? This will overwrite all existing speakers, sponsors, and settings for this event.')) {
                return;
            }

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
                alert(data.data.message + ' The page will now reload to reflect the changes.');
                window.location.reload();
            } else {
                alert('Error: ' + data.data);
                importBtn.disabled = false;
            }
        });
    })();

    // --- Data Sync Logic ---
    (() => {
        const syncBtn = getElement('syncEventyayBtn');
        const syncStatus = getElement('syncStatus');
        if (!syncBtn) return;

        syncBtn.addEventListener('click', async () => {
            syncStatus.textContent = 'Syncing... Please wait.';
            syncStatus.style.color = 'orange';
            syncBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'fossasia_sync_eventyay');
            formData.append('nonce', adminNonce);
            formData.append('event_id', eventId);

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
                }
            } catch (err) {
                syncStatus.textContent = 'An unexpected error occurred. Check the console.';
                syncStatus.style.color = 'red';
                syncBtn.disabled = false;
                console.error(err);
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
                if (confirm(`Are you sure you want to delete "${speakerName}"? This action cannot be undone.`)) {
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
                reader.onerror = () => { alert('Error reading file.'); submitBtn.disabled = false; submitBtn.textContent = 'Add Speaker'; };
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
                if (!confirm('Are you sure you want to delete this entire sponsor group?')) return;
                store.sponsors.splice(e.target.dataset.groupIndex, 1);
                await saveStore('sponsors', 'fossasia_manage_sponsors');
                render();
            }
            if (e.target.matches('.btn-delete-sponsor')) {
                if (!confirm('Are you sure you want to delete this sponsor?')) return;
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
                if (!newSponsor.image) { alert('Please provide an image URL or upload a file.'); return; }
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
                reader.onerror = () => alert('Error reading file.');
                reader.readAsDataURL(imageFile);
            } else { alert('Please provide an image URL or upload a file.'); }
        });

        render();
    })();

    // --- Site Settings Logic ---
    (() => {
        const form = getElement('siteSettingsForm');
        const heroImagePreview = getElement('currentHeroImage');
        if (!form || !heroImagePreview) return;

        const globalSettings = <?php echo json_encode(file_exists($global_settings_file) ? json_decode(file_get_contents($global_settings_file), true) : []); ?>;

        const render = () => {
            // Hero image is event-specific, footer text is global
            heroImagePreview.src = store.settings.hero_image_url || '<?php echo esc_url(get_the_post_thumbnail_url($event_id, "large")); ?>' || '';
            form.querySelector('[name="footerText"]').value = globalSettings.footer_text || '';
        };

        const handleGlobalSettingsSubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const imageFile = formData.get('heroImageFile');
            const imageURL = formData.get('heroImageURL');
            const footerText = formData.get('footerText');
            const cfsText = formData.get('cfsButtonText');
            const cfsLink = formData.get('cfsButtonLink');

            const processSettings = async (newImageUrl) => {
                // Save event-specific settings
                const eventSettings = {
                    hero_image_url: newImageUrl || store.settings.hero_image_url,
                    cfs_button_text: cfsText,
                    cfs_button_link: cfsLink
                };
                store.settings = { ...store.settings, ...eventSettings };
                await saveStore('settings', 'fossasia_manage_site_settings');

                // Save global footer text
                const globalFormData = new FormData();
                globalFormData.append('action', 'fossasia_manage_site_settings');
                globalFormData.append('nonce', adminNonce);
                globalFormData.append('settings', JSON.stringify({ footer_text: footerText }));
                // Note: No event_id is sent for global settings

                const globalResponse = await fetch(ajaxUrl, { method: 'POST', body: globalFormData });
                const globalData = await globalResponse.json();

                if (globalData.success) {
                    alert('Settings saved successfully.');
                    // Manually update the global settings object for the UI
                    globalSettings.footer_text = footerText;
                    render(); // Re-render to show changes
                } else {
                    alert('Error saving global settings: ' + globalData.data);
                }
            };

            if (imageURL) {
                processSettings(imageURL);
            } else if (imageFile && imageFile.size > 0) {
                const reader = new FileReader();
                reader.onload = (event) => processSettings(event.target.result); // This gives a base64 Data URL
                reader.onerror = () => alert('Error reading file.');
                reader.readAsDataURL(imageFile);
            } else {
                // Only footer text changed, hero image URL remains the same
                processSettings(null);
            }
        };

        form.addEventListener('submit', handleGlobalSettingsSubmit);

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
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            store.theme.brand_color = formData.get('brand_color');
            store.theme.background_color = formData.get('background_color');
            store.theme.text_color = formData.get('text_color');

            const result = await saveStore('theme', 'fossasia_manage_theme_settings');
            if (result.success) alert('Theme settings saved successfully.');
        });

        render();
    })();

    // --- Custom Sections Logic ---
    (() => {
        const container = getElement('sections-list');
        const addBtn = getElement('addSectionBtn');
        if (!container || !addBtn) return;

        const sectionPositions = [
            { value: 'events_after_hero', label: 'Events Page - After Hero' },
            { value: 'events_before_footer', label: 'Events Page - Before Footer' },
            { value: 'after_hero', label: 'Single Event Page - After Hero' }, { value: 'after_about', label: 'Single Event Page - After About' },
            { value: 'after_speakers', label: 'Single Event Page - After Speakers' }, { value: 'after_schedule', label: 'Single Event Page - After Schedule' },
            { value: 'after_sponsors', label: 'Single Event Page - After Sponsors' }, { value: 'after_venue', label: 'Single Event Page - After Venue' }
        ];

        const render = () => {
            container.innerHTML = '';
            if (!store.sections || store.sections.length === 0) {
                container.innerHTML = '<p>No custom sections found. Add one to get started.</p>';
                return;
            }

            const sorted = [...store.sections].sort((a, b) => (a.order || 10) - (b.order || 10));
            sorted.forEach(section => {
                const card = document.createElement('div');
                card.className = 'section-card';
                const posLabel = sectionPositions.find(p => p.value === section.position)?.label || section.position;
                card.innerHTML = `
                    <div class="section-info">
                        <h3>${escapeHTML(section.title)} ${section.is_active ? '' : '(Inactive)'}</h3>
                        <p><strong>Position:</strong> ${escapeHTML(posLabel)} | <strong>Order:</strong> ${section.order || 10}</p>
                    </div>
                    <div class="speaker-actions">
                        <button class="btn btn-edit btn-edit-section" data-id="${section.id}">Edit</button>
                        <button class="btn btn-reject btn-delete-section" data-id="${section.id}">Delete</button>
                    </div>
                `;
                container.appendChild(card);
            });
        };

        container.addEventListener('click', async (e) => {
            if (e.target.matches('.btn-delete-section')) {
                if (!confirm('Are you sure you want to delete this section?')) return;
                store.sections = store.sections.filter(s => s.id !== e.target.dataset.id);
                await saveStore('sections', 'fossasia_manage_sections');
                render();
            }
            if (e.target.matches('.btn-edit-section')) {
                openSectionModal(e.target.dataset.id);
            }
        });

        const modal = getElement('sectionModal');
        const closeBtn = modal.querySelector('.close-btn');
        let currentlyEditingId = null;

        const layoutStyleSelect = form.querySelector('[name="layoutStyle"]');
        const mediaColumnFields = getElement('media-column-fields');
        const mediaTypeRadios = mediaColumnFields.querySelectorAll('[name="mediaType"]');
        const photoFields = mediaColumnFields.querySelector('#photo-fields');
        const mapFields = mediaColumnFields.querySelector('#map-fields');

        layoutStyleSelect.addEventListener('change', () => { mediaColumnFields.style.display = layoutStyleSelect.value === 'full_width' ? 'none' : 'block'; });
        mediaTypeRadios.forEach(radio => radio.addEventListener('change', () => {
            const selectedType = radio.value;
            photoFields.style.display = selectedType === 'photo' ? 'block' : 'none';
            mapFields.style.display = selectedType === 'map' ? 'block' : 'none';
        }));

        const openSectionModal = (sectionId = null) => {
            form.reset();
            const positionSelect = form.querySelector('[name="sectionPosition"]');
            positionSelect.innerHTML = sectionPositions.map(p => `<option value="${p.value}">${p.label}</option>`).join('');

            if (sectionId) {
                currentlyEditingId = sectionId;
                const section = store.sections.find(s => s.id === sectionId && s.type !== 'media');
                getElement('sectionModalTitle').textContent = 'Edit Section';
                form.querySelector('[name="sectionId"]').value = section.id;
                form.querySelector('[name="sectionTitle"]').value = section.title || '';
                form.querySelector('[name="sectionSubtitle"]').value = section.subtitle || '';
                form.querySelector('[name="sectionPosition"]').value = section.position || 'after_hero';
                form.querySelector('[name="sectionOrder"]').value = section.order || 10;
                form.querySelector('[name="sectionIsActive"]').checked = section.is_active;
                form.querySelector('[name="layoutStyle"]').value = section.layout || 'full_width';
                form.querySelector('[name="contentTitle"]').value = section.contentTitle || '';
                if (tinymce.get('sectionContentBody')) { tinymce.get('sectionContentBody').setContent(section.contentBody || ''); }
                form.querySelector('[name="buttonText"]').value = section.buttonText || ''; form.querySelector('[name="buttonLink"]').value = section.buttonLink || '';
                
                if (section.layout !== 'full_width') {
                    const mediaType = section.mediaType || 'photo';
                    form.querySelector(`[name="mediaType"][value="${mediaType}"]`).checked = true;
                    if (section.photo_src && !section.photo_src.startsWith('data:image')) { form.querySelector('[name="photoUrl"]').value = section.photo_src; }
                    form.querySelector('[name="mapEmbedUrl"]').value = section.map_embed_src || '';
                }
            } else {
                currentlyEditingId = null;
                getElement('sectionModalTitle').textContent = 'Add New Section';
                if (tinymce.get('sectionContentBody')) { tinymce.get('sectionContentBody').setContent(''); }
            }
            layoutStyleSelect.dispatchEvent(new Event('change'));
            form.querySelector('[name="mediaType"]:checked').dispatchEvent(new Event('change'));
            modal.style.display = 'flex';
        };

        addBtn.addEventListener('click', () => openSectionModal());
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const existingSection = currentlyEditingId ? store.sections.find(s => s.id === currentlyEditingId && s.type !== 'media') : null;
            const layout = formData.get('layoutStyle');
            let photoSrc = '';

            if (layout !== 'full_width' && formData.get('mediaType') === 'photo') {
                const photoUrl = formData.get('photoUrl');
                const photoFile = formData.get('photoUpload');
                if (photoUrl) { photoSrc = photoUrl; } 
                else if (photoFile && photoFile.size > 0) { photoSrc = await new Promise(resolve => { const reader = new FileReader(); reader.onload = e => resolve(e.target.result); reader.readAsDataURL(photoFile); }); } 
                else if (existingSection) { photoSrc = existingSection.photo_src || ''; }
            }

            const sectionData = {
                type: 'content', // Identify this as a content section
                id: formData.get('sectionId') || Date.now().toString(),
                title: formData.get('sectionTitle'), subtitle: formData.get('sectionSubtitle'), position: formData.get('sectionPosition'),
                order: parseInt(formData.get('sectionOrder'), 10), is_active: formData.get('sectionIsActive') === 'on',
                layout: layout, contentTitle: formData.get('contentTitle'),
                contentBody: tinymce.get('sectionContentBody') ? tinymce.get('sectionContentBody').getContent() : '',
                buttonText: formData.get('buttonText'), buttonLink: formData.get('buttonLink'),
                mediaType: layout !== 'full_width' ? formData.get('mediaType') : '',
                photo_src: photoSrc,
                map_embed_src: layout !== 'full_width' ? formData.get('mapEmbedUrl') : '',
            };

            if (currentlyEditingId) {
                store.sections = store.sections.map(s => s.id === currentlyEditingId ? sectionData : s);
            } else {
                store.sections.push(sectionData);
            }
            await saveStore('sections', 'fossasia_manage_sections');
            modal.style.display = 'none';
            render();
        });

        render();
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

            const navItemTypeToggle = getElement('navItemTypeToggle');
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
                if (!confirm('Are you sure you want to remove this navigation item and all its sub-items?')) return;
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

    // Initialize TinyMCE for the section content body
    if (typeof wp.editor !== 'undefined' && typeof tinymce !== 'undefined') {
        wp.editor.initialize('sectionContentBody', {
            tinymce: {
                toolbar1: 'bold,italic,strikethrough,bullist,numlist,link,unlink,undo,redo',
                wpautop: true, // Use wpautop for paragraph handling
                height: 250,
            },
            quicktags: true
        });
    }
    } // End of if(eventId > 0) for event-specific JS
});

</script>

</body>
</html>