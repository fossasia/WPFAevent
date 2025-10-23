<?php
$post_id = get_the_ID();
$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';

// Event-specific files
$speakers_file = $data_dir . '/speakers-' . $post_id . '.json';
$sponsors_file = $data_dir . '/sponsors-' . $post_id . '.json';
$settings_file = $data_dir . '/site-settings-' . $post_id . '.json';
$schedule_file = $data_dir . '/schedule-' . $post_id . '.json';

// Global files
$sections_file = $data_dir . '/custom-sections.json';
$navigation_file = $data_dir . '/navigation.json';
$global_theme_settings_file = $data_dir . '/theme-settings.json';
$event_theme_settings_file = $data_dir . '/theme-settings-' . $post_id . '.json';

// Ensure file exists, if not, it will be created on plugin activation.
if (!file_exists($speakers_file)) { file_put_contents($speakers_file, '[]'); }
if (!file_exists($sponsors_file)) { file_put_contents($sponsors_file, '[]'); }
if (!file_exists($settings_file)) { file_put_contents($settings_file, '{}'); }
if (!file_exists($sections_file)) { file_put_contents($sections_file, '[]'); }
if (!file_exists($navigation_file)) { file_put_contents($navigation_file, '[]'); }
if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
if (!file_exists($event_theme_settings_file)) { file_put_contents($event_theme_settings_file, file_get_contents($global_theme_settings_file)); }

$speakers_data = json_decode(file_get_contents($speakers_file), true);
$sponsors_data = json_decode(file_get_contents($sponsors_file), true);
$site_settings_data = json_decode(file_get_contents($settings_file), true) ?: [];
$custom_sections_data = json_decode(file_get_contents($sections_file), true);
$navigation_data = json_decode(file_get_contents($navigation_file), true);
// Load event-specific theme, with a fallback to the global theme.
$theme_settings_data = json_decode(file_get_contents($event_theme_settings_file), true);
if (empty($theme_settings_data)) { $theme_settings_data = json_decode(file_get_contents($global_theme_settings_file), true); }

/**
 * Converts a standard video URL (YouTube, Vimeo) into an embeddable URL.
 *
 * @param string $url The original video URL.
 * @return string The embeddable URL.
 */
function get_video_embed_url($url) {
    if (empty($url)) {
        return '';
    }

    // Check for YouTube
    if (preg_match('/(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|)([\w-]{11})/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[3];
    }

    // Check for Vimeo
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }

    // Return the original URL if it's not a recognized format (might be a direct embed link already)
    return $url;
}


/**
 * Renders custom sections for a specific position on the page.
 *
 * @param string $position The identifier for the section's location (e.g., 'after_venue').
 * @param array  $all_sections The array of all custom section data.
 */
function render_custom_sections($position, $all_sections) {
	if (empty($all_sections) || !is_array($all_sections)) {
		return;
	}

	$sections_to_render = array_filter($all_sections, function($section) use ($position) {
		return isset($section['position'], $section['is_active']) && $section['position'] === $position && $section['is_active'];
	});

	if (empty($sections_to_render)) {
		return;
	}

	usort($sections_to_render, function($a, $b) {
		return ($a['order'] ?? 10) <=> ($b['order'] ?? 10);
	});

	foreach ($sections_to_render as $section) {
		$section_id = esc_attr($section['id']);
		$layout = $section['layout'] ?? 'full_width';
		$section_type = $section['type'] ?? 'content';
		?>
		<section class="wp-block-group custom-section-added" id="custom-section-<?php echo $section_id; ?>" style="padding: 48px 0;">
			<div class="container reveal" style="max-width: var(--container); margin: 0 auto; padding: 0 24px;">
				<div class="section-head">
					<?php if (!empty($section['title'])) : ?>
						<h2 class="h2"><?php echo esc_html($section['title']); ?></h2>
					<?php endif; ?>
					<?php if (!empty($section['subtitle'])) : ?>
						<p class="meta"><?php echo esc_html($section['subtitle']); ?></p>
					<?php endif; ?>
				</div>

				<?php
				if ($section_type === 'media') {
					// --- RENDER MEDIA SECTION ---
					if ($section['mediaType'] === 'photo' && !empty($section['photo_src'])) {
						$image_src = $section['photo_src'];
						echo '<figure class="wp-block-image size-full"><img src="' . $image_src . '" alt="' . esc_attr($section['title']) . '" style="border-radius: 12px; width: 100%;"></figure>';
					} elseif ($section['mediaType'] === 'video' && !empty($section['video_embed_src'])) {
						$embed_url = get_video_embed_url($section['video_embed_src']);
						echo '<div style="padding:0; aspect-ratio: 16/9; overflow:hidden; border-radius: 12px;"><iframe width="100%" height="100%" src="' . esc_url($embed_url) . '" title="Embedded video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
					} elseif ($section['mediaType'] === 'carousel' && !empty($section['carousel_images']) && is_array($section['carousel_images'])) {
						$carousel_id = 'carousel-' . esc_attr($section['id']);
						$timer = !empty($section['carousel_timer']) ? absint($section['carousel_timer']) * 1000 : 5000;
						?>
						<div class="media-carousel" id="<?php echo $carousel_id; ?>">
							<?php foreach ($section['carousel_images'] as $index => $image_src) : ?>
								<img src="<?php echo $image_src; ?>" class="carousel-slide" alt="Carousel image <?php echo $index + 1; ?>" style="opacity: <?php echo $index === 0 ? '1' : '0'; ?>;">
							<?php endforeach; ?>
						</div>
						<script>
							(function() {
								const carousel = document.getElementById("<?php echo $carousel_id; ?>");
								if (!carousel) return;
								const slides = carousel.querySelectorAll(".carousel-slide");
								if (slides.length < 2) return;
								let current = 0;
								setInterval(() => {
									if (slides[current]) slides[current].style.opacity = 0;
									current = (current + 1) % slides.length;
									if (slides[current]) slides[current].style.opacity = 1;
								}, <?php echo $timer; ?>);
							})();
						</script>
						<?php
					}
				} elseif ($layout === 'full_width') {
					// Full width layout
					?>
					<div class="panel">
						<?php if (!empty($section['contentTitle'])) : ?>
							<h3 style="margin-top:0;"><?php echo esc_html($section['contentTitle']); ?></h3>
						<?php endif; ?>
						<?php if (!empty($section['contentBody'])) : ?>
							<div class="custom-section-content"><?php echo wp_kses_post($section['contentBody']); ?></div>
						<?php endif; ?>
						<?php if (!empty($section['buttonText']) && !empty($section['buttonLink'])) : ?>
							<div class="wp-block-buttons hero-ctas" style="margin-top:14px;">
								<div class="wp-block-button btn btn-primary">
									<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($section['buttonLink']); ?>" target="_blank" rel="noopener"><?php echo esc_html($section['buttonText']); ?></a>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<?php
				} else {
					// Two column layouts
					$media_col_html = '';
					if ($section['mediaType'] === 'photo' && !empty($section['photo_src'])) {
						$image_src = $section['photo_src'];
						$media_col_html = '<div class="wp-block-column" style="padding:0;"><figure class="wp-block-image size-full" style="margin:0;"><img src="' . $image_src . '" alt="' . esc_attr($section['title']) . ' media" style="border-radius: 12px; width: 100%; height: 100%; object-fit: cover;"></figure></div>';
					} elseif ($section['mediaType'] === 'map' && !empty($section['map_embed_src'])) {
						$media_col_html = '<div class="wp-block-column map panel reveal"><iframe width="100%" height="100%" style="min-height:320px;border:0;border-radius:12px;" loading="lazy" src="' . esc_url($section['map_embed_src']) . '" title="Venue map"></iframe></div>';
					}

					$content_col_html = '<aside class="wp-block-column panel reveal">';
					if (!empty($section['contentTitle'])) {
						$content_col_html .= '<h3 style="margin-top:0;">' . esc_html($section['contentTitle']) . '</h3>';
					}
					if (!empty($section['contentBody'])) {
						$content_col_html .= '<div class="custom-section-content">' . wp_kses_post($section['contentBody']) . '</div>';
					}
					if (!empty($section['buttonText']) && !empty($section['buttonLink'])) {
						$content_col_html .= '<div class="wp-block-buttons hero-ctas" style="margin-top:14px;"><div class="wp-block-button btn btn-primary"><a class="wp-block-button__link wp-element-button" href="' . esc_url($section['buttonLink']) . '" target="_blank" rel="noopener">' . esc_html($section['buttonText']) . '</a></div></div>';
					}
					$content_col_html .= '</aside>';

					?>
					<div class="wp-block-columns venue-grid">
						<?php
						if ($layout === 'two_col_media_left') {
							if (!empty($media_col_html)) echo $media_col_html;
							echo $content_col_html;
						} else { // two_col_media_right
							echo $content_col_html;
							if (!empty($media_col_html)) echo $media_col_html;
						}
						?>
					</div>
					<?php
				}
				?>
			</div>
		</section>
		<?php
	}
}

