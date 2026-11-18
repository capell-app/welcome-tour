# Welcome Tour

<!-- prettier-ignore-start -->

A new editor opens the admin dashboard for the first time and wonders where to start. A Getting started checklist runs across the top, covering your site and domain, a first page, a theme and a first package. Each job has an Open link to the screen where the work is done.

Saving the site, a page appearing, a theme other than the default being switched on and a package being enabled after install each tick a job for you. Each person can also tick a job by hand. Jobs a person has no permission to open don't appear for them. Editors and every kind of admin see the checklist, and visitors never do.

Owners can also write tour steps, the notes a tour shows on each screen, in the Welcome tour settings. None come ready-made, and the checklist works without them. Each person can hide the checklist for themselves, and until they do it shows at a glance which jobs are done and which are still open.

## What it does

Welcome Tour adds guided onboarding to Capell Admin. It shows a configurable dashboard tour, tracks each user's completed steps, and adds a Getting started checklist for the first site, page, and enabled non-default theme.

## Setup requirements

Package installation must run both the package migration and its settings migration. Per-user progress is stored in `welcome_tour_user_states`; dismissal can also use the host users table's `dismissed_hints` column when it exists. Diagnostics reports a failure if neither dismissal storage path is available.

## Your screens

- **Welcome tour settings**: enable the shared dashboard tour and define its steps.
- **Dashboard**: shows the tour when the shared setting and the user's preference allow it. The Getting started checklist is a separate role- and widget-settings-gated dashboard contribution.
- **User edit form**: includes **Show welcome tour**, which lets an administrator enable or disable the tour for that user.

## What you can do

- Turn the shared welcome tour on or off.
- Edit each step's stable key, title, description, order, visibility, icon, and optional CSS selector.
- Limit a step to particular roles or users created within a chosen number of days.
- Use **Restart tour** from the dashboard to reset your own tour progress, or **Remind me later** to pause it until the following day.

## Where to find it

Go to **Welcome tour** in the admin settings. Open the dashboard to use the tour controls and see the onboarding checklist. The **Show welcome tour** preference is on an existing user's edit form.

## Good to know

- The package ships no tour steps. Add steps in the Welcome tour settings and order them to match the site's onboarding sequence.
- Keep each step key stable after users begin the tour. Progress is recorded by key, so changing a key makes the renamed step appear new to those users.
- Step copy can be literal text or a translation key. A CSS selector anchors a step to an element in the admin interface; leave it blank for an unanchored step.
- Role targets accept comma-separated role names. The new-user window is evaluated from the user's creation time whenever the tour is built; an incomplete step stops being eligible once that window has passed.
- Progress, dismissal, and snooze state are per user. Completing the tour dismisses it for that user; **Restart tour** clears that state and starts it again on the next dashboard load.
- **Remind me later** snoozes only the current user's dashboard tour for 24 hours. Disabling the shared setting prevents every user from seeing it, even if their own preference is enabled.
- A user can change their own tour state from the dashboard. Only a global admin can change another user's state through the user edit form.
- Checklist completion is installation-wide: it checks whether the Sites and Pages tables contain any rows and whether a non-default theme is enabled. It is not scoped to the current user or assigned site.
- A deliberately empty list of shared tour steps stays empty; the package does not fill it with steps of its own. Contextual tours come from package configuration or extension contributions, rather than this settings form, and none are configured by default.
- Diagnostics checks the dashboard integration, settings, checklist contribution, and per-user state storage.

---

For how to use Welcome Tour, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
