# AGENTS.md

WordPress plugin (PHP 7.4+, WordPress 5.8+) integrating Eventyay events into WordPress via page templates, Gutenberg blocks, and shortcodes.

JavaScript tooling is optional. Run npm commands only when `package.json` is present or JavaScript files are being modified.

## Setup

- composer install
- npm install              # if JavaScript tooling is needed

## Run the tests

- composer test                               # full PHPUnit suite
- composer test -- --testsuite Unit           # unit tests only
- composer test -- --testsuite Integration    # integration tests only (needs local WP test env)
- composer setup-tests                        # bootstrap the local WordPress test DB (bin/install-wp-tests.sh)

## Lint & static analysis

- composer phpcs            # WordPress Coding Standards (phpcs.xml)
- composer phpcbf           # auto-fix PHPCS violations
- composer phpstan          # static analysis (phpstan.neon.dist)
- npm run lint              # ESLint (only if package.json is present)
- npm run format            # Prettier (only if package.json is present)
- npm run check             # lint + phpcs + phpstan (only if package.json is present)

## Where things live

- wpfaevent.php — plugin bootstrap (entry point, defines WPFAEVENT_VERSION/PATH/URL)
- includes/ — core logic: class-wpfaevent.php (main class), class-wpfaevent-loader.php (hooks), class-wpfaevent-templates.php (page templates), cpt/, taxonomies/, meta/, helpers/, cache/, cli/, eventyay-importer/ (API client, repositories, JSON:API parsing)
- admin/ — admin settings pages, dashboard, Eventyay sync/importer UI, partner dashboard
- public/ — public-facing rendering: class-wpfaevent-public.php, partials/, templates/, css/, js/
- tests/ — PHPUnit; tests/unit/ (*Test.php); tests/integration/ (*IntegrationTest.php); suites are defined in phpunit.xml.dist.
- languages/ — i18n .pot file (Text Domain: wpfaevent)
- bin/install-wp-tests.sh — bootstraps the local WP test database

## Conventions

- Follow WordPress Coding Standards — enforced by `composer phpcs` (phpcs.xml); don't hand-format, run phpcbf.
- Keep PHPStan clean at the configured level (phpstan.neon.dist).
- All user-facing strings wrapped in `__()` / `_e()` with Text Domain `wpfaevent`.
- Core logic in includes/, presentation in public/partials/ and public/templates/ — don't mix business logic into templates.
- Custom capabilities (delete_events, delete_speakers, edit_events, edit_speakers, publish_events, publish_speakers) are listed in phpcs.xml for the WordPress.WP.Capabilities sniff — use them, don't invent new ad hoc capability strings.
- No committed real speaker/event images or large demo data — use placeholders only.
- If JS tooling (package.json) is present in the working tree, JS is linted via `@wordpress/eslint-plugin` and formatted via `@wordpress/prettier-config` — obey those configs, don't restate style rules.
- Keep styles in the appropriate CSS files; avoid inline CSS unless explicitly required.

## Existing patterns

Before implementing anything:

- Search for similar functionality before adding new code.
- Follow existing naming conventions.
- Reuse helper classes where possible.
- Extend existing hooks instead of introducing parallel systems.

## Guardrails

- Always: Read relevant files before editing. Follow the project conventions. Run the required validation commands before considering work complete.
- Ask first: running `composer test -- --testsuite Integration` against anything but a local/throwaway DB, adding new dependencies, changing phpcs.xml / phpstan.neon.dist / phpunit.xml.dist, touching bin/install-wp-tests.sh.
- Never:
  - commit vendor/, node_modules/, coverage/, or .phpunit.result.cache.
  - commit real speaker/event data or large binaries.
  - commit directly to main - branch and open a PR.
  - commit secrets or API endpoint credentials.
  - Update plugin or package version numbers unless explicitly requested.

## Definition of Done

Done when: `composer phpcs` exits 0 · `composer phpstan` exits 0 · `composer test` passes · (if package.json is present and JS files were modified) `npm run lint` exits 0 · changes committed on a feature branch with a clear message.

## When stuck

Ask a clarifying question, propose a short plan, or open a draft PR with notes - don't push large speculative changes across includes/, admin/, and public/ at once.

## Security & secrets

Eventyay API endpoint URLs and cache TTL are configured via **Settings → Event Plugin** in the WP admin, not committed to the repo. Never hardcode API credentials or real endpoint tokens in includes/ or admin/.

## Commit & PR

- Branch from `main`; never commit directly to `main`.
- Keep PRs focused and under ~300 lines where practical.
- Split unrelated changes into separate PRs.
- Before opening a PR, ensure the Definition of Done is satisfied.
- Keep translation strings wrapped correctly.
- Use Conventional Commit messages (e.g. `feat:`, `fix:`, `docs:`, `refactor:`).
