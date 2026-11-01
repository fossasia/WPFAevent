const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/js/wpfaevent-public.js'), 'utf8');

// Run the shipped script. Only event registration and browser side effects are substituted.
function clickNavigation({ theme = 'classic', nativeSmooth = false, reduced = false,
    href = 'https://example.test/event/#details', current = 'https://example.test/event/',
    event: eventOverrides = {}, link: linkOverrides = {}, tabindex } = {}) {
    let handler;
    let prevented = false;
    const scrolls = [];
    const focuses = [];
    const history = [];
    const attributes = new Map(tabindex === undefined ? [] : [['tabindex', tabindex]]);
    const listeners = new Map();
    const target = {
        tabIndex: tabindex === undefined ? -1 : Number(tabindex),
        hasAttribute: name => attributes.has(name),
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name),
        addEventListener: (name, listener) => listeners.set(name, listener),
        scrollIntoView: options => scrolls.push(options.behavior),
        focus: options => focuses.push(options.preventScroll),
    };
    const document = {
        documentElement: {}, scrollingElement: {},
        getElementById: id => id === 'details' ? target : null,
    };
    const window = {
        location: new URL(current), wpfaeventPublic: { themeType: theme },
        matchMedia: () => ({ matches: reduced }),
        getComputedStyle: () => ({ scrollBehavior: nativeSmooth ? 'smooth' : 'auto' }),
        history: { pushState: (state, title, url) => history.push(url) },
    };
    const collection = {
        length: 0,
        on(event, selector, callback) {
            if (event === 'click' && typeof selector === 'string' && selector.includes('.nav-links-main a')) {
                handler = callback;
            }
            return this;
        },
        each() { return this; },
    };
    const jQuery = value => {
        if (typeof value === 'function') value();
        return collection;
    };
    vm.runInNewContext(source, { jQuery, window, document, URL });
    const event = {
        button: 0, isDefaultPrevented: () => false,
        preventDefault: () => { prevented = true; }, ...eventOverrides,
    };
    const link = { href, target: '', hasAttribute: () => false, ...linkOverrides };
    // On the unfixed source no navigation handler is registered, so default navigation remains.
    if (handler) handler.call(link, event);
    return { prevented, scrolls, focuses, history, attributes, blur: listeners.get('blur') };
}

for (const theme of ['classic', 'block']) {
    test(theme + ': fallback scrolls and focuses the target', () => {
        const result = clickNavigation({ theme });
        assert.equal(result.prevented, true);
        assert.deepEqual(result.scrolls, ['smooth']);
        assert.deepEqual(result.focuses, [true]);
        assert.deepEqual(result.history, ['#details']);
        assert.equal(result.attributes.get('tabindex'), '-1');
        result.blur();
        assert.equal(result.attributes.has('tabindex'), false);
    });
    test(theme + ': preserves native smooth anchor navigation', () => {
        const result = clickNavigation({ theme, nativeSmooth: true });
        assert.equal(result.prevented, false);
        assert.deepEqual(result.scrolls, []);
        assert.deepEqual(result.history, []);
    });
    test(theme + ': reduced motion overrides native smooth behavior', () => {
        const result = clickNavigation({ theme, nativeSmooth: true, reduced: true });
        assert.equal(result.prevented, true);
        assert.deepEqual(result.scrolls, ['instant']);
    });
}

test('preserves an existing target tabindex', () => {
    const result = clickNavigation({ tabindex: '0' });
    assert.equal(result.attributes.get('tabindex'), '0');
    assert.equal(result.blur, undefined);
});
test('does not create duplicate history entries for the current fragment', () => {
    const result = clickNavigation({ current: 'https://example.test/event/#details' });
    assert.deepEqual(result.history, []);
    assert.deepEqual(result.scrolls, ['smooth']);
});

const untouched = [
    ['control-click', { event: { ctrlKey: true } }],
    ['command-click', { event: { metaKey: true } }],
    ['shift-click', { event: { shiftKey: true } }],
    ['alt-click', { event: { altKey: true } }],
    ['middle button', { event: { button: 1 } }],
    ['previously prevented click', { event: { isDefaultPrevented: () => true } }],
    ['named target', { link: { target: 'event-preview' } }],
    ['new window', { link: { target: '_blank' } }],
    ['download', { link: { hasAttribute: name => name === 'download' } }],
    ['other origin', { href: 'https://other.test/event/#details' }],
    ['other page', { href: 'https://example.test/other/#details' }],
    ['other query', { href: 'https://example.test/event/?custom_page=travel#details' }],
    ['missing target', { href: 'https://example.test/event/#missing' }],
    ['no fragment', { href: 'https://example.test/event/' }],
    ['malformed fragment', { href: 'https://example.test/event/#%zz' }],
    ['malformed configured URL', { href: 'http://[invalid/#details' }],
];
for (const [name, options] of untouched) {
    test('leaves ' + name + ' to the browser', () => {
        const result = clickNavigation(options);
        assert.equal(result.prevented, false);
        assert.deepEqual(result.scrolls, []);
        assert.deepEqual(result.focuses, []);
        assert.deepEqual(result.history, []);
    });
}
