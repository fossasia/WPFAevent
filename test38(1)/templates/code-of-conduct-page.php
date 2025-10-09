<?php
/**
 * Template Name: FOSSASIA Code of Conduct (Plugin)
 * Description: A page to display the event's Code of Conduct.
 */

$upload_dir = wp_upload_dir();
$data_dir = $upload_dir['basedir'] . '/fossasia-data';
$global_theme_settings_file = $data_dir . '/theme-settings.json'; // This is a global page
if (!file_exists($global_theme_settings_file)) { file_put_contents($global_theme_settings_file, '{"brand_color": "#D51007", "background_color": "#f8f9fa", "text_color": "#0b0b0b"}'); }
$theme_settings_data = json_decode(file_get_contents($global_theme_settings_file), true);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
      :root {
        --brand: <?php echo esc_html($theme_settings_data['brand_color'] ?? '#D51007'); ?>;
        --bg: <?php echo esc_html($theme_settings_data['background_color'] ?? '#f8f9fa'); ?>;
        --text: <?php echo esc_html($theme_settings_data['text_color'] ?? '#0b0b0b'); ?>;
      }
    </style>
    <style>
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

        .main-content { background: #fff; padding: 30px 40px; border-radius: var(--card-radius); box-shadow: var(--shadow); }
        .main-content h2 { font-size: 1.8rem; color: var(--brand); margin-top: 2.5rem; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .main-content h2:first-of-type { margin-top: 0; }
        .main-content p, .main-content li { line-height: 1.7; color: #333; }
        .main-content ul { padding-left: 20px; }
        .main-content strong { color: #111; }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header class="nav" role="banner">
      <div class="container nav-inner">
        <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">
            <img src="<?php echo plugins_url( '../images/logo.png', __FILE__ ); ?>" alt="Logo" class="site-logo">
        </a>
        <nav class="nav-links" role="navigation" aria-label="Primary">
            <a href="<?php echo esc_url( home_url( '/events/' ) ); ?>">Events</a>
            <a href="<?php echo esc_url( home_url( '/past-events/' ) ); ?>">Past Events</a>
        </nav>
      </div>
    </header>

    <main>
        <header class="page-hero">
            <h1>Code of Conduct</h1>
            <p>Our commitment to a safe, respectful, and harassment-free event experience for everyone.</p>
        </header>

        <div class="container">
            <div class="main-content">
                <p><strong>The FOSSASIA and its project communities are dedicated to providing a harassment-free experience for participants at all of our events, whether they are held in person or virtually.</strong> FOSSASIA events are working conferences intended for professional networking and collaboration within the open source community. They exist to encourage the open exchange of ideas and expression and require an environment that recognizes the inherent worth of every person and group. While at FOSSASIA events or related ancillary or social events, any participants, including members, speakers, attendees, volunteers, sponsors, exhibitors, booth staff and anyone else, must not engage in harassment in any form.</p>
                <p>This Code of Conduct may be revised at any time by FOSSASIA and the terms are non-negotiable. Your registration for or attendance at any FOSSASIA event, whether it’s held in person or virtually, indicates your agreement to abide by this policy and its terms.</p>

                <h2>Expected Behavior</h2>
                <p>All event participants, whether they are attending an in-person event or a virtual event, are expected to behave in accordance with professional standards, with both this Code of Conduct as well as their respective employer’s policies governing appropriate workplace behavior and applicable laws.</p>

                <h2>Unacceptable Behavior</h2>
                <p>Harassment will not be tolerated in any form, whether in person or virtually, including, but not limited to, harassment based on gender, gender identity and expression, sexual orientation, disability, physical appearance, body size, race, age, religion or any other status protected by laws in which the conference or program is being held. Harassment includes the use of abusive, offensive or degrading language, intimidation, stalking, harassing photography or recording, inappropriate physical contact, sexual imagery and unwelcome sexual advances or requests for sexual favors. Any report of harassment at one of our events, whether in person or virtual, will be addressed immediately. Participants asked to stop any harassing behavior are expected to comply immediately. Anyone who witnesses or is subjected to unacceptable behavior should notify a conference organizer at once.</p>
                <p>Individuals who participate (or plan to participate) in FOSSASIA events, whether its an in-person event or a virtual event, should conduct themselves at all times in a manner that comports with both the letter and spirit of this policy prohibiting harassment and abusive behavior, whether before, during or after the event. This includes statements made in social media postings, on-line publications, text messages, and all other forms of electronic communication.</p>
                <p>Speakers should not use sexual language, images, or any language or images that would constitute harassment as defined above in their talks. Exhibitor booths serve as a platform for presenting businesses and/or projects and should maintain a professional and inclusive presence; therefore, the use of sexualized images, activities, materials, or attire, including costumes and uniforms that contribute to a sexualized environment, is strictly prohibited. Impersonating event staff or a government official, or deliberately wearing clothing or insignia that may mislead others into believing you are affiliated with law enforcement, emergency services, or other government agencies is prohibited for all participants. Additionally, booths must not be utilized for political campaigning or promoting political causes, including the display or engagement in activities or materials that support such endeavors.</p>

                <h2>Consequences of Unacceptable Behavior</h2>
                <p>If a participant engages in harassing behavior, whether in person or virtually, the conference organizers may take any action they deem appropriate depending on the circumstances, ranging from issuance of a warning to the offending individual to expulsion from the conference with no refund. FOSSASIA reserves the right to exclude any participant found to be engaging in harassing behavior from participating in any further FOSSASIA events, trainings or other activities.</p>
                <p>If a participant (or individual wishing to participate in a FOSSASIA event, in-person and/or virtual), through postings on social media or other online publications or another form of electronic communication, engages in conduct that violates this policy, whether before, during or after a FOSSASIA event, FOSSASIA may take appropriate corrective action, which could include imposing a temporary or permanent ban on an individual’s participation in future FOSSASIA events.</p>

                <h2>What To Do If You Witness or Are Subject To Unacceptable Behavior</h2>
                <p>If you are being harassed, notice that someone else is being harassed, or have any other concerns relating to harassment, please contact a member of the conference staff immediately. You are also encouraged to contact Angela Brown, Senior VP & General Manager of Events, at angela@linuxfoundation.org.</p>

                <h2>Incident Response</h2>
                <p>Our staff has taken incident response training and responds to harassment reports quickly and thoroughly. As referenced above, if a participant engages in harassing behavior, whether in-person or virtually, the conference organizers may take any action they deem appropriate, ranging from issuance of a warning to the offending individual to expulsion from the conference with no refund, depending on the circumstances. FOSSASIA reserves the right to exclude any participant found to be engaging in harassing behavior from participating in any further FOSSASIA events, trainings or other activities.</p>
                <p>Conference staff will also provide support to victims, including, but not limited to:</p>
                <ul>
                    <li>Providing an Escort</li>
                    <li>Contacting Hotel/Venue Security or Local Law Enforcement</li>
                    <li>Briefing Key Event Staff For Response/Victim Assistance</li>
                    <li>And otherwise assisting those experiencing harassment to ensure that they feel safe for the duration of the conference.</li>
                </ul>

                <h2>Health and Safety Requirements</h2>
                <p>It is necessary for all attendees to cooperate and protect one another. For this reason, FOSSASIA’s events may have health and safety requirements (the “Health and Safety Requirements”). The specific requirements may vary from event to event, and will be communicated in writing prior to and during the event.</p>
                <p>If an attendee fails to comply with any of the Health and Safety Requirements, FOSSASIA may (but is not obligated to) take appropriate corrective action, which could include immediate removal from the event and venue without a refund, and/or imposing a temporary or permanent ban on an individual’s participation in future FOSSASIA events.</p>

                <h2>Pre-Event Concerns</h2>
                <p>If you are planning to attend an upcoming event, whether in-person or virtually and have concerns regarding another individual who may be present, please contact Angela Brown (angela@linuxfoundation.org). Precautions will be taken to ensure your comfort and safety, including, but not limited to providing an escort, prepping onsite event staff, keeping victim and harasser from attending the same talks/social events and providing onsite contact cell phone numbers for immediate contact.</p>
            </div>
        </div>
    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>