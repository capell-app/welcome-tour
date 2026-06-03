# Welcome Tour — Improvement & Growth Plan
> Package: capell-app/welcome-tour · Kind: package · Tier: free · Product group: Capell Foundation · Bundle: foundation · Status: Draft

## 1. Snapshot

Welcome Tour ships an optional, admin-only guided onboarding overlay for Capell Admin, built on `jibaymcs/filament-tour`. It registers the tour plugin via `WelcomeTourPanelExtender`, replaces the dashboard with `WelcomeTourDashboard` (route `/admin`), and renders a single multi-step tour built from steps stored in `WelcomeTourSettings::steps` (falling back to `config/capell-welcome-tour.php`). Steps are package-registerable from anywhere via `CapellAdmin::registerWelcomeTourStep(...)` — the admin manager `vendor/capell-app/admin/src/Concerns/HasWelcomeTours.php` filters `isVisible()` and sorts by `sort` asc — and the local `WelcomeTourStepRegistrar` translates/escapes the configured rows and re-registers them on each dashboard render (`src/Filament/Pages/WelcomeTourDashboard.php:38`). Per-user state is a single binary dismissal flag persisted into the host `users.dismissed_hints` JSON column (`src/Actions/Users/CanShowWelcomeTourAction.php`, `SetUserWelcomeTourPreferenceAction.php`), surfaced as a `welcome_tour_enabled` toggle on the user edit form (`src/Support/WelcomeTourUserResourceBridge.php`). Only surface is `admin`; depends on `capell-app/admin` + `jibaymcs/filament-tour`; declares `migrations: false`, `settings: true`, `permissions: []`, `capabilities: []`. Marketplace summary (verbatim): **"Welcome Tour provides optional guided onboarding for the Capell admin panel."** — identical to both the capell.json top-level `description` and the composer `description`. capell.json declares **1** marketplace screenshot (`docs/assets/marketplace/extension-card.jpg`), whereas `docs/screenshots.json` defines **4** required captures (dashboard, overlay, settings, user toggle) — a media mismatch.

## 2. Improvements (existing functionality)

- **Make tour persistence resilient when `users.dismissed_hints` is absent** — When the host users table lacks `dismissed_hints`, `CanShowWelcomeTourAction` returns `true` unconditionally and `SetUserWelcomeTourPreferenceAction` silently no-ops (`src/Actions/Users/CanShowWelcomeTourAction.php:30`, `SetUserWelcomeTourPreferenceAction.php:18`). The tour then re-shows on every dashboard load and cannot be dismissed per user. Either ship an opt-in migration that adds `dismissed_hints`, or fall back to a package-owned `welcome_tour_completions` table / cache key so dismissal sticks regardless of host schema. — why: core promise (per-user dismiss) is unmet on any host without that column — effort: M

- **Stop re-registering steps on every render; register once at boot** — `WelcomeTourDashboard::tours()` calls `resolve(WelcomeTourStepRegistrar::class)->register()` on each component render (`src/Filament/Pages/WelcomeTourDashboard.php:38`), repeatedly resolving settings and re-pushing into the admin manager's keyed array. Move registration into the provider boot (or guard against double registration) so the dashboard only *reads* `getWelcomeTourSteps()`. — why: removes per-request settings resolution + redundant work, aligns with how other packages register steps at boot — effort: S

- **Drop the double-escape on descriptions** — `WelcomeTourStepRegistrar::safeDescription()` returns `e(__(...))` (`src/Support/WelcomeTourStepRegistrar.php:120`) and the docs example tells code-side callers to also wrap in `e(...)` (`docs/steps-and-settings.md:46`). `filament-tour` renders the description string; legitimate copy containing apostrophes/ampersands (e.g. "site's content & media") renders as literal `&#039;` / `&amp;`. Escape once, at the render boundary, and document a single rule. — why: visible mojibake in normal localized copy — effort: S

- **Honour the `enabled`/`visible` settings consistently** — `WelcomeTourStepRegistrar::booleanValue()` parses string booleans for `visible`, but the per-step `visible` is only enforced downstream by the admin manager. The global `enabled` flag is read in two places with diverging fallbacks (`WelcomeTourSettings` vs `config('capell-welcome-tour.enabled')`). Centralize the "is the tour on?" decision in a single Action so settings and config can't drift. — why: avoids subtle on/off inconsistencies between settings and config — effort: S

