import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import vm from 'node:vm'

const source = readFileSync(0, 'utf8')
function run({ missing = false, invalid = false, active = true } = {}) {
    const handlers = new Map()
    const events = new Map()
    const dispatched = []
    let removed = 0
    let closed = 0
    const data = { suppressDismiss: false }
    const document = {
        body: { classList: { contains: () => active } },
        querySelector(selector) {
            if (selector === '.driver-popover-close-btn') {
                return {
                    click() {
                        closed++
                        events.get('click')?.({
                            target: { closest: () => true },
                        })
                    },
                }
            }
            if (invalid) throw new SyntaxError('Invalid selector')
            return missing ? null : {}
        },
        addEventListener(event, handler, options) {
            events.set(event, handler)
            options?.signal?.addEventListener('abort', () =>
                events.delete(event),
            )
        },
    }
    vm.runInNewContext(source, {
        document,
        window: { location: { href: 'http://localhost/' } },
        $data: data,
        AbortController,
        requestAnimationFrame: (callback) => callback(),
        queueMicrotask: (callback) => callback(),
        Livewire: {
            on(event, handler) {
                handlers.set(event, handler)
                return () => {
                    removed++
                    handlers.delete(event)
                }
            },
            dispatch(event, payload) {
                if (event === 'filament-tour::load-elements') return
                dispatched.push([event, payload])
            },
        },
    })
    return {
        handlers,
        events,
        dispatched,
        get removed() {
            return removed
        },
        get closed() {
            return closed
        },
    }
}
const loaded = 'filament-tour::loaded-elements'
const tours = [{ id: 'tour_capell_admin_welcome.dashboard' }]
const healthy = run()
healthy.handlers.get(loaded)({ tours: [{ id: 'other' }] })
assert.equal(healthy.dispatched.length, 0)
healthy.handlers.get(loaded)({ tours })
assert.equal(healthy.dispatched[0][0], 'filament-tour::open-tour')
assert.equal(healthy.dispatched[0][1].id, 'capell_admin_welcome.dashboard')
assert.equal(healthy.removed, 1)
for (const options of [{ missing: true }, { invalid: true }]) {
    const broken = run(options)
    broken.handlers.get(loaded)({ tours })
    assert.deepEqual(
        broken.dispatched.map(([event]) => event),
        ['capell-welcome-tour::target-unavailable'],
    )
    assert.equal(broken.closed, 1)
}
const keyboard = run()
keyboard.events.get('keydown')({ key: 'Enter' })
assert.equal(keyboard.dispatched.length, 0)
keyboard.events.get('keydown')({ key: 'Escape' })
assert.equal(keyboard.dispatched[0][0], 'capell-welcome-tour::dismiss')
const inactive = run({ active: false })
inactive.events.get('keydown')({ key: 'Escape' })
assert.equal(inactive.dispatched.length, 0)
keyboard.events.get('livewire:navigating')()
assert.equal(keyboard.events.size, 0)
assert.equal(keyboard.handlers.size, 0)
console.log('DOM controller: 13 assertions passed')
