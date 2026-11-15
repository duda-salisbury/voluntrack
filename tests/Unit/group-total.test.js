import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const listeners = {};
runInNewContext(readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8'), {
    document: { addEventListener(name, callback) { listeners[name] = callback; } },
});

function groupForm() {
    const date = { value: '2026-11-15' };
    const location = { value: '2', options: [{ value: '' }, { value: '1' }, { value: '2' }, { value: '3' }] };
    const participants = { value: '12', checkValidity() { return Number(this.value) >= 1 && Number(this.value) <= 100000; } };
    const perPerson = { value: '3' };
    const total = { value: '', min: '0.01', max: '99999.99' };
    const status = { textContent: '' };
    const form = {
        querySelector(selector) {
            return {
                '[name="volunteered_at"]': date, '[name="location_id"]': location,
                '[name="participant_count"]': participants, '#hours-per-participant': perPerson,
                '[name="hours"]': total, '[data-calculation-status]': status,
            }[selector];
        },
    };
    const event = {
        value: '1', selectedOptions: [{ dataset: { eventDate: '2026-10-01', locationIds: '1' } }],
        closest(selector) { return selector === '[data-group-total]' ? form : null; },
        matches(selector) { return selector === '[name="special_event_id"]'; },
    };
    const button = { closest() { return form; } };
    return { form, date, location, participants, perPerson, total, status, event, button };
}

test('selecting an event defaults its date and single location without preventing overrides', () => {
    const context = groupForm();
    listeners.change({ target: context.event });
    assert.equal(context.date.value, '2026-10-01');
    assert.equal(context.location.value, '1');
    context.date.value = '2026-10-02';
    context.location.value = '3';
    assert.equal(context.location.value, '3');
    assert.equal(context.date.value, '2026-10-02');
    context.event.selectedOptions = [{ dataset: { eventDate: '2026-10-10', locationIds: '1,2' } }];
    listeners.change({ target: context.event });
    assert.equal(context.date.value, '2026-10-10');
    assert.equal(context.location.value, '');
    assert.equal(context.location.options[3].disabled, undefined);
});

test('calculator sets a total only for valid participant and per-person hours', async () => {
    const context = groupForm();
    const target = { closest: selector => selector === '[data-calculate-group-total]' ? context.button : null };
    await listeners.click({ target });
    assert.equal(context.total.value, '36.00');
    context.participants.value = '';
    await listeners.click({ target });
    assert.equal(context.total.value, '36.00');
    assert.match(context.status.textContent, /valid participant count/);
    context.participants.value = '100000';
    await listeners.click({ target });
    assert.equal(context.total.value, '36.00');
    assert.match(context.status.textContent, /calculated total must be/);
    context.participants.value = '12';
    context.perPerson.value = '25';
    await listeners.click({ target });
    assert.equal(context.total.value, '36.00');
    assert.match(context.status.textContent, /24 hours/);
});
