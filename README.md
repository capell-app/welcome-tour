# Capell Welcome Tour

Optional Filament welcome tour package for Capell Admin.

This package requires `capell-app/admin` and owns the `jibaymcs/filament-tour` integration. Installing it swaps the admin dashboard page for `WelcomeTourDashboard`, registers the Filament tour plugin through an admin panel extender, and exposes settings for enabling the tour and configuring the step sequence.

Tour step titles and descriptions are stored as translation keys by default, so sites can override copy through normal Laravel translation publishing. The bundled first step is:

- Title: `Welcome to Capell`
- Description: `This quick tour highlights the main admin areas you will use to manage sites and content.`

Settings are registered under the `welcome-tour` group and backed by `WelcomeTourSettings`.

Step descriptions are escaped before they are passed to the tour renderer. Default steps are modal-only; configured CSS selectors are opt-in.