// --- Dynamic Event Data for Hero ---
$is_dynamic_event_page = get_post_meta( get_the_ID(), '_event_date', true );
$event_date_string = 'March 13–15, 2025 • Bangkok'; // Default date string
$event_title_string = 'FOSSASIA Summit — Building open tech across Asia'; // Default title

$hero_image_url = ''; // Default

if ( $is_dynamic_event_page && has_post_thumbnail( get_the_ID() ) ) {
    $hero_image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
}

if ( $is_dynamic_event_page ) {
    $event_start_date = get_post_meta( get_the_ID(), '_event_date', true );
    $event_title_string = get_the_title(); // Get the post title for the event
    $event_end_date = get_post_meta( get_the_ID(), '_event_end_date', true );
    $event_place = get_post_meta( get_the_ID(), '_event_place', true );
    $event_lead_text = get_post_meta( get_the_ID(), '_event_lead_text', true );

    if ( !empty($event_start_date) ) {
        $start = date_create($event_start_date);
        if ( !empty($event_end_date) && $event_end_date !== $event_start_date ) {
            $end = date_create($event_end_date);
            // e.g., "Mar 13 - 15, 2025"
            $event_date_string = date_format($start, 'M j') . '–' . date_format($end, 'j, Y');
        } else {
            $event_date_string = date_format($start, 'F j, Y');
        }
        $event_date_string .= ' • ' . esc_html($event_place);
    }
}

// Get section visibility settings
$section_visibility = $site_settings_data['section_visibility'] ?? [];

// --- Dynamically build navigation based on visible sections ---
$dynamic_nav_items = [];

// Add default sections if they are visible
if ($section_visibility['about'] ?? true) {
    $dynamic_nav_items['about'] = ['text' => 'About', 'href' => '#about', 'order' => 20];
}
if ($section_visibility['speakers'] ?? true) {
    $dynamic_nav_items['speakers'] = ['text' => 'Speakers', 'href' => '#speakers', 'order' => 30];
}
if ($section_visibility['schedule'] ?? true) {
    $dynamic_nav_items['schedule-overview'] = ['text' => 'Schedule', 'href' => '#schedule-overview', 'order' => 40];
}
if ($section_visibility['sponsors'] ?? true) {
    $dynamic_nav_items['sponsors'] = ['text' => 'Sponsors', 'href' => '#sponsors', 'order' => 50];
}

// Add active custom sections that have a title
if (!empty($custom_sections_data) && is_array($custom_sections_data)) {
    $active_custom_sections = array_filter($custom_sections_data, function($section) {
        return !empty($section['is_active']) && !empty($section['title']);
    });

    foreach ($active_custom_sections as $section) {
        $nav_item = [
            'text' => $section['title'],
            'href' => '#custom-section-' . $section['id'],
            'order' => $section['order'] ?? 100 // Use the section's order for sorting
        ];
        $dynamic_nav_items['custom-' . $section['id']] = $nav_item;
    }
}

// Sort all navigation items by their order
uasort($dynamic_nav_items, fn($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100));
?>
<?php
    // Determine the correct registration link
    $registration_link = get_post_meta( get_the_ID(), '_event_registration_link', true );
    if (empty($registration_link)) { $registration_link = 'https://eventyay.com/e/4c0e0c27'; } // Fallback

    // Get CFS button details from event-specific settings, with fallbacks
    $cfs_button_text = $site_settings_data['cfs_button_text'] ?? 'Call for Speakers';
    $cfs_button_link = $site_settings_data['cfs_button_link'] ?? get_post_meta( get_the_ID(), '_event_cfs_link', true );
    if (empty($cfs_button_link)) { $cfs_button_link = 'https://eventyay.com/e/4c0e0c27/cfs'; }
?>
<!-- wp:group {"className":"site","layout":{"type":"constrained"}} -->
<div class="wp-block-group site">

