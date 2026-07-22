<div
    x-data
    x-init="if (@js($autoStart)) setTimeout(() => Livewire.dispatch('filament-tour::open-tour', { id: 'capell_admin_welcome.dashboard' }), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 450); document.addEventListener('click', (event) => { if (event.target.closest('.driver-popover-close-btn')) Livewire.dispatch('capell-welcome-tour::dismiss') }, true)"
></div>
