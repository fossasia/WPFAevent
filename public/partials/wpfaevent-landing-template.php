<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$post_id    = get_the_ID();
$upload_dir = wp_upload_dir();
$data_dir   = $upload_dir['basedir'] . '/fossasia-data';

// Event-specific files
$speakers_file = $data_dir . '/speakers-' . $post_id . '.json';
$sponsors_file = $data_dir . '/sponsors-' . $post_id . '.json';
$settings_file = $data_dir . '/site-settings-' . $post_id . '.json';
$schedule_file = $data_dir . '/schedule-' . $post_id . '.json';

// Section files
$global_sections_file       = $data_dir . '/custom-sections.json';
$event_sections_file        = $data_dir . '/custom-sections-' . $post_id . '.json';
$navigation_file            = $data_dir . '/navigation.json';
$global_settings_file       = $data_dir . '/site-settings.json';
$global_theme_settings_file = $data_dir . '/theme-settings.json';
$event_theme_settings_file  = $data_dir . '/theme-settings-' . $post_id . '.json';

// Ensure file exists, if not, it will be created on plugin activation.
if ( ! file_exists( $speakers_file ) ) {
	file_put_contents( $speakers_file, '[]' ); }
if ( ! file_exists( $sponsors_file ) ) {
	file_put_contents( $sponsors_file, '[]' ); }
if ( ! file_exists( $settings_file ) ) {
	file_put_contents( $settings_file, '{}' ); }
if ( ! file_exists( $global_sections_file ) ) {
	file_put_contents( $global_sections_file, '[]' ); }
if ( ! file_exists( $event_sections_file ) ) {
	file_put_contents( $event_sections_file, '[]' ); }
if ( ! file_exists( $navigation_file ) ) {
	file_put_contents( $navigation_file, '[]' ); }
if ( ! file_exists( $global_settings_file ) ) {
	file_put_contents( $global_settings_file, '{"site_logo_url": ""}' ); }
