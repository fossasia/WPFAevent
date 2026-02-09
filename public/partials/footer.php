<?php

/**
 * Shared Footer Partial
 * Displays the footer used across WPFA templates, matching the MVP design exactly.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials
 * @since      1.0.0
 */

/**
 * Prevent direct access to this file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Default footer text with filter for customization
$default_footer = '© FOSSASIA • FOSSASIA Summit — Mar 8–10, 2026 • True Digital Park West, Bangkok';
$footer_text = get_option( 'wpfa_footer_text', '' );
if ( empty( $footer_text ) ) {
    $footer_text = apply_filters( 'wpfa_default_footer_text', $default_footer );
}

// Check if user is admin
$is_admin = current_user_can( 'manage_options' );
?>

<!-- Main Site Footer -->
<footer class="footer">
	<div class="container">
		<small id="footer-text-display" class="footer-text">
			<?php echo esc_html( $footer_text ); ?>
		</small>
		
		<?php if ( $is_admin ) : ?>
			<button id="edit-footer-btn" class="btn btn-secondary">
				<span class="btn-text"><?php esc_html_e( 'Edit Footer', 'wpfaevent' ); ?></span>
			</button>
		<?php endif; ?>
		
		<div class="social-icons">
			<a href="https://github.com/fossasia" target="_blank" rel="noopener noreferrer" title="GitHub">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
				</svg>
			</a>
			<a href="https://www.facebook.com/fossasia" target="_blank" rel="noopener noreferrer" title="Facebook">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
				</svg>
			</a>
			<a href="https://www.flickr.com/photos/fossasia/" target="_blank" rel="noopener noreferrer" title="Flickr">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M6.5 7.5c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5zm11 0c-2.485 0-4.5 2.015-4.5 4.5s2.015 4.5 4.5 4.5 4.5-2.015 4.5-4.5-2.015-4.5-4.5-4.5z"/>
				</svg>
			</a>
			<a href="https://www.linkedin.com/company/fossasia" target="_blank" rel="noopener noreferrer" title="LinkedIn">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
				</svg>
			</a>
			<a href="https://www.youtube.com/c/fossasia" target="_blank" rel="noopener noreferrer" title="YouTube">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
				</svg>
			</a>
			<a href="https://x.com/fossasia" target="_blank" rel="noopener noreferrer" title="X">
				<svg viewBox="0 0 24 24" fill="currentColor">
					<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.62l-5.21-6.817-6.022 6.817h-3.308l7.748-8.786-8.3-10.714h6.78l4.596 6.145 5.45-6.145zm-2.46 17.63h1.89l-9.48-12.605h-1.93l9.52 12.605z"/>
				</svg>
			</a>
		</div>
	</div>
</footer>

<?php if ( $is_admin ) : ?>
<!-- Admin-only footer edit modal -->
<div id="edit-footer-modal" class="modal">
	<div class="modal-content">
		<span class="close-btn">&times;</span>
		<form id="edit-footer-form">
			<h2><?php esc_html_e( 'Edit Footer Text', 'wpfaevent' ); ?></h2>
			<label for="footer-text"><?php esc_html_e( 'Footer Content:', 'wpfaevent' ); ?></label>
			<textarea id="footer-text" name="footer_text" rows="2" required><?php echo esc_textarea( $footer_text ); ?></textarea>
			<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save Footer', 'wpfaevent' ); ?></button>
		</form>
	</div>
</div>
<?php endif; ?>