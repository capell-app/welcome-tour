<div
    x-data="{
        closeListener: null,
        init() {
            if (@js($autoStart)) {
                setTimeout(
                    () => Livewire.dispatch('filament-tour::open-tour', { id: 'capell_admin_welcome.dashboard' }),
                    window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 450,
                )
            }

            this.closeListener = (event) => {
                if (event.target.closest('.driver-popover-close-btn')) {
                    Livewire.dispatch('capell-welcome-tour::dismiss')
                }
            }

            document.addEventListener('click', this.closeListener, true)
        },
        destroy() {
            document.removeEventListener('click', this.closeListener, true)
        },
    }"
></div>
