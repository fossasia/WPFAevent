<?php
/**
 * Template Name: FOSSASIA Past Events (Plugin)
 * Description: A page to list events that have already concluded.
 */

$today = date('Y-m-d');

$past_events_query = new WP_Query([
    'post_type'      => 'page',
    'posts_per_page' => -1,
    'meta_key'       => '_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'DESC', // Show most recent past events first
    'meta_query'     => [
        'relation' => 'AND',
        [
            'key'     => '_wp_page_template',
            'value'   => 'public/partials/fossasia-landing-template.php',
            'compare' => '=',
        ],
        [
            // This logic finds events where the end date is in the past.
            // If no end date, it checks if the start date is in the past.
            'relation' => 'OR',
            [
                'key' => '_event_end_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE'
            ],
            [
                'relation' => 'AND',
                [
                    'key' => '_event_end_date',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_event_date',
                    'value' => $today,
                    'compare' => '<',
                    'type' => 'DATE'
                ]
            ]
        ]
    ],
]);
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
        a { color: var(--brand); text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        .site-logo { height: 36px; width: auto; }
        .container { width: 100%; max-width: var(--container); margin: 0 auto; padding: 24px; }
        .nav { position: sticky; top: 0; background: rgba(255,255,255,.9); backdrop-filter: blur(6px) saturate(120%); border-bottom: 1px solid #00000010; z-index: 60; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; padding: 14px 0; }
        .nav-links { display: flex; gap: .6rem; align-items: center; }
        .nav-links a { padding: .4rem .6rem; border-radius: 999px; font-weight: 600; color: #222; font-size: 0.9rem; }
        .nav-links a:hover { background: #00000006; }
        .admin-bar .nav { top: 32px; }
        @media (max-width: 782px) { .admin-bar .nav { top: 46px; } }

        .page-hero { text-align: center; padding: 60px 20px; background: #fff; margin-bottom: 30px; }
        .page-hero h1 { margin: 0 0 10px; font-size: 2.5rem; color: var(--brand); }
        .page-hero p { color: var(--muted); font-size: 1.1rem; max-width: 70ch; margin: 0 auto; }
        .btn-secondary { background: #6c757d; color: #fff; padding: .6rem 1rem; border-radius: 999px; font-weight: 700; }
        .hero-ctas { margin-top: 2rem; }

        .main-content { background: #fff; padding: 20px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .main-content-header h1 { margin: 0; }

        #events-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .event-card { background: #fff; border-radius: var(--card-radius); box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; transition: transform .2s ease, box-shadow .2s ease; }
        .event-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(11,11,11,.1); }
        .event-card-link { text-decoration: none; color: inherit; }
        .event-card-image { height: 180px; background-color: #f0f0f0; }
        .event-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .event-card-content { padding: 15px; }
        .event-card-content h3 { margin: 0 0 10px; font-size: 1.25rem; }
        .event-card-content p { margin: 5px 0 0; color: var(--muted); font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .event-card-content p svg { width: 16px; height: 16px; flex-shrink: 0; }
        .placeholder-text { text-align: center; color: var(--muted); padding: 40px 0; }
        /* Admin action buttons on cards */
        .event-card { position: relative; }
        .event-card-actions { position: absolute; top: 10px; right: 10px; z-index: 5; display: flex; gap: 5px; }
        .event-card-actions button, .event-card-actions a { background: rgba(0,0,0,0.6); color: white !important; border: none; border-radius: 4px; padding: 5px 8px; font-size: 12px; cursor: pointer; text-decoration: none; font-weight: bold; }
        .event-card-actions button:hover, .event-card-actions a:hover { background: rgba(0,0,0,0.8); }
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; } .modal-content { background-color: #fefefe; margin: auto; padding: 20px 30px 30px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 16px; position: relative; box-shadow: 0 15px 40px rgba(0,0,0,0.2); } .close-btn { color: #aaa; position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; } #editEventForm { display: flex; flex-direction: column; gap: 15px; } #editEventForm h2 { margin-top: 0; color: var(--brand); } #editEventForm label { font-weight: 600; margin-bottom: -10px; } #editEventForm input, #editEventForm textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 1rem; } #editEventForm .char-counter { font-size: 0.85rem; color: var(--muted); text-align: right; margin-top: -10px; } #editEventForm button { margin-top: 15px; align-self: flex-start; } .btn-primary { background: var(--brand); color: #fff; }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="container nav-inner">
        <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            <?php
                $logo_url = plugins_url( '../../assets/images/logo.png', __FILE__ );
            ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
            <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Upcoming Events</a>
            <a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>" style="background: #00000006;">Past Events</a>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-hero">
            <h1>Past FOSSASIA Events</h1>
            <p>A look back at our community events, meetups, and conferences.</p>
            <div class="hero-ctas">
                <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>" class="btn-secondary">
                    View Upcoming Events
                </a>
            </div>
        </header>

        <div class="container">
            <div class="main-content">
                <div class="main-content-header">
                    <h1>Event Archive</h1>
                </div>

                <div id="events-container">
                    <?php
                    if ( $past_events_query->have_posts() ) :
                        while ( $past_events_query->have_posts() ) : $past_events_query->the_post();
                            $event_date = get_post_meta( get_the_ID(), '_event_date', true );
                            $event_end_date = get_post_meta( get_the_ID(), '_event_end_date', true );
                            $event_place = get_post_meta( get_the_ID(), '_event_place', true );
                            $event_time = get_post_meta( get_the_ID(), '_event_time', true );
                            $event_description = get_the_excerpt();
                            $event_lead_text = get_post_meta( get_the_ID(), '_event_lead_text', true );
                            $event_registration_link = get_post_meta( get_the_ID(), '_event_registration_link', true );
                            $event_cfs_link = get_post_meta( get_the_ID(), '_event_cfs_link', true );
                            $featured_img_url = get_the_post_thumbnail_url( get_the_ID(), 'large' ) ?: '';
                            
                            $formatted_date = 'Date not set';
                            if (!empty($event_date)) {
                                $start = date_create($event_date);
                                if (!empty($event_end_date) && $event_end_date !== $event_date) {
                                    $end = date_create($event_end_date);
                                    $formatted_date = date_format($start, 'M j') . ' - ' . date_format($end, 'M j, Y');
                                } else {
                                    $formatted_date = date_format($start, 'F j, Y');
                                }
                            }
                    ?>
                        <div class="event-card"
                            data-post-id="<?php echo get_the_ID(); ?>"
                            data-name="<?php echo esc_attr(get_the_title()); ?>"
                            data-date="<?php echo esc_attr($event_date); ?>"
                            data-place="<?php echo esc_attr($event_place); ?>"
                            data-time="<?php echo esc_attr($event_time); ?>"
                            data-end-date="<?php echo esc_attr($event_end_date); ?>"
                            data-description="<?php echo esc_attr($event_description); ?>"
                            data-lead-text="<?php echo esc_attr($event_lead_text); ?>"
                            data-registration-link="<?php echo esc_attr($event_registration_link); ?>"
                            data-cfs-link="<?php echo esc_attr($event_cfs_link); ?>"
                            data-image-url="<?php echo esc_url($featured_img_url); ?>"
                            data-permalink="<?php the_permalink(); ?>">
                            <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                <?php
                                    $dashboard_url = get_permalink( get_page_by_path( "admin-dashboard" ) );
                                    $edit_content_url = add_query_arg( 'event_id', get_the_ID(), $dashboard_url );
                                ?>
                                <div class="event-card-actions">
                                    <button class="btn-edit-event">Edit Details</button>
                                    <a href="<?php echo esc_url($edit_content_url); ?>" class="btn-edit-content">Edit Content</a>
                                    <button class="btn-delete-event">Delete</button>
                                </div>
                            <?php endif; ?>
                            <a href="<?php the_permalink(); ?>" class="event-card-link">
                                <div class="event-card-image">
                                    <img src="<?php echo esc_url($featured_img_url); ?>" alt="<?php the_title_attribute(); ?>">
                                </div>
                                <div class="event-card-content">
                                    <h3><?php the_title(); ?></h3>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"></path></svg> <?php echo esc_html($formatted_date); ?></p>
                                    <p><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"></path></svg> <?php echo esc_html($event_place); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p class="placeholder-text">No past events found in the archive.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <form id="editEventForm">
            <h2>Edit Event</h2>
            <input type="hidden" id="editEventId" name="postId">
            <label for="editEventName">Event Name:</label>
            <input type="text" id="editEventName" name="eventName" required>

            <label for="editEventDate">Event Date:</label>
            <input type="date" id="editEventDate" name="eventDate" required>

            <label for="editEventEndDate">Event End Date (optional):</label>
            <input type="date" id="editEventEndDate" name="eventEndDate">

            <label for="editEventTime">Event Time:</label>
            <input type="time" id="editEventTime" name="eventTime" required>

            <label for="editEventPlace">Event Place:</label>
            <input type="text" id="editEventPlace" name="eventPlace" required>

            <label for="editEventDescription">Description (2-3 sentences):</label>
            <textarea id="editEventDescription" name="eventDescription" rows="3" required maxlength="300"></textarea>
            <small class="char-counter">0 / 300</small>

            <label for="editEventLeadText">Hero Lead Text:</label>
            <textarea id="editEventLeadText" name="eventLeadText" rows="2" required maxlength="160"></textarea>
            <small class="char-counter">0 / 160</small>

            <label for="editRegistrationLink">Registration Link:</label>
            <input type="url" id="editRegistrationLink" name="eventRegistrationLink" placeholder="https://eventyay.com/e/..." required>

            <label for="editCfsLink">Call for Speakers Link (optional):</label>
            <input type="url" id="editCfsLink" name="eventCfsLink" placeholder="https://eventyay.com/e/.../cfs">

            <label for="editEventPicture">Update Picture (optional):</label>
            <input type="file" id="editEventPicture" name="eventPicture" accept="image/*">

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
    const adminNonce = '<?php echo wp_create_nonce("fossasia_admin_nonce"); ?>';

    class PastEventsPageManager {
        constructor(ajaxUrl, nonce) {
            this.ajaxUrl = ajaxUrl;
            this.nonce = nonce;
            this.eventsContainer = document.getElementById('events-container');
            this.editModal = document.getElementById('editEventModal');
            this.editForm = document.getElementById('editEventForm');
            this.closeEditBtn = this.editModal.querySelector('.close-btn');
        }

        init() {
            if (!this.eventsContainer) return;
            this.closeEditBtn?.addEventListener('click', () => this.closeEditModal());
            window.addEventListener('click', (event) => {
                if (event.target === this.editModal) this.closeEditModal();
            });
            this.editForm?.addEventListener('submit', (e) => this.handleEditFormSubmit(e));
            this.eventsContainer.addEventListener('click', (e) => this.handleCardActions(e));
        }

        openEditModal() { this.editModal.style.display = 'flex'; }
        closeEditModal() { this.editModal.style.display = 'none'; }

        handleCardActions(e) {
            const target = e.target;
            if (target.matches('.btn-edit-event')) {
                e.preventDefault();
                e.stopPropagation();
                const card = target.closest('.event-card');
                this.populateAndOpenEditModal(card);
            } else if (target.matches('.btn-delete-event')) {
                e.preventDefault();
                e.stopPropagation();
                const card = target.closest('.event-card');
                this.handleDeleteEvent(card);
            }
        }

        populateAndOpenEditModal(card) {
            this.editForm.querySelector('#editEventId').value = card.dataset.postId;
            this.editForm.querySelector('#editEventName').value = card.dataset.name;
            this.editForm.querySelector('#editEventDate').value = card.dataset.date;
            this.editForm.querySelector('#editEventEndDate').value = card.dataset.endDate;
            this.editForm.querySelector('#editEventPlace').value = card.dataset.place;
            this.editForm.querySelector('#editEventTime').value = card.dataset.time;
            this.editForm.querySelector('#editEventDescription').value = card.dataset.description;
            this.editForm.querySelector('#editEventLeadText').value = card.dataset.leadText || '';
            this.editForm.querySelector('#editRegistrationLink').value = card.dataset.registrationLink;
            this.editForm.querySelector('#editCfsLink').value = card.dataset.cfsLink || '';
            this.openEditModal();
        }

        handleEditFormSubmit(e) {
            e.preventDefault();
            const editButton = this.editForm.querySelector('button[type="submit"]');
            editButton.disabled = true;
            editButton.textContent = 'Saving...';

            const formData = new FormData(this.editForm);
            formData.append('action', 'fossasia_edit_event_page');
            formData.append('nonce', this.nonce);

            fetch(this.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                })
                .finally(() => {
                    editButton.disabled = false;
                    editButton.textContent = 'Save Changes';
                    this.closeEditModal();
                });
        }

        handleDeleteEvent(card) {
            const postId = card.dataset.postId;
            if (!confirm(`Are you sure you want to delete the event "${card.dataset.name}"? This cannot be undone.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'fossasia_delete_event_page');
            formData.append('nonce', this.nonce);
            formData.append('postId', postId);

            fetch(this.ajaxUrl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.data.message);
                        card.remove();
                    } else {
                        alert('Error: ' + data.data);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }

    // Initialize the manager if the user is an admin
    <?php if ( current_user_can( 'manage_options' ) ) : ?>
        const eventsManager = new PastEventsPageManager(ajaxUrl, adminNonce);
        eventsManager.init();
    <?php endif; ?>
});
</script>
</body>
</html>