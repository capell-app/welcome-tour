## What it does

Welcome Tour adds guided onboarding to Capell Admin. It shows a configurable dashboard tour, tracks each user's completed steps, and adds a Getting started checklist for the first site, page, and media upload.

## Your screens

- **Welcome tour settings**: enable the shared dashboard tour and define its steps.
- **Dashboard**: shows the tour and the Getting started checklist when the feature and the user's preference allow it.
- **User edit form**: includes **Show welcome tour**, which lets an administrator enable or disable the tour for that user.

## What you can do

- Turn the shared welcome tour on or off.
- Edit each step's stable key, title, description, order, visibility, icon, and optional CSS selector.
- Limit a step to particular roles or users created within a chosen number of days.
- Use **Restart tour** from the dashboard to reset your own tour progress, or **Remind me later** to pause it until the following day.

## Where to find it

Go to **Welcome tour** in the admin settings. Open the dashboard to use the tour controls and see the onboarding checklist. The **Show welcome tour** preference is on an existing user's edit form.

## Good to know

- The default tour has seven steps for the admin menu, header tools, Sites, Pages, Media, and dashboard. Replace or reorder them to match the site's onboarding sequence.
- Step copy can be literal text or a translation key. A CSS selector anchors a step to an element in the admin interface; leave it blank for an unanchored step.
- Progress, dismissal, and snooze state are per user. Completing the tour dismisses it for that user; **Restart tour** clears that state and starts it again on the next dashboard load.
- **Remind me later** snoozes only the current user's dashboard tour for 24 hours. Disabling the shared setting prevents every user from seeing it, even if their own preference is enabled.
- A deliberately empty list of shared tour steps stays empty; the package does not restore its defaults. Contextual tours for Sites, Pages, and Media come from package configuration or extension contributions, rather than this settings form.
- Diagnostics checks the dashboard integration, settings, checklist contribution, and per-user state storage.
