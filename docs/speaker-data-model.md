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

When an event is saved, the event-speaker sync flow should keep both sides aligned:

1. Read the previous `wpfa_event_speakers` value.
2. Sanitize and save the current speaker IDs on the event.
3. Add the event ID to every newly assigned speaker's `wpfa_speaker_events`.
4. Remove the event ID from speakers that were removed from the event.

Speaker profile pages use `wpfa_speaker_events` first and also check `wpfa_event_speakers` as a fallback so older data can still render linked events.

## Session Data

There is not yet a reusable Session CPT or session relationship model. Until that exists, the speaker profile displays the interim `wpfa_speaker_talk_*` metadata as "Sessions by this speaker".

When a reusable session data model is added, speaker profiles should move from the interim talk fields to a dedicated session relationship and keep these fields only for migration/backward compatibility.