if ( ! file_exists( $global_theme_settings_file ) ) {
	file_put_contents( $global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}' ); }
if ( ! file_exists( $event_theme_settings_file ) ) {
	file_put_contents( $event_theme_settings_file, file_get_contents( $global_theme_settings_file ) ); }

$speakers_data      = json_decode( file_get_contents( $speakers_file ), true );
$sponsors_data      = json_decode( file_get_contents( $sponsors_file ), true );
$site_settings_data = json_decode( file_get_contents( $settings_file ), true ) ?: array();
// Load both global and event-specific sections and merge them.
$global_sections      = json_decode( file_get_contents( $global_sections_file ), true ) ?: array();
$event_sections       = json_decode( file_get_contents( $event_sections_file ), true ) ?: array();
$custom_sections_data = array_merge( $global_sections, $event_sections );

$navigation_data      = json_decode( file_get_contents( $navigation_file ), true );
$global_settings_data = json_decode( file_get_contents( $global_settings_file ), true );
// Load event-specific theme, with a fallback to the global theme.
$theme_settings_data = json_decode( file_get_contents( $event_theme_settings_file ), true );
if ( empty( $theme_settings_data ) ) {
	$theme_settings_data = json_decode( file_get_contents( $global_theme_settings_file ), true ); }

/**
 * Converts a standard video URL (YouTube, Vimeo) into an embeddable URL.
 *
 * @param string $url The original video URL.
 * @return string The embeddable URL.
 */
function get_video_embed_url( $url ) {
	if ( empty( $url ) ) {
		return '';
	}

	// Check for YouTube
	if ( preg_match( '/(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|)([\w-]{11})/', $url, $matches ) ) {
		return 'https://www.youtube.com/embed/' . $matches[3];
	}

	// Check for Vimeo
	if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches ) ) {
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
function render_custom_sections( $position, $all_sections ) {
	if ( empty( $all_sections ) || ! is_array( $all_sections ) ) {
		return;
	}

	$sections_to_render = array_filter(
		$all_sections,
		function ( $section ) use ( $position ) {
			return isset( $section['position'], $section['is_active'] ) && $section['position'] === $position && $section['is_active'];
		}
	);

	if ( empty( $sections_to_render ) ) {
		return;
	}

	usort(
		$sections_to_render,
		function ( $a, $b ) {
			return ( $a['order'] ?? 10 ) <=> ( $b['order'] ?? 10 );
		}
	);

	foreach ( $sections_to_render as $section ) {
		$section_id   = esc_attr( $section['id'] );
		$layout       = $section['layout'] ?? 'full_width';
		$section_type = $section['type'] ?? 'content';
		?>
		<section class="wp-block-group custom-section-added" id="custom-section-<?php echo $section_id; ?>" style="padding: 48px 0;">
			<div class="container reveal" style="max-width: var(--container); margin: 0 auto; padding: 0 24px;">
				<div class="section-head">
					<?php if ( ! empty( $section['title'] ) ) : ?>
						<h2 class="h2"><?php echo esc_html( $section['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $section['subtitle'] ) ) : ?>
						<p class="meta"><?php echo esc_html( $section['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>

				<?php
				if ( $section_type === 'media' ) {
					// --- RENDER MEDIA SECTION ---
					if ( $section['mediaType'] === 'photo' && ! empty( $section['photo_src'] ) ) {
						$image_src = $section['photo_src'];
						echo '<figure class="wp-block-image size-full"><img src="' . $image_src . '" alt="' . esc_attr( $section['title'] ) . '" style="border-radius: 12px; width: 100%;"></figure>';
					} elseif ( $section['mediaType'] === 'video' && ! empty( $section['video_embed_src'] ) ) {
						$embed_url = get_video_embed_url( $section['video_embed_src'] );
						echo '<div style="padding:0; aspect-ratio: 16/9; overflow:hidden; border-radius: 12px;"><iframe width="100%" height="100%" src="' . esc_url( $embed_url ) . '" title="Embedded video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
					} elseif ( $section['mediaType'] === 'carousel' && ! empty( $section['carousel_images'] ) && is_array( $section['carousel_images'] ) ) {
						$carousel_id = 'carousel-' . esc_attr( $section['id'] );
						$timer       = ! empty( $section['carousel_timer'] ) ? absint( $section['carousel_timer'] ) * 1000 : 5000;
						?>
						<div class="media-carousel" id="<?php echo $carousel_id; ?>">
							<?php foreach ( $section['carousel_images'] as $index => $image_src ) : ?>
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
				} elseif ( $layout === 'full_width' ) {
					// Full width layout
					?>
					<div class="panel">
						<?php if ( ! empty( $section['contentTitle'] ) ) : ?>
							<h3 style="margin-top:0;"><?php echo esc_html( $section['contentTitle'] ); ?></h3>
						<?php endif; ?>
						<?php if ( ! empty( $section['contentBody'] ) ) : ?>
							<div class="custom-section-content"><?php echo wp_kses_post( $section['contentBody'] ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $section['buttonText'] ) && ! empty( $section['buttonLink'] ) ) : ?>
							<div class="wp-block-buttons hero-ctas" style="margin-top:14px;">
								<div class="wp-block-button btn btn-primary">
									<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $section['buttonLink'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $section['buttonText'] ); ?></a>
								</div>
							</div>
						<?php endif; ?>
					</div>
					<?php
				} else {
					// Two column layouts
					$media_col_html = '';
					if ( $section['mediaType'] === 'photo' && ! empty( $section['photo_src'] ) ) {
						$image_src      = $section['photo_src'];
						$media_col_html = '<div class="wp-block-column" style="padding:0;"><figure class="wp-block-image size-full" style="margin:0;"><img src="' . $image_src . '" alt="' . esc_attr( $section['title'] ) . ' media" style="border-radius: 12px; width: 100%; height: 100%; object-fit: cover;"></figure></div>';
					} elseif ( $section['mediaType'] === 'map' && ! empty( $section['map_embed_src'] ) ) {
						$media_col_html = '<div class="wp-block-column map panel reveal"><iframe width="100%" height="100%" style="min-height:320px;border:0;border-radius:12px;" loading="lazy" src="' . esc_url( $section['map_embed_src'] ) . '" title="Venue map"></iframe></div>';
					}

					$content_col_html = '<aside class="wp-block-column panel reveal">';
					if ( ! empty( $section['contentTitle'] ) ) {
						$content_col_html .= '<h3 style="margin-top:0;">' . esc_html( $section['contentTitle'] ) . '</h3>';
					}
					if ( ! empty( $section['contentBody'] ) ) {
						$content_col_html .= '<div class="custom-section-content">' . wp_kses_post( $section['contentBody'] ) . '</div>';
					}
					if ( ! empty( $section['buttonText'] ) && ! empty( $section['buttonLink'] ) ) {
						$content_col_html .= '<div class="wp-block-buttons hero-ctas" style="margin-top:14px;"><div class="wp-block-button btn btn-primary"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $section['buttonLink'] ) . '" target="_blank" rel="noopener">' . esc_html( $section['buttonText'] ) . '</a></div></div>';
					}
					$content_col_html .= '</aside>';

					?>
					<div class="wp-block-columns venue-grid">
						<?php
						if ( $layout === 'two_col_media_left' ) {
							if ( ! empty( $media_col_html ) ) {
								echo $media_col_html;
							}
							echo $content_col_html;
						} else { // two_col_media_right
							echo $content_col_html;
							if ( ! empty( $media_col_html ) ) {
								echo $media_col_html;
							}
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
$event_date_string     = 'March 13–15, 2025 • Bangkok'; // Default date string
$event_title_string    = 'FOSSASIA Summit — Building open tech across Asia'; // Default title

$hero_image_url = ''; // Default

if ( $is_dynamic_event_page && has_post_thumbnail( get_the_ID() ) ) {
	$hero_image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
}

if ( $is_dynamic_event_page ) {
	$event_start_date   = get_post_meta( get_the_ID(), '_event_date', true );
	$event_title_string = get_the_title(); // Get the post title for the event
	$event_end_date     = get_post_meta( get_the_ID(), '_event_end_date', true );
	$event_place        = get_post_meta( get_the_ID(), '_event_place', true );
	$event_lead_text    = get_post_meta( get_the_ID(), '_event_lead_text', true );

	if ( ! empty( $event_start_date ) ) {
		$start = date_create( $event_start_date );
		if ( ! empty( $event_end_date ) && $event_end_date !== $event_start_date ) {
			$end = date_create( $event_end_date );
			// e.g., "Mar 13 - 15, 2025"
			$event_date_string = date_format( $start, 'M j' ) . '–' . date_format( $end, 'j, Y' );
		} else {
			$event_date_string = date_format( $start, 'F j, Y' );
		}
		$event_date_string .= ' • ' . esc_html( $event_place );
	}
}

// Get section visibility settings
$section_visibility = $site_settings_data['section_visibility'] ?? array();

?>
<?php
	// Determine the correct registration link
	$reg_button_text = $site_settings_data['reg_button_text'] ?? 'Get Tickets';
	$reg_button_link = $site_settings_data['reg_button_link'] ?? get_post_meta( get_the_ID(), '_event_registration_link', true );
if ( empty( $reg_button_link ) ) {
	$reg_button_link = '#'; } // Fallback

	// Get CFS button details from event-specific settings, with fallbacks
	$cfs_button_text = $site_settings_data['cfs_button_text'] ?? 'Call for Speakers';
	$cfs_button_link = $site_settings_data['cfs_button_link'] ?? get_post_meta( get_the_ID(), '_event_cfs_link', true );
if ( empty( $cfs_button_link ) ) {
	$cfs_button_link = '#'; }
?>
<!-- wp:group {"className":"site","layout":{"type":"constrained"}} -->
<div class="wp-block-group site">

<!-- wp:html -->
<style>
	:root {
	--brand: <?php echo esc_html( $theme_settings_data['brand_color'] ?? '#D51007' ); ?>;
	--bg: <?php echo esc_html( $theme_settings_data['background_color'] ?? '#f8f9fa' ); ?>;
	--text: <?php echo esc_html( $theme_settings_data['text_color'] ?? '#0b0b0b' ); ?>;
	--navbar-bg: <?php echo esc_html( $theme_settings_data['navbar_color'] ?? '#ffffff' ); ?>;
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
	/* nav */ .nav{position:sticky;top:0;background:color-mix(in srgb, var(--navbar-bg) 90%, transparent);backdrop-filter:blur(6px) saturate(120%);border-bottom:1px solid #00000010;z-index:60}
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
	@-moz-keyframes fadeOut
