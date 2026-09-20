# PLAN — fossasia/WPFAevent#312

## Goal
Fix two sub-bugs in the speakers template:
1. **Event resolution**: `/speakers/?event=<slug>` fails to resolve the event name in the hero
2. **Social link hover**: Social links on speaker profile pages show invisible text on hover

## Bug 1: Event resolution on speakers archive

### Root cause
`page-speakers.php:90` uses `get_page_by_path()` to resolve the event slug. This function queries `wp_posts` with `post_name = <slug>` AND `post_type = 'page'` (the third parameter is passed but WordPress's `get_page_by_path` prioritizes the `post_type` column differently). For custom post types like `wpfa_event`, the lookup fails.

The event card generates the link with `get_post_field('post_name', $event_id)` (line 118 of event-card.php), which returns the correct combined slug (e.g., `fossasia-open-source-summit`). But `get_page_by_path()` can't find it.

### Fix
Replace `get_page_by_path()` with a `WP_Query` using the `name` parameter, which is the canonical way to look up posts by slug for any post type:

```php
$candidate_event_query = new WP_Query( array(
    'name'           => sanitize_title( $current_event_filter ),
    'post_type'      => 'wpfa_event',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
) );
if ( ! empty( $candidate_event_query->posts ) ) {
    $selected_event_id = absint( $candidate_event_query->posts[0] );
}
wp_reset_postdata();
```

### Files to touch
- `public/templates/page-speakers.php` — lines 89-94 (event resolution logic)

### Verification
- `composer phpcs` exits 0
- `composer phpstan` exits 0
- `composer test` passes
- `composer test -- --testsuite Unit` passes

## Bug 2: Social link hover invisible text

### Root cause
In `speakers.css:381-384`, the social link hover rule uses `var(--event-primary)` for background and `var(--event-primary-contrast, #fff)` for text. However, on the single speaker profile page (`single-wpfa-speaker.php`), the `event_style_attr` is NOT applied to the `<body>` or any parent element — unlike the speakers archive which applies it at line 273. So the CSS variables from the event's style attribute are never set, and the social link text color falls back to the brand color (usually red) on a white background.

The fix: apply the event style attribute to the speaker profile body. But since the single speaker template doesn't have an event context (speakers can be linked to multiple events), the proper fix is to ensure the social link hover state doesn't depend on event variables. Instead, use explicit high-contrast values:

```css
.wpfaevent .wpfa-social-link:hover {
    background: var(--event-primary, #D51007);
    color: #fff;
}
```

### Files to touch
- `public/css/templates/speakers.css` — line 383

### Verification
- `composer phpcs` exits 0
- `composer phpstan` exits 0
- `composer test` passes

## Acceptance criteria
1. `/speakers/?event=<slug>` resolves the event name in the hero and shows speakers
2. Speaker social links have visible text on hover
3. `composer phpcs` exits 0
4. `composer phpstan` exits 0
5. `composer test` passes

## Risk
Minimal — CSS-only change for bug 2; targeted template logic change for bug 1. No business logic changes.
