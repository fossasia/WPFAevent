# Speaker Data Model

The speaker profile system uses the `wpfa_speaker` custom post type for public speaker pages and REST-accessible speaker data.

## Speaker Metadata

| Meta key | Type | Purpose |
| --- | --- | --- |
| `wpfa_speaker_position` | string | Speaker title or role. |
| `wpfa_speaker_organization` | string | Company, project, or organization name. |
| `wpfa_speaker_bio` | string | Speaker biography. |
| `wpfa_speaker_headshot_url` | string | Speaker image URL used when no featured image is available. |
| `wpfa_speaker_linkedin` | string | LinkedIn profile URL. |
| `wpfa_speaker_twitter` | string | Twitter/X profile URL. |
| `wpfa_speaker_github` | string | GitHub profile URL. |
| `wpfa_speaker_website` | string | Speaker website URL. |
| `wpfa_speaker_talk_title` | string | Interim session title for the speaker. |
| `wpfa_speaker_talk_date` | string | Interim session date. |
| `wpfa_speaker_talk_time` | string | Interim session start time. |
| `wpfa_speaker_talk_end_time` | string | Interim session end time. |
| `wpfa_speaker_talk_abstract` | string | Interim session abstract. |
| `wpfa_speaker_events` | array<int> | Event post IDs linked to the speaker. |

All speaker metadata above is registered through `register_post_meta()` and exposed through the WordPress REST API.

## Event Relationship

Events store their assigned speakers in `wpfa_event_speakers` as an array of `wpfa_speaker` post IDs. Speakers store their related events in `wpfa_speaker_events` as an array of `wpfa_event` post IDs.

When an event is saved, the event-speaker sync flow keeps both sides aligned:

1. Read the previous `wpfa_event_speakers` value and merge any reverse `wpfa_speaker_events` links already pointing to the event.
2. Sanitize and save the current speaker IDs on the event.
3. Add the event ID to every newly assigned speaker's `wpfa_speaker_events`.
4. Remove the event ID from speakers that were removed from the event.

The Speaker edit screen also exposes the same relationship from the speaker side. Saving the speaker's related events writes `wpfa_speaker_events` and syncs each selected or removed event's `wpfa_event_speakers` list.

Speaker profile pages merge `wpfa_speaker_events` with events that reference the speaker through `wpfa_event_speakers`, so older one-sided data can still render linked published events.

For CLI-based refreshes, `wp wpfa import` updates the existing imported events and speakers in place, including the speaker-event relationship data that the public templates read.

## Session Data

There is not yet a reusable Session CPT or session relationship model. Until that exists, the speaker profile displays the interim `wpfa_speaker_talk_*` metadata as "Sessions by this speaker".

When a reusable session data model is added, speaker profiles should move from the interim talk fields to a dedicated session relationship and keep these fields only for migration/backward compatibility.

## Eventyay Import Data Ownership

Imported Eventyay data is written to dashboard JSON under `uploads/fossasia-data/`. Reimports follow these ownership rules:

| Data | Reimport behaviour |
| --- | --- |
| Eventyay event metadata | Updated from Eventyay |
| Eventyay speaker (`source = eventyay`) | Updated from Eventyay; post status follows linked event (`publish` only when event is published, otherwise `draft`) |
| Manual speaker | Preserved and kept linked to the event |
| Eventyay sponsor group or sponsor (`source = eventyay`) | Replaced on reimport |
| Manual sponsor group | Preserved across reimport |
| Eventyay exhibitor (`source = eventyay`) | Replaced on reimport |
| Manual exhibitor | Preserved across reimport |
| Featured speaker flag set manually | Preserved; Eventyay featured speakers are merged without removing manual featured selections |
| Eventyay schedule (`source = eventyay`) | Replaced unless a manual schedule with a different source already exists |

Manual records are identified by the absence of `source = eventyay` on the stored group, sponsor, exhibitor, or speaker post metadata.
