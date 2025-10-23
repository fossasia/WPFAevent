
<?php
/**
 *
 * Template Name: All Speakers Page
 * Description: A full-bleed template for the speakers page with its own styling and structure.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$navigation_file = $data_dir . '/navigation.json';

// Get event_id from URL to load correct data
$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
$speakers_file = $event_id ? $data_dir . '/speakers-' . $event_id . '.json' : '';
$sections_file = $data_dir . '/custom-sections.json'; // For custom sections
$global_theme_settings_file = $data_dir . '/theme-settings.json';
$event_theme_settings_file = $event_id ? $data_dir . '/theme-settings-' . $event_id . '.json' : '';

if (!file_exists($speakers_file)) { file_put_contents($speakers_file, '[]'); }
if (!file_exists($navigation_file)) { file_put_contents($navigation_file, '[]'); }
if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
if ($event_id && !file_exists($event_theme_settings_file)) { file_put_contents($event_theme_settings_file, file_get_contents($global_theme_settings_file)); }

$speakers_data = json_decode(file_get_contents($speakers_file), true);
// Load event-specific theme, with a fallback to the global theme.
$theme_settings_data = $event_id && file_exists($event_theme_settings_file) ? json_decode(file_get_contents($event_theme_settings_file), true) : json_decode(file_get_contents($global_theme_settings_file), true);
if (empty($theme_settings_data)) { $theme_settings_data = ["brand_color" => "#D51007", "background_color" => "#f8f9fa", "text_color" => "#0b0b0b"]; }
$navigation_data = json_decode(file_get_contents($navigation_file), true);
$custom_sections_data = file_exists($sections_file) ? json_decode(file_get_contents($sections_file), true) : [];

// --- Dynamically build navigation based on visible sections ---
$dynamic_nav_items = [];
$settings_file = $event_id ? $data_dir . '/site-settings-' . $event_id . '.json' : '';
$site_settings_data = $event_id && file_exists($settings_file) ? json_decode(file_get_contents($settings_file), true) : [];
$section_visibility = $site_settings_data['section_visibility'] ?? [];

// Add default sections if they are visible
if ($section_visibility['about'] ?? true) {
    $dynamic_nav_items['about'] = ['text' => 'About', 'href' => get_permalink($event_id) . '#about', 'order' => 20];
}
if ($section_visibility['speakers'] ?? true) {
    $dynamic_nav_items['speakers'] = ['text' => 'Speakers', 'href' => get_permalink($event_id) . '#speakers', 'order' => 30];
}
if ($section_visibility['schedule'] ?? true) {
    $dynamic_nav_items['schedule-overview'] = ['text' => 'Schedule', 'href' => get_permalink($event_id) . '#schedule-overview', 'order' => 40];
}
if ($section_visibility['sponsors'] ?? true) {
    $dynamic_nav_items['sponsors'] = ['text' => 'Sponsors', 'href' => get_permalink($event_id) . '#sponsors', 'order' => 50];
}

// Add active custom sections that have a title
if (!empty($custom_sections_data) && is_array($custom_sections_data)) {
    foreach ($custom_sections_data as $section) {
        if (!empty($section['is_active']) && !empty($section['title'])) {
            $dynamic_nav_items['custom-' . $section['id']] = ['text' => $section['title'], 'href' => get_permalink($event_id) . '#custom-section-' . $section['id'], 'order' => $section['order'] ?? 100];
        }
    }
}

// Sort all navigation items by their order
uasort($dynamic_nav_items, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <style>
      :root {
        --brand: <?php echo esc_html($theme_settings_data['brand_color'] ?? '#D51007'); ?>;
        --bg: <?php echo esc_html($theme_settings_data['background_color'] ?? '#f8f9fa'); ?>;
        --text: <?php echo esc_html($theme_settings_data['text_color'] ?? '#0b0b0b'); ?>;
      }
    </style>
    <style>
      html {
        scroll-behavior: smooth;
      }
      .event-block.highlight {
        background: yellow;
        transition: background 1s ease;
      }
      .speaker-link { text-decoration: none; display: block; }
      .session-info p { margin: 0; }
      .card-body {
        position: relative;
      }
      .btn-edit-speaker {
          position: absolute;
          top: 12px;
          right: 12px;
          background: #ffc107;
          color: #212529;
          border: none;
          border-radius: 50%;
          width: 32px;
          height: 32px;
          cursor: pointer;
          font-weight: bold;
          z-index: 10;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 0;
          font-size: 16px;
          box-shadow: 0 2px 5px rgba(0,0,0,0.2);
          transition: all .2s;
      }
      .btn-edit-speaker:hover {
          transform: scale(1.1);
          background: #f5b700;
      }
      html, body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
      }
      * {
        box-sizing: border-box;
      }
      a {
        color: var(--brand);
        text-decoration: none;
      }
      img {
        max-width: 100%;
        height: auto;
        display: block;
      }
      .site-logo {
        height: 36px;
        width: auto;
      }
      .site {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      .container {
        width: 100%;
        max-width: var(--container);
        margin: 0 auto;
        padding-left: 24px;
        padding-right: 24px;
      }
      .nav {
        position: sticky;
        top: 0;
        background: rgba(255,255,255,.9);
        backdrop-filter: blur(6px) saturate(120%);
        border-bottom: 1px solid #00000010;
        z-index: 60;
      }
      .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 0;
      }
      .nav-links {
        display: flex;
        gap: .6rem;
        align-items: center;
      }
      .nav-links-main { display: flex; gap: .6rem; align-items: center; flex-grow: 1; }
      .nav-links-secondary { display: flex; gap: 1rem; align-items: center; margin-left: 2rem; }
      .nav-links-secondary a { font-weight: 600; color: #222; }
      .nav-links-secondary a:hover { color: var(--brand); }
      .nav-links a {
        padding: .55rem .75rem;
        border-radius: 999px;
        font-weight: 600;
        color: #222;
      }
      .nav-links a:hover {
        background: #00000006;
      }
      .nav-links a.btn-primary {
        color: #fff;
      }
      /* Login Dropdown */
      .login-dropdown {
        position: relative;
        display: inline-block;
      }
      .nav-login-btn {
        padding: .55rem .75rem;
        border-radius: 999px;
        font-weight: 600;
        color: #222;
        background: transparent;
        border: none;
        cursor: pointer;
        display: inline-block;
      }
      .nav-login-btn:hover {
        background: #00000006;
      }
      .login-dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 8px;
        overflow: hidden;
        right: 0;
      }
      .login-dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-weight: 500;
      }
      .login-dropdown-content a:hover { background-color: #f1f1f1; }
      .login-dropdown-content.show {
        display: block;
      }
      .btn {
        display: inline-flex;
        gap: .6rem;
        align-items: center;
        padding: .6rem 1rem;
        border-radius: 999px;
        font-weight: 700;
        border: 2px solid transparent;
      }
      .btn-primary {
        background: var(--brand);
        color: #fff;
        box-shadow: 0 8px 20px rgba(213,16,7,.14);
      }
      footer.footer {
        padding: 28px 0;
        color: var(--muted);
        border-top: 1px solid #f3f4f6;
        text-align: center;
      }
      .admin-bar .nav {
        top: 32px;
      }
      @media (max-width: 782px) {
        .admin-bar .nav {
          top: 46px;
        }
      }
      
      /* Search section */
      .search-section {
        background: #f8f9fa;
        padding: 60px 20px;
        text-align: center;
        border-bottom: 1px solid #eee;
      }
      .search-container {
        max-width: var(--container);
        margin: 0 auto;
      }
      .search-box {
        position: relative;
        margin: 30px auto 20px;
        max-width: 600px;
      }
      .search-input {
        width: 100%;
        padding: 12px 40px 12px 20px;
        border: 1px solid #ddd;
        border-radius: 30px;
        font-size: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      }
      .search-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
      }
      .filters {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
      }
      .filter-group {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
      }
      .filter-group-title {
        color: var(--brand);
        font-size: 14px;
        text-transform: uppercase;
        margin: 0 15px 0 0;
        font-weight: 700;
        white-space: nowrap;
      }
      .filter-btn {
        background: #e9ecef;
        color: #333;
        border: none;
        padding: 8px 18px;
        border-radius: 20px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease-in-out;
      }
      .filter-btn:hover, .filter-btn.active {
        background: var(--brand);
        color: #fff;
      }
      .results-info {
        text-align: center;
        margin: 30px 0;
        font-size: 18px;
        color: var(--muted);
      }
      
      /* Speaker grid */
      .speakers-grid {
        display: grid !important; /* Override WP block styles */
        grid-template-columns: repeat(auto-fill,minmax(250px,1fr));
        gap: 18px;
        padding: 40px 20px;
        max-width: var(--container);
        margin: 0 auto;
        align-items: start;
      }
      
      /* Speaker card */
      .wp-block-column.card { /* Match landing page selector */
        background: #fff;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
        opacity: 0;
        transform: translateY(14px);
        transition: opacity .6s ease, transform .6s ease, box-shadow .2s, transform .2s;
        display: flex;
        flex-direction: column;
      }
      .wp-block-column.card.visible {
        opacity: 1;
        transform: none;
      }
      .wp-block-column.card:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 40px rgba(11,11,11,.12);
      }
      .card-media, .card-media figure {
        height: 220px;
        background: linear-gradient(135deg, #e9e9e9, #f6f6f6);
      }
      .card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        transition: transform 0.3s ease; /* This was missing */
      }
      .speaker-card:hover .card-media img {
        transform: scale(1.05);
      }
      .card-body {
        padding: 16px;
        cursor: pointer;
        flex-grow: 1;
      }
      .pill {
        display: inline-block;
        padding: 2px 8px; /* Adjusted from landing page for consistency */
        border-radius: 12px;
        background: var(--brand);
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        margin-bottom: 8px; /* Adjusted from landing page for consistency */
        text-transform: uppercase;
      }
      .card-body h3 {
        margin: 0.5rem 0 0.25rem;
        font-size: 20px;
      }
      .meta {
        color: var(--muted);
        font-size: 14px;
        margin: 0;
      }
      .card-expand {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.3s ease;
        padding: 0 16px;
        background: #fafafa;
      }
      .wp-block-column.card.expanded .card-expand {
        max-height: 9999px;
        opacity: 1;
        padding: 14px 16px 16px;
      }
      .card-expand p {
        margin: 15px 0;
        font-size: 14px;
        line-height: 1.6;
      }
      .social-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 10px 0;
      }
      .social-links a {
        font-size: 14px;
        text-decoration: none;
      }
      .session-info {
        background-color: var(--brand);
        color: #fff;
        border-radius: 6px;
        padding: 12px;
        margin: 15px 0;
        font-size: 14px;
        line-height: 1.5;
      }
      .session-info strong {
        font-size: 14px;
      }
      .no-results {
        display: none;
        text-align: center;
        padding: 60px 20px;
        color: var(--muted);
      }
      .no-results h3 {
        margin-bottom: 10px;
        font-size: 24px;
      }
      /* Modal styles */
      .modal {
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
      }
      .modal-content {
        background-color: #fefefe;
        margin: auto;
        padding: 20px 30px 30px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: var(--card-radius);
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
      }
      .close-btn {
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
      }
      .close-btn:hover,
      .close-btn:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
      }
      #newSpeakerForm {
        display: flex;
        flex-direction: column;
        gap: 10px;
      }
      #newSpeakerForm h2 {
         margin-top: 0;
      }
      #editSpeakerForm .form-section-heading, #newSpeakerForm .form-section-heading { font-weight: bold; font-size: 1.1em; margin-top: 20px; margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px solid #eee; color: var(--brand); }
      #newSpeakerForm label {
        font-weight: 600;
        margin-top: 5px;
      }
      #newSpeakerForm input,
      #newSpeakerForm textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        font-size: 14px;
      }
      #newSpeakerForm .image-option-toggle { display: flex; gap: 15px; margin-bottom: 10px; }
      #newSpeakerForm .image-option-toggle label { font-weight: normal; margin-top: 0; }
      #newSpeakerForm button {
        margin-top: 15px;
        align-self: flex-start;
      }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="container nav-inner">
        <?php
            $event_page_url = $event_id ? get_permalink($event_id) : esc_url( get_permalink( get_page_by_path( 'events' ) ) );
        ?>
        <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            <img src="<?php echo plugins_url( '../assets/images/logo.png', __FILE__ ); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
          <?php
            echo '<div class="nav-links-main">';
            // Use the new dynamically generated navigation items
            if (!empty($dynamic_nav_items)) {
                foreach ($dynamic_nav_items as $nav_item) {
                    $href = esc_url($nav_item['href']);
                    $text = esc_html($nav_item['text']);
                    echo "<a href=\"{$href}\">{$text}</a>";
                }
            }
            echo '</div>';
          ?>
          <div class="nav-links-secondary">
            <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'events' ) ) ); ?>">View All Events</a>
            <a href="https://eventyay.com/e/4c0e0c27" target="_blank" rel="noopener" class="btn btn-primary">Register</a>
          </div>
        </nav>
      </div>
    </header>

    <main>
        <section class="search-section">
            <div class="search-container">
                <h1>FOSSASIA Summit Speakers</h1>
                <p>Discover all the amazing speakers joining us at FOSSASIA Summit 2025</p>
                
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search speakers...">
                    <span class="search-icon">🔍</span>
                </div>

                <div class="filters" id="speaker-filters-container">
                    <!-- Filter buttons will be dynamically generated here -->
                </div>

                <div class="sorting-controls" style="margin-top: 20px; text-align: center;">
                    <label for="sortSpeakers" style="font-weight:bold; margin-right: 10px;">Sort by:</label>
                    <select id="sortSpeakers" style="padding: 8px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="category_asc">Category (A-Z)</option>
                    </select>
                </div>
            </div>
        </section>
        
        <div class="results-info">
            Showing <span id="resultsCount">0</span> speakers
        </div>
        
        <div class="no-results" id="noResults">
            <h3>No speakers found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
        
        <div class="speakers-grid" id="speaker-grid">
            <!-- Speaker cards will be dynamically inserted here -->
        </div>
    </main>

    <footer class="footer">
        <small>© FOSSASIA • FOSSASIA Summit — Mar 13–15, 2025 • True Digital Park West, Bangkok</small>
    </footer>