<!-- wp:html -->
<style>
  :root {
    --brand: <?php echo esc_html($theme_settings_data['brand_color'] ?? '#D51007'); ?>;
    --bg: <?php echo esc_html($theme_settings_data['background_color'] ?? '#f8f9fa'); ?>;
    --text: <?php echo esc_html($theme_settings_data['text_color'] ?? '#0b0b0b'); ?>;
  }
  /* reset + layout */
  *{box-sizing:border-box}html,body{height:100%;margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  a{color:var(--brand);text-decoration:none}
  img{max-width:100%;height:auto;display:block}
  .site{min-height:100vh;display:flex;flex-direction:column}
  .site-logo { height: 36px; width: auto; }
  .container {
    width: 100%;
    max-width: var(--container);
    margin: 0 auto;
    padding-left: 24px;
    padding-right: 24px;
  }
  /* nav */
  .nav{position:sticky;top:0;background:rgba(255,255,255,.9);backdrop-filter:blur(6px) saturate(120%);border-bottom:1px solid #00000010;z-index:60}
  .nav-inner{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
  .brand{display:flex;align-items:center;gap:.6rem;font-weight:700}
  .brand-mark{width:36px;height:36px;border-radius:8px;background:var(--brand);box-shadow:0 6px 20px rgba(213,16,7,.18)}
  .nav-links{display:flex;gap:.6rem;align-items:center}
  .nav-links a{padding:.55rem .75rem;border-radius:999px;font-weight:600;color:#222}
  .nav-links a:hover{background:#00000006}
  .nav-links a.btn-primary { color: #fff; }
  /* Login Dropdown */
  .nav-links-main { display: flex; gap: .6rem; align-items: center; flex-grow: 1; }
  .nav-links-secondary { display: flex; gap: 1rem; align-items: center; margin-left: 2rem; }
  .nav-links-secondary a { font-weight: 600; color: #222; }
  .nav-links-secondary a:hover { color: var(--brand); }

  .nav-dropdown { position: relative; }
  .nav-dropdown-toggle { cursor: pointer; }
  .nav-dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    min-width: 200px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    z-index: 100;
    border-radius: 8px;
    padding: 8px 0;
  }
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
  .login-dropdown-content a { color: black; padding: 12px 16px; text-decoration: none; display: block; font-weight: 500; }
  .login-dropdown-content a:hover { background-color: #f1f1f1; }
  .login-dropdown-content.show { display: block; }
  .nav-dropdown:hover .nav-dropdown-content { display: block; }
  .nav-dropdown-content a {
    display: block;
    padding: 8px 16px !important;
  }
  /* Carousel Styles */
  .media-carousel {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    overflow: hidden;
    border-radius: 12px;
    background: #f0f0f0;
  }
  .media-carousel .carousel-slide {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    object-fit: cover;
    opacity: 0; transition: opacity 0.8s ease-in-out;
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
  .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; cursor: pointer; }
  #newSpeakerForm { display: flex; flex-direction: column; gap: 10px; }
  #newSpeakerForm h2 { margin-top: 0; }
  #newSpeakerForm label { font-weight: 600; margin-top: 5px; }
  #newSpeakerForm input, #newSpeakerForm textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px; }
  #newSpeakerForm .image-option-toggle { display: flex; gap: 15px; margin-bottom: 10px; }
  #newSpeakerForm .image-option-toggle label { font-weight: normal; margin-top: 0; }
  #newSpeakerForm button { margin-top: 15px; align-self: flex-start; }
  .btn{display:inline-flex;gap:.6rem;align-items:center;padding:.6rem 1rem;border-radius:999px;font-weight:700;border:2px solid transparent}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 8px 20px rgba(213,16,7,.14)}
  .btn-ghost{background:transparent;border:2px solid var(--brand);color:var(--brand)}
  /* hero */
  .btn-primary .wp-block-button__link { color: #fff !important; } .hero{min-height:88vh;display:grid;place-items:center;text-align:center;padding:72px 0;position:relative;overflow:hidden}
  .hero-bg{position:absolute;inset:0;background:
    radial-gradient(40vw 40vw at 8% 50%, #fff0f0, transparent),
    radial-gradient(30vw 30vw at 92% 50%, #fff7f1, transparent);mix-blend-mode:normal;opacity:.9;pointer-events:none}
  .hero-inner{position:relative;z-index:2;padding:0 12px} .kicker{display:inline-block;background:#fff0f0;color:var(--brand);padding:.7rem 1.2rem;border-radius:999px;font-weight:800;margin-bottom:1rem; font-size: 1.25rem;}
  h1{font-size:clamp(2rem,5.5vw,3.6rem);line-height:1.02;margin:.15rem 0 .6rem}
  .lead{color:var(--muted);font-size:1.05rem;max-width:70ch;margin:0 auto 1.25rem}
  .hero-ctas{display:flex;gap:.7rem;justify-content:center;flex-wrap:wrap;margin-top:1rem}
  /* sections */ section{padding:48px 0}
  .section-head{display:flex;align-items:end;justify-content:space-between;gap:1rem;margin-bottom:18px}
  .h2{font-size:clamp(1.25rem,3vw,1.8rem);margin:0}
  .h3{font-size:clamp(1.1rem,2.5vw,1.4rem);margin:28px 0 18px;color:var(--brand)}
  .lead-center{text-align:center;color:var(--muted);max-width:68ch;margin:0 auto 28px}
  /* speakers */
  .grid{display:grid;gap:18px}
  .grid-4{grid-template-columns:repeat(4,1fr)}
  @media (max-width:1100px){.grid-4{grid-template-columns:repeat(3,1fr)}}
  @media (max-width:820px){.grid-4{grid-template-columns:repeat(2,1fr)}}
  @media (max-width:520px){.grid-4{grid-template-columns:1fr}}
  .card{background:#fff;border-radius:var(--card-radius);box-shadow:var(--shadow);overflow:hidden;transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column}
  .card:hover{transform:translateY(-6px);box-shadow:0 18px 40px rgba(11,11,11,.12)}
  .card-media{height:220px;background:linear-gradient(135deg,#e9e9e9,#f6f6f6)}
  .card-body{padding:16px;flex-grow:1}
  .meta{color:var(--muted);font-size:.95rem}
  .pill{display:inline-block;padding:.2rem .5rem;border-radius:999px;background:#f3f5f7;font-weight:700;font-size:.82rem}
  /* schedule tabs */
  .tabs{display:flex;gap:.5rem;flex-wrap:wrap}
  .tab{padding:.45rem .8rem;border-radius:999px;background:#f6f7f9;border:1px solid #e7e9ec;cursor:pointer;font-weight:800}
  .tab small {
    font-weight: 400;
    opacity: 0.8;
    font-size: 0.8em;
    margin-left: 4px;
  }
  .tab.active{background:var(--brand);color:#fff;box-shadow:0 10px 30px rgba(213,16,7,.12)}
  .panel{margin-top:18px}
  table{width:100%;border-collapse:collapse;border-radius:12px;overflow:hidden;table-layout:fixed}
  .schedule-table th, .schedule-table td { padding: 16px 14px; text-align: left; word-wrap: break-word; }
  .schedule-table th { 
    background: var(--brand); /* Red background */
    color: #fff; /* White text */
    font-weight: bold; /* Bold text */
  }
  .schedule-table td { background: #fff; color: var(--text); border-bottom: 1px solid var(--brand); } /* White cells, black text, red lines */
  .schedule-table tr:hover td { background-color: #f1f3f5; }
  .schedule-table td:first-child { font-weight: 700; }
  .badge{display:inline-block;padding:.18rem .45rem;border-radius:8px;background:#eef6ff;color:#0b63b8;font-weight:800;font-size:.82rem}
  /* sponsors */
  .sponsors{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;align-items:stretch}
  @media (max-width:1100px){.sponsors{grid-template-columns:repeat(3,1fr)}}
  @media (max-width:600px){.sponsors{grid-template-columns:repeat(2,1fr)}}
  .sponsors.sponsors-centered {
    display: flex; /* Override grid display */
    flex-wrap: wrap; /* Allow logos to wrap to the next line */
    justify-content: center; /* Center the logos horizontally */
  }
  .sponsors.sponsors-centered .slogo {
    width: auto; /* Allow the figure to shrink to its content's width */
    flex-grow: 0; /* Prevent the item from growing */
  }
  .slogo{background:#fff;padding:18px;border-radius:12px;box-shadow:var(--shadow);display:grid;place-items:center; width: 100%;}
  /* venue */
  .venue-grid{display:grid;grid-template-columns:1.25fr .75fr;gap:22px}
  @media (max-width:880px){.venue-grid{grid-template-columns:1fr}}
  .map{min-height:320px;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);background:#eaeaea}
  .panel{background:#fff;padding:18px;border-radius:12px;box-shadow:var(--shadow)}
  /* CTA + footer */
  .cta{padding:54px 0;background:linear-gradient(180deg,#fff7f6, var(--bg, #fff))}
  .custom-section-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
  }
  .cta-inner{display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap}
  .cta-box{background:var(--text);color:#fff;padding:26px;border-radius:14px;box-shadow:0 10px 30px rgba(11,11,11,.12);flex:1}
  .footer{padding:28px 0;color:var(--muted);border-top:1px solid #f3f4f6}
  /* reveal */
  .reveal{opacity:0;transform:translateY(14px);transition:opacity .6s ease,transform .6s ease}
  .reveal.visible{opacity:1;transform:none}
  /* utilities */
  .center{text-align:center}

  /* About section specifics */
  .about { background:#fff; border-radius:14px; padding:28px; box-shadow:var(--shadow); margin-top:24px; }
  .about .subhead { font-weight:800; color:var(--brand); display:inline-block; background:#fff0f0; padding:.25rem .6rem; border-radius:999px; margin-bottom:12px; }
  .about p { color:var(--muted); line-height:1.6; margin-bottom:14px; }
  .about ul { margin:12px 0 0 1.25rem; color:var(--muted); }
  .about li { margin:8px 0; }
  .info-grid{display:grid;grid-template-columns:1fr 320px;gap:20px}
  @media (max-width:980px){ .info-grid{grid-template-columns:1fr} }
  .venue-address{font-size:.95rem;color:var(--muted);background:#fbfcfd;padding:12px;border-radius:10px}
  .muted-note{font-size:.95rem;color:var(--muted)}
  
  /* Gutenberg content area */
  .gutenberg-content { padding: 40px 0; }
  .gutenberg-content > * { max-width: var(--container); margin-left: auto; margin-right: auto; }
  .gutenberg-content .wp-block-group, 
  .gutenberg-content .wp-block-cover, 
  .gutenberg-content .wp-block-columns { max-width: none; }
  
  /* Admin bar adjustment */
  .admin-bar .nav { top: 32px; }
  @media (max-width: 782px) {
    .admin-bar .nav { top: 46px; }
  }
  
  /* Speaker card expandable content */
  .card-expand { 
    max-height: 0; 
    overflow: hidden; 
    opacity: 0; 
    transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.3s ease; 
    padding: 0 16px; 
    background: #fafafa; 
  }
  .card.expanded .card-expand { 
    max-height: 9999px;
    opacity: 1;
    padding: 14px 16px 16px;
  }
  /* Custom rule for centering the 'More Speakers' button */
  .wp-block-buttons.aligncenter { 
    display: flex !important;
    justify-content: center;
    width: 100% !important;
  }
  .wp-block-buttons.aligncenter .wp-block-button.btn {
    display: block !important;
    width: auto !important;
  }
  html {
    scroll-behavior: smooth;
  }
  .back-to-top-btn {
    position: fixed;
    bottom: 20px;
    left: 20px;
    display: none;
    width: 40px;
    height: 40px;
    background-color: var(--brand);
    color: #fff;
    text-align: center;
    line-height: 40px;
    font-size: 24px;
    border-radius: 50%;
    z-index: 100;
    text-decoration: none;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: opacity 0.3s, visibility 0.3s;
    /* Adjusted for better caret alignment */
    line-height: 38px;
    font-size: 28px;
  }
  .back-to-top-btn:hover { background-color: var(--brand-600); }

  .panel.highlight {
    background: #fff7a3; /* highlight color */
    transition: background 1s ease;
    border-radius: 12px;
  }
  tr.highlight {
    background: #fff7a3; /* highlight color */
    transition: background 1s ease;
  }
  .speaker-link { text-decoration: none; display: block; }
  .session-info p { margin: 0; }

  .social-icons { display: flex; gap: 1rem; margin-top: 0.75rem; justify-content: flex-end; }
  .social-icons a { color: var(--muted); }
  .social-icons a:hover { color: var(--brand); }
  .social-icons svg { width: 24px; height: 24px; }

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

  /* Animations */
  @-webkit-keyframes moveFromTopFade { from { opacity: .3; height:0px; margin-top:0px; -webkit-transform: translateY(-100%); } }
  @-moz-keyframes moveFromTopFade { from { height:0px; margin-top:0px; -moz-transform: translateY(-100%); } }
  @keyframes moveFromTopFade { from { height:0px; margin-top:0px; transform: translateY(-100%); } }

  @-webkit-keyframes moveToTopFade { to { opacity: .3; height:0px; margin-top:0px; opacity: 0.3; -webkit-transform: translateY(-100%); } }
  @-moz-keyframes moveToTopFade { to { height:0px; -moz-transform: translateY(-100%); } }
  @keyframes moveToTopFade { to { height:0px; transform: translateY(-100%); } }

  @-webkit-keyframes moveToTopFadeMonth { to { opacity: 0; -webkit-transform: translateY(-30%) scale(.95); } }
  @-moz-keyframes moveToTopFadeMonth { to { opacity: 0; -moz-transform: translateY(-30%); } }
  @keyframes moveToTopFadeMonth { to { opacity: 0; -moz-transform: translateY(-30%); } }

  @-webkit-keyframes moveFromTopFadeMonth { from { opacity: 0; -webkit-transform: translateY(30%) scale(.95); } }
  @-moz-keyframes moveFromTopFadeMonth { from { opacity: 0; -moz-transform: translateY(30%); } }
  @keyframes moveFromTopFadeMonth { from { opacity: 0; -moz-transform: translateY(30%); } }

  @-webkit-keyframes moveToBottomFadeMonth { to { opacity: 0; -webkit-transform: translateY(30%) scale(.95); } }
  @-moz-keyframes moveToBottomFadeMonth { to { opacity: 0; -webkit-transform: translateY(30%); } }
  @keyframes moveToBottomFadeMonth { to { opacity: 0; -webkit-transform: translateY(30%); } }

  @-webkit-keyframes moveFromBottomFadeMonth { from { opacity: 0; -webkit-transform: translateY(-30%) scale(.95); } }
  @-moz-keyframes moveFromBottomFadeMonth { from { opacity: 0; -webkit-transform: translateY(-30%); } }
  @keyframes moveFromBottomFadeMonth { from { opacity: 0; -webkit-transform: translateY(-30%); } }

  @-webkit-keyframes fadeIn  { from { opacity: 0; } }
  @-moz-keyframes fadeIn  { from { opacity: 0; } }
  @keyframes fadeIn  { from { opacity: 0; } }

  @-webkit-keyframes fadeOut  { to { opacity: 0; } }
  @-moz-keyframes fadeOut  { to { opacity: 0; } }
  @keyframes fadeOut  { to { opacity: 0; } }

  @-webkit-keyframes fadeOutShink  { to { opacity: 0; padding: 0px; height: 0px; } }
  @-moz-keyframes fadeOutShink  { to { opacity: 0; padding: 0px; height: 0px; } }
  @keyframes fadeOutShink  { to { opacity: 0; padding: 0px; height: 0px; } }
</style>

<header class="nav" role="banner">
  <div class="container nav-inner">
    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'events' ) ) ); ?>">
        <img src="<?php echo plugins_url( '../assets/images/logo.png', __FILE__ ); ?>" alt="Logo" class="site-logo">
    </a>
    <nav class="nav-links" role="navigation" aria-label="Primary">
      <?php
        echo '<div class="nav-links-main">';
        // Use the new dynamically generated navigation items
        if (!empty($dynamic_nav_items)) {
            foreach ($dynamic_nav_items as $nav_item) {
                $href = esc_attr($nav_item['href']);
                $text = esc_html($nav_item['text']);
                echo "<a href=\"{$href}\">{$text}</a>";
            }
        }
        echo '</div>';
      ?>
      <div class="nav-links-secondary">
        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'events' ) ) ); ?>">View All Events</a>
        <a href="<?php echo esc_url($registration_link); ?>" target="_blank" rel="noopener" class="btn btn-primary">Register</a>
      </div>
    </nav>
  </div>
</header>
<!-- /wp:html -->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

<!-- wp:group {"tagName":"section","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull hero" aria-labelledby="hero-title">
    <!-- wp:html -->
    <div class="hero-bg" aria-hidden="true"></div>
    <!-- /wp:html -->

    <!-- wp:group {"className":"container hero-inner","layout":{"type":"constrained"}} -->
    <div class="wp-block-group container hero-inner">
        <!-- wp:paragraph {"className":"kicker"} -->
        <p class="kicker"><?php echo esc_html($event_date_string); ?></p>
        <!-- /wp:paragraph -->

        <!-- wp:heading {"level":1,"anchor":"hero-title"} -->
        <h1 id="hero-title"><?php echo esc_html($event_title_string); ?></h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"className":"lead"} -->
        <p class="lead">
            <?php echo esc_html( $event_lead_text ?: 'A short description of the event will appear here.' ); ?>
        </p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"className":"hero-ctas"} -->
        <div class="wp-block-buttons hero-ctas">
            <!-- wp:button {"className":"btn btn-primary","url":"<?php echo esc_url($registration_link); ?>","target":"_blank","rel":"noopener"} -->
            <div class="wp-block-button btn btn-primary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($registration_link); ?>" target="_blank" rel="noopener">Get Tickets</a></div>
            <!-- /wp:button -->

            <?php if (!empty($cfs_button_link)): ?>
                <!-- wp:button {"className":"btn btn-ghost","url":"<?php echo esc_url($cfs_button_link); ?>","target":"_blank","rel":"noopener"} -->
                <div class="wp-block-button btn btn-ghost"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($cfs_button_link); ?>" target="_blank" rel="noopener"><?php echo esc_html($cfs_button_text); ?></a></div>
                <!-- /wp:button -->
            <?php endif; ?>
        </div>
        <!-- /wp:buttons -->

        <!-- wp:image {"align":"center","sizeSlug":"full","linkDestination":"none","style":{"spacing":{"margin":{"top":"32px"}}}} -->
        <figure class="wp-block-image aligncenter size-full" style="margin-top:32px; display: flex; justify-content: center;">
            <img src="<?php echo esc_url($hero_image_url); ?>" alt="<?php echo esc_attr($event_title_string); ?> hero image" style="width: 100%; height: auto; border-radius: 12px; box-shadow: var(--shadow);"/>
        </figure>
        <!-- /wp:image -->
    </div>
    <!-- /wp:group -->
</section>
<!-- /wp:group -->
<?php render_custom_sections('after_hero', $custom_sections_data); ?>

<?php if ( ($section_visibility['about'] ?? true) ) : ?>
<section id="about" aria-labelledby="about-title" class="wp-block-group" style="padding: 48px 0;">
    <!-- wp:group {"className":"container reveal","layout":{"type":"constrained"}} -->
    <div class="wp-block-group container reveal" style="max-width: var(--container); margin: 0 auto; padding: 0 24px;">
        <div class="wp-block-group about">
            <?php echo wp_kses_post($site_settings_data['about_section_content'] ?? '<p>About content has not been set for this event.</p>'); ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php render_custom_sections('after_about', $custom_sections_data); ?>

<?php if ( ($section_visibility['speakers'] ?? true) ) : ?>
<section id="speakers" class="wp-block-group" style="padding: 48px 0;">
    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"20px","bottom":"40px","left":"20px"}}},"className":"container","layout":{"type":"constrained","wideSize":"1200px"}} -->
    <div class="wp-block-group has-global-padding-is-contained container" style="padding-top:40px;padding-right:20px;padding-bottom:40px;padding-left:20px;max-width:1200px;margin-left:auto;margin-right:auto;">
        <!-- wp:group {"className":"section-head","layout":{"type":"default"},"style":{"spacing":{"margin":{"bottom":"30px"}}}} -->
        <div class="wp-block-group section-head" style="margin-bottom:30px; text-align: left;">
            <!-- wp:heading {"level":2,"className":"h2","style":{"typography":{"fontSize":"28px","margin":{"bottom":"10px"}}}} -->
            <h2 class="h2" style="font-size:28px;margin-bottom:10px; text-align: left;">Featured Speakers</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"className":"meta","style":{"color":{"text":"#5b636a"}}} -->
            <p class="meta" style="color:#5b636a;">Curated invited & selected speakers</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:columns {"className":"grid grid-4"} -->
        <div id="speaker-grid" class="wp-block-columns grid grid-4" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:18px;align-items:start;">
            <!-- Speaker cards will be dynamically inserted here -->
        </div>
        <!-- /wp:columns -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"margin":{"top":"40px"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
    <div class="wp-block-group" style="margin-top:40px;">
        <!-- wp:buttons {"align":"center","layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons aligncenter" style="display:flex; justify-content:center; width:100%;">
            <!-- wp:button {"className":"btn","id":"seeMoreBtn","style":{"backgroundColor":"#D51007","textColor":"#fff","padding":{"top":"12px","right":"32px","bottom":"12px","left":"32px"},"borderRadius":"6px","fontSize":"16px","fontWeight":"bold"}}} -->
            <?php
                $speakers_page_url = get_permalink( get_page_by_path( FOSSASIA_Landing_Plugin::SPEAKERS_PAGE_SLUG ) );
                $speakers_page_url_with_id = add_query_arg( 'event_id', $post_id, $speakers_page_url );
            ?>
            <div class="wp-block-button btn" id="seeMoreBtn">
                <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $speakers_page_url_with_id ); ?>" style="background-color:var(--brand);color:#fff;border-radius:6px;font-size:16px;font-weight:bold;padding-top:12px;padding-right:32px;padding-bottom:12px;padding-left:32px; margin-left: auto; margin-right: auto;">More Speakers</a>
            </div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</section>
<?php endif; ?>
<?php render_custom_sections('after_speakers', $custom_sections_data); ?>

<?php if ( ($section_visibility['schedule'] ?? true) ) : ?>
<section id="schedule-overview" class="wp-block-group" style="padding: 48px 0;">
    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"20px","bottom":"40px","left":"20px"}}},"className":"container","layout":{"type":"constrained","wideSize":"1200px"}} -->
    <div class="wp-block-group has-global-padding-is-contained container" style="padding-top:40px;padding-right:20px;padding-bottom:40px;padding-left:20px;max-width:1200px;margin-left:auto;margin-right:auto;">
        <!-- wp:group {"className":"section-head","layout":{"type":"default"},"style":{"spacing":{"margin":{"bottom":"30px"}}}} -->
        <div class="wp-block-group section-head" style="margin-bottom:30px; text-align: left;">
            <!-- wp:heading {"level":2,"className":"h2","style":{"typography":{"fontSize":"28px","margin":{"bottom":"10px"}}}} -->
            <h2 class="h2" style="font-size:28px;margin-bottom:10px; text-align: left;">Schedule Overview</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"className":"meta","style":{"color":{"text":"#5b636a"}}} -->
            <p class="meta" style="color:#5b636a;">Event timeline and sessions</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:html -->
        <?php
            $schedule_file = $upload_dir['basedir'] . '/fossasia-data/schedule-' . $post_id . '.json';
            if (file_exists($schedule_file)) {
                $schedule_table_data = json_decode(file_get_contents($schedule_file), true);

                if (!empty($schedule_table_data) && isset($schedule_table_data['name'])) {
                    $table = $schedule_table_data;
                    echo '<div class="panel" style="padding:0; overflow:hidden; border: 1px solid var(--brand); border-radius: 12px;">'; // Wrapper to match theme style
                    echo '<h3 class="h3" style="margin:0; padding: 15px 20px; background: #f8f9fa; color: var(--text); border-bottom: 1px solid #dee2e6;">' . esc_html($table['name']) . '</h3>';
                    echo '<div style="overflow-x:auto;">';
                    echo '<table class="schedule-table" style="margin-top:0; border: none;">';
                    
                    $is_first_row = true;
                    foreach ($table['data'] as $row_data) {
                        echo '<tr>';
                        foreach ($row_data as $cell_content) {
                            $cell_html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', esc_html($cell_content));
                            $cell_html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $cell_html);
                            $cell_html = nl2br($cell_html);
                            $tag = $is_first_row ? 'th' : 'td';
                            echo "<{$tag}>" . wp_kses_post($cell_html) . "</{$tag}>";
                        }
                        echo '</tr>';
                        $is_first_row = false;
                    }
                    echo '</table></div></div>';
                }
            }
        ?>
        <!-- /wp:html -->

        <!-- wp:buttons {"align":"center","layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"20px"}}}} -->
        <div class="wp-block-buttons aligncenter" style="margin-top:20px;">
            <!-- wp:button {"className":"btn","style":{"backgroundColor":"#D51007","textColor":"#fff","padding":{"top":"12px","right":"32px","bottom":"12px","left":"32px"},"borderRadius":"6px","fontSize":"16px","fontWeight":"bold"}}} -->
            <?php
                $schedule_page_url = get_permalink( get_page_by_path( FOSSASIA_Landing_Plugin::SCHEDULE_PAGE_SLUG ) );
                $schedule_page_url_with_id = add_query_arg( 'event_id', $post_id, $schedule_page_url );
            ?>
            <div class="wp-block-button btn">
                <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $schedule_page_url_with_id ); ?>" style="background-color:var(--brand);color:#fff;border-radius:6px;font-size:16px;font-weight:bold;padding-top:12px;padding-right:32px;padding-bottom:12px;padding-left:32px; margin-left: auto; margin-right: auto;">View Full Schedule</a>
            </div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->

    </div>
    <!-- /wp:group -->
</section>
<!-- /wp-group -->
<?php endif; ?>
<?php render_custom_sections('after_schedule', $custom_sections_data); ?>

<?php if ( ($section_visibility['sponsors'] ?? true) ) : ?>
<section id="sponsors" class="wp-block-group" style="padding: 48px 0;">
    <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"20px","bottom":"40px","left":"20px"}}},"className":"container","layout":{"type":"constrained","wideSize":"1200px"}} -->
    <div class="wp-block-group has-global-padding-is-contained container" style="padding-top:40px;padding-right:20px;padding-bottom:40px;padding-left:20px;max-width:1200px;margin-left:auto;margin-right:auto;">
        <!-- wp:group {"className":"section-head","layout":{"type":"default"},"style":{"spacing":{"margin":{"bottom":"30px"}}}} -->
        <div class="wp-block-group section-head" style="margin-bottom:30px; text-align: left;">
            <!-- wp:heading {"level":2,"className":"h2","style":{"typography":{"fontSize":"28px","margin":{"bottom":"10px"}}}} -->
            <h2 class="h2" style="font-size:28px;margin-bottom:10px; text-align: left;">Sponsors</h2>
            <!-- /wp:heading -->
            <!-- wp:paragraph {"className":"meta","style":{"color":{"text":"#5b636a"}}} -->
            <p class="meta" style="color:#5b636a;">Our valued partners and supporters</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:html -->
        <?php
        if ( ! empty( $sponsors_data ) && is_array( $sponsors_data ) ) {
            foreach ( $sponsors_data as $group ) {
                if ( empty( $group['sponsors'] ) ) continue;
                $group_classes = 'wp-block-group sponsors';
                if (!empty($group['centered'])) { $group_classes .= ' sponsors-centered'; }
                $logo_size = !empty($group['logo_size']) ? absint($group['logo_size']) : 160;
                echo '<h3 class="h3">' . esc_html( $group['group_name'] ) . '</h3>';
                echo '<div class="' . esc_attr($group_classes) . '">';
                foreach ( $group['sponsors'] as $sponsor ) {
                    ?>
                    <figure class="wp-block-image size-full is-resized slogo reveal">
                        <a href="<?php echo esc_url( $sponsor['link'] ); ?>" target="_blank" rel="noopener">
                            <?php
                                $image_src = $sponsor['image'];
                                if (strpos(trim($image_src), 'data:image') !== 0) {
                                    $image_src = esc_url($image_src);
                                }
                            ?>
                            <img src="<?php echo $image_src; ?>" alt="<?php echo esc_attr( $sponsor['name'] ); ?> Logo" width="<?php echo $logo_size; ?>" height="auto" style="object-fit:contain; max-width: <?php echo $logo_size; ?>px;"/>
                        </a>
                        <figcaption class="wp-element-caption"><?php echo esc_html( $sponsor['name'] ); ?></figcaption>
                    </figure>
                    <?php
                }
                echo '</div>';
            }
        }
        ?>
        <!-- /wp:html -->
    </div>
</section>
<?php endif; ?>
<?php render_custom_sections('after_sponsors', $custom_sections_data); ?>
<?php render_custom_sections('after_venue', $custom_sections_data); ?>

<!-- wp:group {"tagName":"section","className":"cta","layout":{"type":"constrained"},"metadata":{"name":"Call to Action"}} -->
<section class="wp-block-group cta">
    <!-- wp:group {"className":"container","layout":{"type":"constrained"}} -->
    <div class="wp-block-group container">
        <!-- wp:group {"className":"cta-inner","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
        <div class="wp-block-group cta-inner">
            <!-- wp:group {"className":"cta-box reveal","style":{"spacing":{"padding":{"top":"26px","right":"26px","bottom":"26px","left":"26px"},"margin":{"top":"0","bottom":"0"}},"backgroundColor":"var(--text)","textColor":"#fff","borderRadius":"14px"},"layout":{"type":"constrained"},"minWidth":"260px"} -->
            <div class="wp-block-group cta-box reveal" style="min-width:260px;background-color:var(--text);color:#fff;border-radius:14px;padding-top:26px;padding-right:26px;padding-bottom:26px;padding-left:26px;margin-top:0;margin-bottom:0;">
                <!-- wp:heading {"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"0.4rem"}}}} -->
                <h3 style="margin-top:0;margin-bottom:0.4rem;">Join the open-source future</h3>
                <!-- /wp:heading -->
                <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","bottom":"12px"}},"color":{"text":"#f3f3f5"}}} -->
                <p style="margin-top:0;margin-bottom:12px;color:#f3f3f5;">Register now and submit a talk — help shape the program.</p>
                <!-- /wp:paragraph -->
                <!-- wp:buttons {"style":{"spacing":{"blockGap":"0.6rem"}}} -->
                <div class="wp-block-buttons" style="gap:0.6rem;">
                    <!-- wp:button {"className":"btn btn-primary","url":"<?php echo esc_url($registration_link); ?>","target":"_blank","rel":"noopener"} --> <div class="wp-block-button btn btn-primary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($registration_link); ?>" target="_blank" rel="noopener">Register</a></div> <!-- /wp:button -->
                    <?php if (!empty($cfs_button_link)): ?>
                        <!-- wp:button {"className":"btn btn-ghost","url":"<?php echo esc_url($cfs_button_link); ?>","target":"_blank","rel":"noopener"} -->
                        <div class="wp-block-button btn btn-ghost"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($cfs_button_link); ?>" target="_blank" rel="noopener"><?php echo esc_html($cfs_button_text); ?></a></div>
                        <!-- /wp:button -->
                    <?php endif; ?>
                </div>
                <!-- /wp:buttons -->
            </div>
            <!-- /wp:group -->
            <!-- wp:group {"className":"reveal","style":{"flexGrow":1,"minWidth":"220px"},"layout":{"type":"constrained","justifyContent":"right"}} -->
            <div class="wp-block-group reveal" style="flex-grow:1;min-width:220px;">
                <!-- wp:html -->
                <small class="meta">Follow updates on <a href="https://twitter.com/fossasia" target="_blank" rel="noopener">@fossasia</a></small>
                <div class="social-icons">
                  <a href="https://github.com/fossasia" target="_blank" rel="noopener" title="GitHub"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg></a>
                  <a href="https://www.facebook.com/fossasia" target="_blank" rel="noopener" title="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg></a>
                  <a href="https://www.flickr.com/photos/fossasia/" target="_blank" rel="noopener" title="Flickr"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.5 7.5c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zm11 0c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5z"/></svg></a>
                  <a href="https://floss.social/@fossasia" target="_blank" rel="noopener" title="FLOSS Social (Mastodon)"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.258 13.412c.24-1.13.382-2.294.382-3.473C21.64 4.44 18.398 1.2 14.334 1.2c-2.35 0-4.445 1.12-5.83 2.89-1.053.223-2.03.58-2.92.99-.07-.11-.14-.22-.22-.33-.8-.9-1.9-1.4-3.1-1.4-.5 0-1 .1-1.4.4-.5.2-1 .7-1.1 1.3-.1.4-.1.9 0 1.3.3.9 1.2 1.4 2.2 1.4.8 0 1.6-.4 2.1-.9.3.4.6.8.9 1.2.5 1.1.8 2.3.9 3.5.1 1.2 0 2.5-.2 3.7-.3 1.5-1 2.9-1.9 4.1-1.5 1.9-3.5 3-5.8 3.1h-.2c-.4 0-.7-.2-.8-.5-.2-.3-.1-.7.1-1 .1-.1.2-.2.3-.3.4-.3.8-.6 1.1-.9.6-.7 1.1-1.5 1.5-2.3.4-.8.6-1.7.7-2.6.1-.9.1-1.8.1-2.8 0-.5 0-1-.1-1.5-.1-.6-.2-1.2-.4-1.8-.1-.4-.2-.7-.4-1.1-.3-.4-.6-.8-1-1.2-.1-.1-.2-.2-.3-.3-.3-.4-.7-.7-1.1-.9-.3-.1-.6-.2-.9-.2-.8 0-1.5.4-1.9.9-.3.4-.5.9-.5 1.4 0 .5.1.9.3 1.3.1.1.2.2.3.3.7.8 1.8 1.3 2.9 1.3.3 0 .6-.1.9-.2.3-.1.6-.3.9-.5.4-.3.7-.6 1.1-.9.2.3.4.6.6.9.5.8 1.1 1.6 1.8 2.3.7.7 1.5 1.4 2.3 2 .8.6 1.7 1.1 2.6 1.5.9.4 1.9.6 2.8.7h.3c.4 0 .8-.1 1.1-.3.4-.2.7-.5.9-.8.2-.4.3-.8.3-1.2 0-.4-.1-.8-.3-1.1-.2-.3-.5-.6-.8-.8-.6-.4-1.3-.7-2-1-.7-.3-1.4-.5-2.1-.6-.8-.1-1.6-.2-2.4-.2-.8 0-1.6.1-2.3.2-.8.1-1.6.3-2.3.5-.6.2-1.2.4-1.8.6-.5.2-1 .4-1.5.6-.1 0-.2.1-.3.1-.2.1-.4.1-.6.1h-.1c-.3 0-.5-.1-.7-.2-.2-.1-.4-.3-.5-.5-.1-.2-.2-.4-.2-.6s.1-.4.2-.6c.1-.2.3-.3.5-.4.2-.1.4-.2.6-.2.2 0 .4-.1.6-.1.2-.1.4-.1.6-.2.5-.2 1-.4 1.5-.6.6-.2 1.2-.4 1.8-.6.7-.2 1.5-.3 2.3-.5.8-.1 1.5-.2 2.3-.2.8 0 1.6.1 2.4.2.7.1 1.4.3 2.1.6.7.3 1.4.6 2 .9.3.2.6.5.8.8.2.3.3.6.3 1 .1.4 0 .8-.1 1.2-.2.4-.4.7-.7.9-.3.2-.7.4-1.1.4h-.3c-1.3-.1-2.6-.5-3.8-1.2-1.2-.7-2.3-1.7-3.1-2.9-.1-.2-.3-.4-.4-.6-.1-.2-.2-.4-.3-.6-.1-.2-.2-.4-.3-.6-.1-.2-.2-.4-.2-.6s0-.4.1-.6c.1-.2.1-.4.2-.6.1-.2.2-.4.3-.6.2-.2.3-.4.5-.6.2-.2.4-.4.6-.6.2-.2.4-.4.7-.6.2-.2.5-.4.7-.5.3-.2.5-.3.8-.4.3-.1.6-.2.9-.2.9 0 1.8.2 2.6.6.8.4 1.5.9 2.1 1.6.6.7 1.1 1.5 1.4 2.4.3.9.5 1.8.5 2.8z"/></svg></a>
                  <a href="https://www.linkedin.com/company/fossasia" target="_blank" rel="noopener" title="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg></a>
                  <a href="https://www.youtube.com/c/fossasia" target="_blank" rel="noopener" title="YouTube"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg></a>
                  <a href="https://x.com/fossasia" target="_blank" rel="noopener" title="X (Twitter)"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.62l-5.21-6.817-6.022 6.817h-3.308l7.748-8.786-8.3-10.714h6.78l4.596 6.145 5.45-6.145zm-2.46 17.63h1.89l-9.48-12.605h-1.93l9.52 12.605z"/></svg></a>
                  <a href="https://fossasia.org" target="_blank" rel="noopener" title="Website"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg></a>
                </div>
                <!-- /wp:html -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- Edit Speaker Modal -->
<div id="editSpeakerModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="editSpeakerForm">
            <!-- Form fields will be populated by JS -->
        </form>
    </div>
</div>

<!-- wp:html -->
<a href="#" id="back-to-top" class="back-to-top-btn" title="Back to top">^</a>
<!-- /wp:html -->

<!-- wp:group {"tagName":"footer","className":"footer","layout":{"type":"constrained"}} -->
<footer class="wp-block-group footer">
    <!-- wp:paragraph {"tagName":"small","align":"center","className":"container center"} -->
    <small class="has-text-align-center container center">
        <?php echo esc_html( $site_settings_data['footer_text'] ?? '© FOSSASIA' ); ?>
    </small>
    <!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->

<!-- wp:html -->
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.5.1/moment.min.js"></script>
<script>
  // This script block is for existing functionality.
  // New calendar scripts will be added below.

  // Reveal on scroll
  (function(){
    const io = new IntersectionObserver((entries)=> {
      entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible')});
    }, {threshold:.12});
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  })();

  // Speaker card expand/collapse
  (function(){
    document.querySelectorAll('.card').forEach(card => {
      card.querySelector('.card-body').addEventListener('click', () => {
        card.classList.toggle('expanded');
      });
    });
  })();

  // Speaker link scroll
  (function(){
    function highlightSpeakerEvent(speaker) {
      if (!speaker) return;
      // Find the event block for this speaker
      const eventBlock = document.querySelector(`.event-block[data-speaker="${speaker}"]`);
      if (eventBlock) {
        const dayBox = eventBlock.closest('.panel');
        if (dayBox) {
          const dayId = dayBox.id;
          const tab = document.querySelector(`.tab[data-day="${dayId}"]`);

          if (tab && !tab.classList.contains('active')) {
            tab.click();
          }

          const schedule = document.querySelector('#schedule-overview');
          if (schedule) {
            schedule.scrollIntoView({ behavior: 'smooth' });
          }

          setTimeout(() => {
            // Scroll the specific event row into view and highlight it
            eventBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
            eventBlock.classList.add('highlight');
            setTimeout(() => eventBlock.classList.remove('highlight'), 2000); // Highlight for 2 seconds
          }, 800);
        }
      }
    }

    // Handle clicks on speaker links using event delegation for robustness
    document.body.addEventListener('click', function(e) {
      const link = e.target.closest('.speaker-link');
      if (!link) return; // Exit if the click wasn't on a speaker link

      const href = link.getAttribute('href');
      // Only act on on-page links to the schedule/calendar
      if (href && (href.includes('#schedule-overview') || href.includes('#event-calendar'))) {
        e.preventDefault();
        const speaker = link.dataset.speaker;
        highlightSpeakerEvent(speaker);
      }
    });

    // Check for speaker in URL on page load
    const urlParams = new URLSearchParams(window.location.search);
    const speakerFromUrl = urlParams.get('speaker');
    if (speakerFromUrl) { setTimeout(() => { highlightSpeakerEvent(speakerFromUrl); }, 500); }
  })();

  // Back to top button
  (function() {
    const backToTopButton = document.getElementById('back-to-top');
    if (!backToTopButton) return;

    window.onscroll = function() {
      if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
        backToTopButton.style.display = "block";
      } else {
        backToTopButton.style.display = "none";
      }
    };

    backToTopButton.addEventListener('click', function(e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  })();

  // Featured Speakers Rendering
  (function() {
    const isAdmin = <?php echo current_user_can( 'manage_options' ) ? 'true' : 'false'; ?>;
    const ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
    let speakersData = <?php echo json_encode($speakers_data); ?>;
    const grid = document.getElementById('speaker-grid');
    if (!grid) return;

    function truncate(str, num) {
        if (!str) return '';
        if (str.length <= num) return str;
        return str.slice(0, num) + '...';
    }

    function renderScheduleOverview(speakers) {
      if (!speakers || !Array.isArray(speakers)) return;

      // 1. Get all unique dates from sessions and sort them
      const allDates = new Set();
      speakers.forEach(speaker => {
          if (speaker.sessions && Array.isArray(speaker.sessions)) {
              speaker.sessions.forEach(session => {
                  if (session.date) allDates.add(session.date);
              });
          }
      });
      const sortedDates = Array.from(allDates).sort();

      // 2. Create a dynamic mapping from date to dayId (day1, day2, day3)
      const dateToDayIdMap = {};
      const dayIds = ['day1', 'day2', 'day3'];
      sortedDates.slice(0, 3).forEach((date, index) => {
          dateToDayIdMap[date] = dayIds[index];
      });

      // 3. Initialize and populate sessionsByDay using the dynamic map
      const sessionsByDay = { day1: [], day2: [], day3: [] };
      speakers.forEach(speaker => {
          if (speaker.sessions && Array.isArray(speaker.sessions)) {
              speaker.sessions.forEach(session => {
                  if (session.date && session.time && session.title) {
                      const dayId = dateToDayIdMap[session.date];
                      if (dayId) {
                          sessionsByDay[dayId].push({
                              date: session.date, time: session.time, title: session.title, track: speaker.category,
                              speaker: speaker.name, speakerId: speaker.id
                          });
                      }
                  }
              });
          }
      });

      // 4. Update Tab UI and render tables
      const tabsContainer = document.querySelector('.tabs[role="tablist"]');
      let hasAnySessions = false;

      dayIds.forEach((dayId, index) => {
          const tab = tabsContainer.querySelector(`[data-day="${dayId}"]`);
          const tableBody = document.querySelector(`#${dayId} table tbody`);
          const panel = document.getElementById(dayId);
          const dateForDay = sortedDates[index];

          if (dateForDay && sessionsByDay[dayId].length > 0) {
              hasAnySessions = true;
              tab.style.display = '';
              tab.innerHTML = `Day ${index + 1} <small>(${moment(dateForDay).format('MMM D')})</small>`;
              
              const daySessions = sessionsByDay[dayId].sort((a, b) => a.time.localeCompare(b.time));
              if (tableBody) {
                  tableBody.innerHTML = daySessions.map(session => `
                      <tr class="event-block" data-speaker="${session.speakerId}">
                          <td>${moment(session.date).format('MMM D, YYYY')}</td>
                          <td>${session.time}</td><td>${session.title}</td>
                          <td><span class="badge">${session.track}</span></td>
                          <td><a href="#speakers" class="speaker-link" data-speaker="${session.speakerId}">${session.speaker}</a></td>
                      </tr>`).join('');
              }
          } else {
              tab.style.display = 'none';
              if (tableBody) tableBody.innerHTML = '';
              if (panel) panel.style.display = 'none';
              if (tab) tab.classList.remove('active');
          }
      });

      // 5. Final UI adjustments for tabs and panels
      if (hasAnySessions) {
          const firstVisibleTab = tabsContainer.querySelector('.tab:not([style*="display: none"])');
          if (firstVisibleTab) {
              firstVisibleTab.classList.add('active');
              const panelId = firstVisibleTab.dataset.day;
              document.getElementById(panelId).style.display = 'block';
          }
      } else {
          const firstTab = tabsContainer.querySelector('[data-day="day1"]');
          const firstPanel = document.getElementById('day1');
          const firstTableBody = document.querySelector('#day1 table tbody');
          if (firstTab) {
              firstTab.style.display = '';
              firstTab.innerHTML = 'Day 1';
              firstTab.classList.add('active');
          }
          if (firstPanel) firstPanel.style.display = 'block';
          if (firstTableBody) firstTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Schedule will be announced soon.</td></tr>';
      }
    }

    function renderFeaturedSpeakers(searchTerm = '') {
        // Filter for featured speakers and sort them by the new order property.
        let featuredSpeakers = speakersData.filter(s => s.featured).sort((a, b) => (a.featured_order || 0) - (b.featured_order || 0));

        if (searchTerm) {
            featuredSpeakers = featuredSpeakers.filter(speaker => 
                (speaker.name && speaker.name.toLowerCase().includes(searchTerm)) ||
                (speaker.title && speaker.title.toLowerCase().includes(searchTerm)) ||
                (speaker.category && speaker.category.toLowerCase().includes(searchTerm))
            );
        }
        
        grid.innerHTML = featuredSpeakers.map(speaker => {
            const editButtonHTML = isAdmin ? `<button class="btn-edit-speaker" data-id="${speaker.id}" title="Edit Speaker">✎</button>` : '';
            const imageSrc = speaker.image;
            // The image source is now trusted, whether it's a URL or a data URI.
            // No extra escaping is needed for the src attribute itself if the content is controlled.
            // We still escape alt tags and other content.
            const escapedName = escapeHTML(speaker.name);
 
            const socialLinks = [
                speaker.social?.linkedin ? `<a href="${speaker.social.linkedin}" target="_blank" rel="noopener" style="margin-right:10px;text-decoration:none;font-size:14px;display:inline-block;">🔗 LinkedIn</a>` : '',
                speaker.social?.twitter ? `<a href="${speaker.social.twitter}" target="_blank" rel="noopener" style="margin-right:10px;text-decoration:none;font-size:14px;display:inline-block;">🐦 Twitter</a>` : '',
                speaker.social?.github ? `<a href="${speaker.social.github}" target="_blank" rel="noopener" style="margin-right:10px;text-decoration:none;font-size:14px;display:inline-block;">💻 GitHub</a>` : '',
                speaker.social?.website ? `<a href="${speaker.social.website}" target="_blank" rel="noopener" style="margin-right:10px;text-decoration:none;font-size:14px;display:inline-block;">🌐 Website</a>` : ''
            ].filter(Boolean).join(' ');

            let sessionsHTML = 'Session details to be announced.';
            if (speaker.sessions) {
                if (Array.isArray(speaker.sessions) && speaker.sessions.length > 0) {
                    sessionsHTML = speaker.sessions.map(s => `<strong>${s.title || ''}</strong> on ${moment(s.date).format('MMM D')} at ${s.time || ''}`).join('<br>');
                } else if (typeof speaker.sessions === 'string') {
                    sessionsHTML = speaker.sessions; // For backward compatibility
                }
            }

            return `
            <div class="wp-block-column card reveal">
                <figure class="wp-block-image size-full card-media" role="img" aria-label="${escapedName} — Speaker photo">
                    <img src="${imageSrc}" alt="${escapedName}" style="width:100%; height:100%; object-fit:cover;">
                </figure>
                <div class="wp-block-group card-body">
                    ${editButtonHTML}
                    <p class="pill" style="background-color:var(--brand);color:#fff;border-radius:12px;font-size:12px;padding-top:2px;padding-right:8px;padding-bottom:2px;padding-left:8px;font-weight:bold;">${escapeHTML(speaker.category)}</p>
                    <h3 style="margin-top:0.5rem;margin-bottom:0.25rem;font-size:20px;">${escapedName}</h3>
                    <p class="meta" style="color:#5b636a;font-size:14px;">${escapeHTML(speaker.title)}</p>
                </div>
                <div class="wp-block-group card-expand">
                    <p style="margin-top:15px;margin-bottom:15px;font-size:14px;line-height:1.6;">${escapeHTML(speaker.bio)}</p>
                    <p style="margin-top:10px;margin-bottom:10px;font-size:14px;line-height:1.4;">${socialLinks}</p> <!-- socialLinks is already escaped -->
                    <a href="#event-calendar" class="speaker-link" data-speaker="${escapeHTML(speaker.id)}"><div class="session-info" style="background-color: var(--brand); color: #fff; border-radius: 6px; padding: 12px; margin: 15px 0; font-size: 14px; line-height: 1.5;"><p><strong>Sessions:</strong><br>${sessionsHTML}</p></div></a> <!-- sessionsHTML is already escaped -->
                </div>
            </div>`;
        }).join('');

        // Re-initialize expand/collapse for new cards
        grid.addEventListener('click', (e) => {
            // Handle edit button click
            if (e.target.matches('.btn-edit-speaker')) {
                e.stopPropagation(); // Prevent card from expanding
                const speakerId = e.target.dataset.id;
                if (isAdmin) openEditModal(speakerId);
                return;
            }
            // Handle card expand/collapse on card-body click
            const cardBody = e.target.closest('.card-body');
            if (cardBody) {
                cardBody.closest('.card').classList.toggle('expanded');
            }
        });

        // Re-initialize reveal on scroll for new cards
        const io = new IntersectionObserver((entries)=> {
            entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible')});
        }, {threshold:.12});
        document.querySelectorAll('#speaker-grid .reveal').forEach(el => io.observe(el));
    }

    function renderAll() {
        renderFeaturedSpeakers();
        renderScheduleOverview(speakersData);
    }

    // Admin Edit Functionality
    if (isAdmin) {
        const editSpeakerModal = document.getElementById('editSpeakerModal');
        const adminNonce = '<?php echo wp_create_nonce("fossasia_admin_nonce"); ?>';
        if (!editSpeakerModal) return;
        const editSpeakerForm = document.getElementById('editSpeakerForm');
        const closeEditModalBtn = editSpeakerModal.querySelector('.close-btn');
        let currentlyEditingId = null;

        function escapeHTML(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function openEditModal(speakerId) {
            const speaker = speakersData.find(s => s.id === speakerId);
            if (!speaker) return;
            currentlyEditingId = speakerId;

            const session = speaker.sessions && Array.isArray(speaker.sessions) && speaker.sessions[0] ? speaker.sessions[0] : { title: '', date: '', time: '', end_time: '' };

            editSpeakerForm.innerHTML = `
                <h2>Edit Speaker</h2>
                <label for="editSpeakerName">Name:</label>
                <input type="text" id="editSpeakerName" name="name" value="${escapeHTML(speaker.name)}" required>
                <label for="editSpeakerTitle">Title:</label>
                <input type="text" id="editSpeakerTitle" name="title" value="${escapeHTML(speaker.title)}" required>
                <label for="editSpeakerCategory">Category:</label>
                <input type="text" id="editSpeakerCategory" name="category" value="${escapeHTML(speaker.category)}" required>
                <label>Image:</label>
                <div class="image-option-toggle">
                    <label><input type="radio" name="image_source" value="url" checked> URL</label>
                    <label><input type="radio" name="image_source" value="upload"> Upload</label>
                </div>
                <input type="url" name="image_url" placeholder="Enter image URL" value="${!speaker.image.startsWith('data:image') ? escapeHTML(speaker.image) : ''}" required>
                <input type="file" name="image_upload" accept="image/*" style="display:none;">
                <label for="editSpeakerBio">Bio:</label>
                <textarea id="editSpeakerBio" name="bio" rows="4" required>${escapeHTML(speaker.bio)}</textarea>
                <p><strong>Social Links (Optional)</strong></p>
                <label for="editTalkTitle">Talk Title:</label>
                <input type="text" id="editTalkTitle" name="talkTitle" value="${escapeHTML(session?.title || '')}" required>
                <label for="editTalkDate">Date:</label>
                <input type="date" id="editTalkDate" name="talkDate" value="${escapeHTML(session?.date || '')}" required>
                <label for="editTalkTime">Start Time:</label>
                <input type="time" id="editTalkTime" name="talkTime" value="${escapeHTML(session?.time || '')}" required>
                <label for="editTalkEndTime">End Time:</label>
                <input type="time" id="editTalkEndTime" name="talkEndTime" value="${escapeHTML(session?.end_time || '')}" required>
                <input type="url" name="linkedin" placeholder="LinkedIn URL" value="${escapeHTML(speaker.social?.linkedin || '')}">
                <input type="url" name="twitter" placeholder="Twitter URL" value="${escapeHTML(speaker.social?.twitter || '')}">
                <input type="url" name="github" placeholder="GitHub URL" value="${escapeHTML(speaker.social?.github || '')}">
                <input type="url" name="website" placeholder="Website URL" value="${escapeHTML(speaker.social?.website || '')}">
                <button type="submit" class="btn btn-primary">Save</button>
            `;
            const form = editSpeakerModal.querySelector('#editSpeakerForm');
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
            form.querySelectorAll('[name="image_source"]').forEach(radio => radio.addEventListener('change', () => toggleImageInputs(form)));
            editSpeakerModal.style.display = 'flex';
        }

        closeEditModalBtn.addEventListener('click', () => { editSpeakerModal.style.display = 'none'; });
        window.addEventListener('click', (event) => { if (event.target == editSpeakerModal) editSpeakerModal.style.display = 'none'; });

        editSpeakerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = editSpeakerForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            const formData = new FormData(editSpeakerForm);

            const processUpdate = (imageUrl) => {
                const originalSpeaker = speakersData.find(s => s.id === currentlyEditingId);
                const updatedSpeakerData = {
                    ...originalSpeaker, // Preserve other properties like 'featured'
                    id: currentlyEditingId,
                    name: formData.get('name'), title: formData.get('title'), category: formData.get('category'),
                    image: imageUrl, bio: formData.get('bio'),
                    social: { linkedin: formData.get('linkedin'), twitter: formData.get('twitter'), github: formData.get('github'), website: formData.get('website') },
                    sessions: [{ title: formData.get('talkTitle'), date: formData.get('talkDate'), time: formData.get('talkTime'), end_time: formData.get('talkEndTime') }]
                };

                const ajaxFormData = new FormData();
                ajaxFormData.append('action', 'fossasia_manage_speakers');
                ajaxFormData.append('nonce', adminNonce);
                ajaxFormData.append('task', 'update_live');
                ajaxFormData.append('speaker', JSON.stringify(updatedSpeakerData));

                fetch(ajaxUrl, { method: 'POST', body: ajaxFormData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        speakersData = speakersData.map(s => s.id === currentlyEditingId ? updatedSpeakerData : s);
                        renderAll();
                        editSpeakerModal.style.display = 'none';
                        alert('Speaker updated successfully.');
                    } else {
                        alert('Error updating speaker: ' + data.data);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Save';
                });
            };

            const imageSource = formData.get('image_source');
            if (imageSource === 'upload' && formData.get('image_upload').size > 0) {
                const reader = new FileReader();
                reader.onload = (event) => processUpdate(event.target.result);
                reader.onerror = () => { alert('Error reading file.'); submitBtn.disabled = false; submitBtn.textContent = 'Save'; };
                reader.readAsDataURL(formData.get('image_upload'));
            } else {
                const speaker = speakersData.find(s => s.id === currentlyEditingId);
                const existingImage = speaker ? speaker.image : '';
                const imageUrl = formData.get('image_url');
                processUpdate(imageUrl || (existingImage.startsWith('data:image') ? existingImage : ''));
            }
        });
    }
    
    // Ensure the DOM is ready before rendering
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderAll);
    } else {
        renderAll();
    }
  })();
</script>
<!-- /wp:html -->

</main>
<!-- /wp:group -->

</div>
<!-- /wp:group -->