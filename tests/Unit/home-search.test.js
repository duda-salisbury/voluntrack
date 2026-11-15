import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const listeners = {};
const classes = () => ({
    values: new Set(['d-none']),
    add(value) { this.values.add(value); },
    remove(value) { this.values.delete(value); },
});
const searchError = { classList: classes() };
const requestError = { classList: classes() };
const results = { id: 'org-results', cleared: 0, replaceChildren() { this.cleared++; } };
const form = { matches: selector => selector === '[data-organization-search]' };
const input = { id: 'org-search', value: '', form };
const elements = { 'org-search': input, 'org-results': results, 'search-error': searchError, 'request-error': requestError };
let aborts = 0;
runInNewContext(readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8'), {
    document: {
        addEventListener(name, callback) { listeners[name] = callback; },
        getElementById(id) { return elements[id]; },
    },
    window: { htmx: { trigger(target, name) { if (target === form && name === 'htmx:abort') aborts++; } } },
});

test('search requests require two trimmed characters', () => {
    for (const [value, expected] of [[' A ', true], ['😀', true], ['AB', false]]) {
        input.value = value;
        const event = { detail: { target: results }, prevented: false, preventDefault() { this.prevented = true; } };
        listeners['htmx:beforeRequest'](event);
        assert.equal(event.prevented, expected);
    }
});

test('changing the search clears stale results and aborts an in-flight request', () => {
    const previous = results.cleared;
    listeners.input({ target: input });
    assert.equal(results.cleared, previous + 1);
    assert.equal(aborts, 1);
    assert.equal(searchError.classList.values.has('d-none'), true);
});

test('search failures show an error by the search instead of the global error', () => {
    searchError.classList.add('d-none');
    listeners['htmx:sendError']({ detail: { target: results } });
    assert.equal(searchError.classList.values.has('d-none'), false);
    assert.equal(requestError.classList.values.has('d-none'), true);
});