</div><!-- #page -->

<!-- Edit Speaker Modal -->
<div id="editSpeakerModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="editSpeakerForm">
            <!-- Form fields will be populated by JS -->
        </form>
    </div>
</div>

<!-- Add New Speaker Modal -->
<div id="newSpeakerModal" class="modal" style="display:none;">
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
            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Add Speaker</button>
        </form>
    </div>
</div>
<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = <?php echo current_user_can( 'manage_options' ) ? 'true' : 'false'; ?>;
    const ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';

    // --- Get DOM elements ---
    const speakerGrid = document.getElementById('speaker-grid');
    const searchInput = document.getElementById('searchInput');
    const filterContainer = document.getElementById('speaker-filters-container');
    const resultsCountSpan = document.getElementById('resultsCount');
    const noResultsDiv = document.getElementById('noResults');

    // Exit if the main grid isn't found, to prevent script errors.
    if (!speakerGrid) return;

    let speakersData = <?php echo json_encode($speakers_data); ?>;
    if (!Array.isArray(speakersData)) speakersData = [];
    let currentFilter = 'all';
    let currentSort = 'name_asc';

    // Add reveal animation using a single observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    // Function to apply observer to new cards
    function observeNewCards() {
        document.querySelectorAll('.wp-block-column.card:not(.visible)').forEach(card => {
            observer.observe(card);
        });
    }

    function truncate(str, num) {
        if (!str) return '';
        if (str.length <= num) return str;
        return str.slice(0, num) + '...';
    }

    function renderSpeakers(speakers) {
        speakerGrid.innerHTML = '';
        if (speakers.length === 0) {
            if (noResultsDiv) noResultsDiv.style.display = 'block';
            if (resultsCountSpan) resultsCountSpan.textContent = '0';
            return;
        }
        if (noResultsDiv) noResultsDiv.style.display = 'none';

        speakers.forEach(speaker => {
            const editButtonHTML = isAdmin ? `<button class="btn-edit-speaker" data-id="${speaker.id}" title="Edit Speaker">✎</button>` : '';
 
            let sessionsHTML = 'Session details to be announced.';
            if (speaker.sessions) {
                if (Array.isArray(speaker.sessions) && speaker.sessions.length > 0) {
                    sessionsHTML = speaker.sessions.map(s => `<strong>${s.title || ''}</strong> on ${s.date || ''} at ${s.time || ''}`).join('<br>');
                } else if (typeof speaker.sessions === 'string') {
                    sessionsHTML = speaker.sessions; // For backward compatibility
                }
            }

            const speakerCard = document.createElement('div'); // This will be the wp-block-column
            speakerCard.className = 'wp-block-column card'; // Match landing page classes
            speakerCard.innerHTML = `
                <figure class="wp-block-image size-full card-media" role="img" aria-label="${speaker.name || ''} — Speaker photo">
                    <img src="${speaker.image || ''}" alt="${speaker.name || ''}" style="width:100%; height:100%; object-fit:cover;">
                </figure>
                <div class="wp-block-group card-body">
                    ${editButtonHTML}
                    <p class="pill" style="background-color:var(--brand);color:#fff;border-radius:12px;font-size:12px;padding-top:2px;padding-right:8px;padding-bottom:2px;padding-left:8px;font-weight:bold;">${speaker.category || ''}</p>
                    <h3 style="margin-top:0.5rem;margin-bottom:0.25rem;font-size:20px;">${speaker.name || ''}</h3>
                    <p class="meta" style="color:#5b636a;font-size:14px;">${speaker.title || ''}</p>
                </div>
                <div class="card-expand">
                    <p>${speaker.bio || ''}</p>
                    <div class="social-links">
                        ${speaker.social && speaker.social.linkedin ? `<a href="${speaker.social.linkedin}" target="_blank" rel="noopener">🔗 LinkedIn</a>` : ''}
                        ${speaker.social && speaker.social.twitter ? `<a href="${speaker.social.twitter}" target="_blank" rel="noopener">🐦 Twitter</a>` : ''}
                        ${speaker.social && speaker.social.github ? `<a href="${speaker.social.github}" target="_blank" rel="noopener">💻 GitHub</a>` : ''}
                        ${speaker.social && speaker.social.website ? `<a href="${speaker.social.website}" target="_blank" rel="noopener">🌐 Website</a>` : ''}
                    </div>
                    <a href="<?php echo esc_url( home_url( '/fossasia-summit/' ) ); ?>?speaker=${speaker.id || ''}#event-calendar" class="speaker-link" data-speaker="${speaker.id || ''}">
                        <div class="session-info">
                            <p><strong>Sessions:</strong><br>${sessionsHTML}</p>
                        </div>
                    </a>
                </div>
            `;
            speakerGrid.appendChild(speakerCard);
        });
        if (resultsCountSpan) resultsCountSpan.textContent = speakers.length;

        // Re-apply the observer to the newly created cards
        observeNewCards();
    }

    function filterAndSearchSpeakers() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        let filteredSpeakers = speakersData;

        if (currentFilter !== 'all') {
            // Use exact match for the category filter for better accuracy
            filteredSpeakers = filteredSpeakers.filter(speaker => speaker.category && speaker.category.toLowerCase() === currentFilter);
        }

        if (searchTerm) {
            filteredSpeakers = filteredSpeakers.filter(speaker => {
                const inBasicInfo = (speaker.name && speaker.name.toLowerCase().includes(searchTerm)) ||
                                    (speaker.title && speaker.title.toLowerCase().includes(searchTerm)) ||
                                    (speaker.category && speaker.category.toLowerCase().includes(searchTerm)) ||
                                    (speaker.bio && speaker.bio.toLowerCase().includes(searchTerm));

                const inSessions = Array.isArray(speaker.sessions) && speaker.sessions.some(session =>
                    session.title && session.title.toLowerCase().includes(searchTerm)
                );

                // Backward compatibility for string-based sessions
                const inOldSessions = typeof speaker.sessions === 'string' && speaker.sessions.toLowerCase().includes(searchTerm);

                return inBasicInfo || inSessions || inOldSessions;
            });
        }

        // Apply sorting
        filteredSpeakers.sort((a, b) => {
            if (currentSort === 'name_desc') return (b.name || '').localeCompare(a.name || '');
            if (currentSort === 'category_asc') return (a.category || '').localeCompare(b.category || '') || (a.name || '').localeCompare(b.name || '');
            return (a.name || '').localeCompare(b.name || ''); // Default to name_asc
        });

        renderSpeakers(filteredSpeakers);
    }

    function setupFilters() {
        if (!filterContainer) return;

        // 1. Get unique categories and sort them
        const uniqueCategories = [...new Set(speakersData.map(s => s.category))].filter(Boolean).sort();

        // 2. Create the HTML for the buttons
        const buttonsHTML = uniqueCategories.map(category => {
            // Use the category name directly for the filter value, but lowercase it for comparison
            return `<button class="filter-btn" data-filter="${category.toLowerCase()}">${category}</button>`;
        }).join('');

        // 3. Replace the hardcoded filters with dynamic ones
        filterContainer.innerHTML = `
            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">All Speakers</button>
                ${buttonsHTML}
            </div>
        `;

        // 4. Add event listeners to all new buttons
        const allFilterButtons = filterContainer.querySelectorAll('.filter-btn');
        allFilterButtons.forEach(button => {
            button.addEventListener('click', () => {
                allFilterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                currentFilter = button.dataset.filter;
                filterAndSearchSpeakers();
            });
        });
    }

    const sortSelect = document.getElementById('sortSpeakers');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            currentSort = sortSelect.value;
            filterAndSearchSpeakers();
        });
    }
    if (searchInput) searchInput.addEventListener('keyup', filterAndSearchSpeakers);

    // Initial render
    setupFilters();
    filterAndSearchSpeakers();
    observeNewCards(); // Observe initial cards

    // Admin Edit Functionality
    if (isAdmin) {
        const editSpeakerModal = document.getElementById('editSpeakerModal');
        const adminNonce = '<?php echo wp_create_nonce("fossasia_admin_nonce"); ?>';
        if (!editSpeakerModal) return;
        const editSpeakerForm = document.getElementById('editSpeakerForm');
        const closeEditModalBtn = editSpeakerModal.querySelector('.close-btn');
        let currentlyEditingId = null;
        const eventId = <?php echo $event_id; ?>;

        const newSpeakerModal = document.getElementById('newSpeakerModal');
        const newSpeakerForm = document.getElementById('newSpeakerForm');
        const addNewSpeakerBtn = document.getElementById('addNewSpeakerBtn');
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


        if (addNewSpeakerBtn) {
            addNewSpeakerBtn.addEventListener('click', () => {
                newSpeakerForm.reset();
                newSpeakerModal.style.display = 'flex';
            });
        }

        [newSpeakerForm, editSpeakerForm].forEach(form => {
            if (!form) return;
            form.querySelectorAll('[name="image_source"]').forEach(radio => {
                radio.addEventListener('change', () => toggleImageInputs(form));
            });
        });

        closeNewSpeakerBtn.addEventListener('click', () => newSpeakerModal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === newSpeakerModal) newSpeakerModal.style.display = 'none'; });

        newSpeakerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = newSpeakerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';

            const formData = new FormData(newSpeakerForm);
            
            const processNewSpeaker = (imageUrl) => {
                const newSpeaker = {
                    id: `manual-${Date.now()}`,
                    name: formData.get('name'), title: formData.get('title'), category: formData.get('category'),
                    image: imageUrl, bio: formData.get('bio'), featured: false,
                    social: { linkedin: formData.get('linkedin'), twitter: formData.get('twitter'), github: formData.get('github'), website: formData.get('website') },
                    sessions: [{ title: formData.get('talkTitle'), date: formData.get('talkDate'), time: formData.get('talkTime'), end_time: formData.get('talkEndTime') }]
                };

                const ajaxFormData = new FormData();
                ajaxFormData.append('action', 'fossasia_manage_speakers');
                ajaxFormData.append('nonce', adminNonce);
                ajaxFormData.append('task', 'save_all');
                ajaxFormData.append('event_id', eventId);
                ajaxFormData.append('speakers', JSON.stringify([newSpeaker, ...speakersData]));

                fetch(ajaxUrl, { method: 'POST', body: ajaxFormData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert('Speaker added successfully. The page will now reload.');
                        window.location.reload();
                    } else {
                        alert('Error adding speaker: ' + data.data);
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Add Speaker';
                    }
                });
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

        // Use event delegation for both edit and expand clicks
        speakerGrid.addEventListener('click', (e) => {
            // Handle edit button click
            if (e.target.matches('.btn-edit-speaker')) {
                e.stopPropagation(); // Prevent card from expanding
                const speakerId = e.target.dataset.id;
                openEditModal(speakerId);
                return;
            }

            if (e.target.matches('.btn-delete-speaker')) {
                e.stopPropagation();
                const speakerId = e.target.dataset.id;
                const speakerName = e.target.dataset.name;
                if (confirm(`Are you sure you want to delete "${speakerName}"? This action cannot be undone.`)) {
                    const updatedSpeakers = speakersData.filter(s => s.id !== speakerId);
                    const ajaxFormData = new FormData();
                    ajaxFormData.append('action', 'fossasia_manage_speakers');
                    ajaxFormData.append('nonce', adminNonce);
                    ajaxFormData.append('task', 'save_all');
                    ajaxFormData.append('event_id', eventId);
                    ajaxFormData.append('speakers', JSON.stringify(updatedSpeakers));

                    fetch(ajaxUrl, { method: 'POST', body: ajaxFormData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Speaker deleted successfully. The page will now reload.');
                            window.location.reload();
                        } else {
                            alert('Error deleting speaker: ' + data.data);
                        }
                    });
                }
                return;
            }

            // Handle card expand/collapse on card-body click
            const cardBody = e.target.closest('.card-body');
            if (cardBody) {
                const card = cardBody.closest('.wp-block-column.card');
                if (card) card.classList.toggle('expanded');
            }
        });

        function escapeHTML(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function openEditModal(speakerId) {
            const speaker = speakersData.find(s => s.id === speakerId);
            if (!speaker) return;
            currentlyEditingId = speakerId;
            
            const session = speaker.sessions && Array.isArray(speaker.sessions) && speaker.sessions[0] ? speaker.sessions[0] : { title: '', date: '', time: '', end_time: '', abstract: '' };

            editSpeakerForm.innerHTML = `
                <h2>Edit Speaker</h2>
                <label for="editSpeakerName">Name:</label>
                <input type="text" id="editSpeakerName" name="name" value="${escapeHTML(speaker.name)}" required>
                
                <label for="editSpeakerTitle">Title:</label>
                <input type="text" id="editSpeakerTitle" name="title" value="${escapeHTML(speaker.title)}" required>
                
                <label for="editSpeakerCategory">Category:</label>
                <input type="text" id="editSpeakerCategory" name="category" value="${escapeHTML(speaker.category)}" required>
                
                <label for="editSpeakerImage">Image URL:</label>
                <input type="url" id="editSpeakerImage" name="image" value="${escapeHTML(speaker.image)}" required>
                
                <label for="editSpeakerBio">Bio:</label>
                <textarea id="editSpeakerBio" name="bio" rows="4" required>${escapeHTML(speaker.bio)}</textarea>
                
                <p class="form-section-heading">Session Details</p>
                <label for="editTalkTitle">Talk Title:</label>
                <input type="text" id="editTalkTitle" name="talkTitle" value="${escapeHTML(session.title)}" required>
                <label for="editTalkAbstract">Talk Abstract:</label>
                <textarea id="editTalkAbstract" name="talkAbstract" rows="4">${escapeHTML(session.abstract || '')}</textarea>
                <label for="editTalkDate">Date:</label>
                <input type="date" id="editTalkDate" name="talkDate" value="${escapeHTML(session.date)}" required>
                <label for="editTalkTime">Start Time:</label>
                <input type="time" id="editTalkTime" name="talkTime" value="${escapeHTML(session.time)}" required>
                <label for="editTalkEndTime">End Time:</label>
                <input type="time" id="editTalkEndTime" name="talkEndTime" value="${escapeHTML(session.end_time || '')}" required>
                <p class="form-section-heading">Social Links (Optional)</p>
                <input type="url" name="linkedin" placeholder="LinkedIn URL" value="${escapeHTML(speaker.social?.linkedin || '')}">
                <input type="url" name="twitter" placeholder="Twitter URL" value="${escapeHTML(speaker.social?.twitter || '')}">
                <input type="url" name="github" placeholder="GitHub URL" value="${escapeHTML(speaker.social?.github || '')}">
                <input type="url" name="website" placeholder="Website URL" value="${escapeHTML(speaker.social?.website || '')}">
                
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Save Changes</button>
            `;
            editSpeakerModal.style.display = 'flex';
        }

        closeEditModalBtn.addEventListener('click', () => { editSpeakerModal.style.display = 'none'; });
        window.addEventListener('click', (event) => { if (event.target == editSpeakerModal) editSpeakerModal.style.display = 'none'; });

        editSpeakerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(editSpeakerForm);
            const updatedSpeakerData = {
                id: currentlyEditingId,
                name: formData.get('name'), title: formData.get('title'), category: formData.get('category'), image: formData.get('image'), bio: formData.get('bio'),
                social: { linkedin: formData.get('linkedin'), twitter: formData.get('twitter'), github: formData.get('github'), website: formData.get('website') }, featured: speakersData.find(s => s.id === currentlyEditingId)?.featured || false,
                sessions: [{
                    title: formData.get('talkTitle'),
                    abstract: formData.get('talkAbstract'),
                    date: formData.get('talkDate'),
                    time: formData.get('talkTime'),
                    end_time: formData.get('talkEndTime')
                }]
            };

            const ajaxFormData = new FormData();
            ajaxFormData.append('action', 'fossasia_manage_speakers');
            ajaxFormData.append('nonce', adminNonce);
            ajaxFormData.append('task', 'update_live');
            ajaxFormData.append('speaker', JSON.stringify(updatedSpeakerData));
            ajaxFormData.append('event_id', eventId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: ajaxFormData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the local speakers data array and re-render
                    speakersData = speakersData.map(s => s.id === currentlyEditingId ? updatedSpeakerData : s);
                    currentFilter = 'all'; // Reset to 'all' to ensure view is predictable
                    setupFilters(); // Re-generate filters to include any new categories
                    filterAndSearchSpeakers(); // Re-render the grid with the new data
                    editSpeakerModal.style.display = 'none';
                    alert('Speaker updated successfully.');
                } else {
                    alert('Error updating speaker: ' + data.data);
                }
            });
        });
    }
});
</script>
</body>
</html>
