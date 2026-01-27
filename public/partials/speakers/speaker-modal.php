<?php
/**
 * Unified Speaker Modal Partial
 * Single reusable modal for both adding and editing speakers.
 *
 * @package    Wpfaevent
 * @subpackage Wpfaevent/public/partials/speakers
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get existing categories for dropdown if taxonomy exists
$category_terms = array();
if ( taxonomy_exists( 'wpfa_speaker_category' ) ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'wpfa_speaker_category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$category_terms = $terms;
	}
}
?>

<!-- Unified Speaker Modal -->
<div id="wpfa-speaker-modal" class="wpfa-modal">
	<div class="wpfa-modal-content">
		<button type="button" class="wpfa-modal-close" aria-label="<?php esc_attr_e( 'Close modal', 'wpfaevent' ); ?>">
			&times;
		</button>
		
		<form id="wpfa-speaker-form" class="wpfa-speaker-form">
			<h2 id="wpfa-modal-title"><?php esc_html_e( 'Add New Speaker', 'wpfaevent' ); ?></h2>
			
			<input type="hidden" id="wpfa-speaker-id" name="speaker_id" value="">
			<input type="hidden" id="wpfa-modal-action" name="action" value="add">
			
			<div class="wpfa-form-section">
				<h3 class="wpfa-form-section-title"><?php esc_html_e( 'Speaker Information', 'wpfaevent' ); ?></h3>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-name"><?php esc_html_e( 'Name', 'wpfaevent' ); ?> *</label>
					<input type="text" id="wpfa-speaker-name" name="name" required>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-position"><?php esc_html_e( 'Position/Title', 'wpfaevent' ); ?> *</label>
					<input type="text" id="wpfa-speaker-position" name="position" required 
						placeholder="<?php esc_attr_e( 'e.g., Developer at Company', 'wpfaevent' ); ?>">
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-organization"><?php esc_html_e( 'Organization', 'wpfaevent' ); ?></label>
					<input type="text" id="wpfa-speaker-organization" name="organization">
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-category"><?php esc_html_e( 'Category/Track', 'wpfaevent' ); ?></label>
					<?php if ( ! empty( $category_terms ) ) : ?>
						<select id="wpfa-speaker-category" name="category">
							<option value=""><?php esc_html_e( 'Select a category', 'wpfaevent' ); ?></option>
							<?php foreach ( $category_terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>">
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
							<option value="_custom"><?php esc_html_e( '+ Add New Category', 'wpfaevent' ); ?></option>
						</select>
						<input type="text" id="wpfa-speaker-category-custom" name="category_custom" 
							placeholder="<?php esc_attr_e( 'Enter new category', 'wpfaevent' ); ?>" style="display:none; margin-top: 5px;">
					<?php else : ?>
						<input type="text" id="wpfa-speaker-category" name="category" 
							placeholder="<?php esc_attr_e( 'e.g., AI, Web Development, Cloud', 'wpfaevent' ); ?>">
					<?php endif; ?>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-bio"><?php esc_html_e( 'Biography', 'wpfaevent' ); ?> *</label>
					<textarea id="wpfa-speaker-bio" name="bio" rows="4" required></textarea>
				</div>
			</div>
			
			<div class="wpfa-form-section">
				<h3 class="wpfa-form-section-title"><?php esc_html_e( 'Session Details', 'wpfaevent' ); ?></h3>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-talk-title"><?php esc_html_e( 'Talk Title', 'wpfaevent' ); ?> *</label>
					<input type="text" id="wpfa-speaker-talk-title" name="talk_title" required>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-talk-date"><?php esc_html_e( 'Date', 'wpfaevent' ); ?> *</label>
					<input type="date" id="wpfa-speaker-talk-date" name="talk_date" required>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-talk-time"><?php esc_html_e( 'Start Time', 'wpfaevent' ); ?> *</label>
					<input type="time" id="wpfa-speaker-talk-time" name="talk_time" required>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-talk-end-time"><?php esc_html_e( 'End Time', 'wpfaevent' ); ?> *</label>
					<input type="time" id="wpfa-speaker-talk-end-time" name="talk_end_time" required>
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-talk-abstract"><?php esc_html_e( 'Talk Abstract (Optional)', 'wpfaevent' ); ?></label>
					<textarea id="wpfa-speaker-talk-abstract" name="talk_abstract" rows="3"></textarea>
				</div>
			</div>
			
			<div class="wpfa-form-section">
				<h3 class="wpfa-form-section-title"><?php esc_html_e( 'Speaker Image', 'wpfaevent' ); ?></h3>
				
				<div class="wpfa-form-group" id="wpfa-image-url-group">
					<label for="wpfa-speaker-image-url"><?php esc_html_e( 'Image URL', 'wpfaevent' ); ?> *</label>
					<input type="url" id="wpfa-speaker-image-url" name="image_url" 
						placeholder="https://example.com/photo.jpg" required>
				</div>
			</div>
			
			<div class="wpfa-form-section">
				<h3 class="wpfa-form-section-title"><?php esc_html_e( 'Social Links (Optional)', 'wpfaevent' ); ?></h3>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-linkedin"><?php esc_html_e( 'LinkedIn URL', 'wpfaevent' ); ?></label>
					<input type="url" id="wpfa-speaker-linkedin" name="linkedin">
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-twitter"><?php esc_html_e( 'Twitter URL', 'wpfaevent' ); ?></label>
					<input type="url" id="wpfa-speaker-twitter" name="twitter">
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-github"><?php esc_html_e( 'GitHub URL', 'wpfaevent' ); ?></label>
					<input type="url" id="wpfa-speaker-github" name="github">
				</div>
				
				<div class="wpfa-form-group">
					<label for="wpfa-speaker-website"><?php esc_html_e( 'Website URL', 'wpfaevent' ); ?></label>
					<input type="url" id="wpfa-speaker-website" name="website">
				</div>
			</div>
			
			<div class="wpfa-form-actions">
				<button type="submit" class="wpfa-btn wpfa-btn-primary">
					<span id="wpfa-submit-text"><?php esc_html_e( 'Add Speaker', 'wpfaevent' ); ?></span>
				</button>
				<!-- Cancel button removed - using the close (×) button instead -->
			</div>
		</form>
	</div>
</div>

<script>
// Category dropdown toggle for custom category
document.addEventListener('DOMContentLoaded', function() {
	const categorySelect = document.getElementById('wpfa-speaker-category');
	const customCategoryInput = document.getElementById('wpfa-speaker-category-custom');
	
	if (categorySelect && customCategoryInput) {
		categorySelect.addEventListener('change', function() {
			if (this.value === '_custom') {
				customCategoryInput.style.display = 'block';
				customCategoryInput.required = true;
			} else {
				customCategoryInput.style.display = 'none';
				customCategoryInput.required = false;
			}
		});
	}
});
</script>