- **Provide at least one real anchored step out of the box** — All four default steps set `element => null` (`config/capell-welcome-tour.php`), so the shipped tour is centered modal cards, not anchored tooltips, despite the package being pitched as "guided." Add 1–2 verified, stable selectors (e.g. the sidebar nav `.fi-sidebar-nav`, already used in tests) so the default experience demonstrates true element targeting. — why: default UX undersells the feature; "guided tooltips" are advertised but not shipped — effort: S

- **Localize the dashboard tour ID / button copy fallbacks** — Button labels pull `capell-admin::button.*` (`src/Filament/Pages/WelcomeTourDashboard.php:53-55`); fine, but the tour has no per-tour title/intro localized beyond steps. Surface a configurable welcome headline so owners can brand the first card without editing steps. — why: low-effort branding lever for owners — effort: S

## 3. Missing Features (gaps)

`capabilities: []` is empty, so the manifest advertises nothing the package actually does — every item below is currently unrepresented.

- **Per-step completion / progress tracking (differentiator).** State is one binary dismissed-hint key (`CanShowWelcomeTourAction::DISMISSED_HINT_KEY`). There is no "step 3 of 5 reached", no resume-where-you-left-off across sessions, no per-step completion record. An onboarding product is expected to know *how far* a user got. Gap vs table-stakes onboarding tooling.
- **Onboarding checklist surface.** Tours are transient overlays; there is no persistent "Getting started" checklist widget on the dashboard that tracks tasks (create a site, add a page, set a theme) with completion ticks. This is the highest-leverage retention feature and is absent.
- **Multiple / contextual tours beyond the dashboard.** Only `WelcomeTourDashboard` hosts a tour, and it's hard-bound to `route('/admin')`. `registerWelcomeTourStep` is catalogue-wide capable, but no resource/page-specific tours exist (e.g. a Pages tour, a Media tour). Coverage does not span the catalogue.
- **First-run vs role-based targeting.** `permissions: []`; the tour shows to every admin who hasn't dismissed it, regardless of role. No "show only to users created in the last N days", no per-role step visibility, no targeting by Filament Shield role.
- **Dismiss vs "remind me later" / re-trigger.** Dismissal is permanent (until an admin toggles the user form). There is no "Restart tour" action in the user menu or dashboard, and no soft "snooze".
- **Other-package step contribution helper.** Docs show `CapellAdmin::registerWelcomeTourStep` for other packages, but there is no published contract/helper or example registrar that sibling packages can reuse — so catalogue-wide onboarding coverage is theoretical.
- **Analytics / telemetry.** No event is emitted on tour start, step advance, or completion, so owners cannot measure activation impact (the package's whole value proposition).

## 4. Issues / Risks

- **Per-user state silently broken without host column (correctness).** As in §2 #1 — `src/Actions/Users/CanShowWelcomeTourAction.php:30` returns `true` and `SetUserWelcomeTourPreferenceAction.php:18` returns early when `dismissed_hints` is missing. Tests always run with the column present (`tests/Feature/WelcomeTourTest.php`), so this path is never exercised. Test gap + real regression risk on lean hosts.
- **Health check is a stub vs. its declared severity (manifest mismatch).** `WelcomeTourHealthCheck` implements only `compatibleCapellApiVersion(): '^4.0'` (`src/Health/WelcomeTourHealthCheck.php`) yet capell.json labels it *critical* and claims it verifies that "surfaces, providers, and install health are discoverable by Diagnostics." It verifies none of that. Either downgrade severity/label or implement real checks (plugin registered, dashboard swapped, settings resolvable).
- **README structure references nonexistent code.** README "Code Map" and "Data And Persistence" point to `packages/welcome-tour/src/Data` and "Data objects live in `src/Data/`" — there is no `src/Data` directory; the only Data object (`WelcomeTourStepData`) lives in `vendor/capell-app/admin`. The "Extension Points" section is generic template boilerplate ("routes, migrations, render hooks") for a package that registers none of those. Doc rot / template leakage.
- **Dashboard takeover side effect.** `CapellAdmin::useDashboardPage(WelcomeTourDashboard::class)` (`src/Providers/WelcomeTourServiceProvider.php:67`) unconditionally replaces the admin dashboard whenever the package is installed — even for hosts that only wanted step registration. If another package also wants the dashboard, last-writer-wins with no conflict surfacing.
- **i18n coverage.** Only `resources/lang/en/` ships (`welcome_tour.php`, `package.php`). Tour copy is correctly translation-key based, but no other locales are provided, and the settings repeater asks owners for raw translation keys (`step_title => 'Title translation key'`) — high-friction for non-developer owners who just want to type a sentence. Consider accepting literal strings *or* keys.
- **Performance budget realism.** capell.json sets `adminQueryBudget: 40`; the only query is the `dismissed_hints` lookup per render, so this is comfortably met, but re-registering steps every render (§2 #2) is wasted CPU under the budget rather than a query cost. `frontendRenderBudgetMs: 0` is correct (admin-only, no public output).
- **`CHANGELOG.md` is a placeholder.** Single "Unreleased — Prepared package metadata and documentation" line; no versioned history to anchor support.

## 5. Marketplace & Positioning

Welcome Tour is foundation/bundled and free — correctly so. Its real job is **activation and retention**: a smooth first-run reduces early churn and support load, which is exactly the kind of soft value a free foundation package should contribute to the platform pitch ("Capell gets new admins productive on day one").

**Current copy (all three identical):** "Welcome Tour provides optional guided onboarding for the Capell admin panel." — accurate but self-referential ("Welcome Tour provides…"), feature-named rather than benefit-led, and gives no sense of configurability or per-user control.

**Improved `marketplace.summary`:** "Guided, in-product onboarding for Capell Admin — configurable multi-step tours that introduce new editors to sites, pages, media, and settings, with per-user dismiss and resume."

**Improved composer `description`:** "Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel."

**Media gaps:** capell.json lists 1 screenshot but `docs/screenshots.json` defines 4 required captures; promote the overlay + settings + user-toggle shots into the marketplace `screenshots[]` so the listing shows the feature in action rather than a single card. A short GIF of the tour advancing would convert far better than a static image for an onboarding product.

**Platform-pitch contribution:** position as the "day-one activation" piece of the Foundation bundle; pair it explicitly with Diagnostics (health) and Translation Manager (localized copy) in cross-sell.

**Keywords/tags (8–12):** `capell`, `cms`, `laravel`, `filament`, `onboarding`, `user-onboarding`, `product-tour`, `guided-tour`, `walkthrough`, `tooltips`, `admin-ux`, `activation`. (Current set is only `capell, cms, laravel, onboarding`.)

## 6. Prioritized Roadmap

| Item | Bucket | Effort | Impact | Section ref |
| --- | --- | --- | --- | --- |
| Resilient persistence when `dismissed_hints` absent (migration or package-owned store) | Now | M | High | §2, §4 |
| Implement real `WelcomeTourHealthCheck` or fix severity/label | Now | S | High | §4 |
| Register steps once at boot, not per render | Now | S | Med | §2 |
| Fix README `src/Data` + generic "Extension Points" doc rot | Now | S | Med | §4 |
| Remove description double-escape; single escape rule | Now | S | Med | §2 |
| Populate `capabilities[]` in capell.json | Now | S | Med | §3 |
| Ship ≥1 anchored default step (real tooltip) | Next | S | Med | §2 |
| Rewrite marketplace summary + composer description; expand keywords | Next | S | High | §5 |
| Promote 3–4 screenshots (and a GIF) into marketplace media | Next | S | High | §5 |
| Add "Restart tour" action + per-user resume | Next | M | High | §3 |
| Per-step completion / progress tracking | Next | M | High | §3 |
| Onboarding checklist dashboard widget | Later | L | High | §3 |
| Role/first-run targeting for steps | Later | M | Med | §3 |
| Contextual tours on Pages/Media/Sites + reusable contribution helper | Later | L | Med | §3 |
| Tour analytics events (start/advance/complete) | Later | M | Med | §3 |
| Accept literal step copy (not just translation keys) in settings | Later | M | Med | §4 |
