import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const listeners = {};
const { updateEventLocations, updateVolunteerContext } = runInNewContext(
    `${readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8')}\n({ updateEventLocations, updateVolunteerContext });`,
    { document: { addEventListener(name, callback) { listeners[name] = callback; } } },
);

function contextForm({ location = '1', date = '2026-10-01', errors = false } = {}) {
    let locationValue = location;
    const form = { dataset: { hasErrors: String(errors) } };
    const options = ['', '1', '2'].map(value => ({
        value,
        textContent: { '': '', '1': 'Forest', '2': 'Park' }[value],
        disabled: false,
        hidden: false,
        get selected() { return locationValue === value; },
    }));
    const locationInput = {
        name: 'location_id', options,
        get value() { return locationValue; },
        set value(value) { locationValue = value; },
        get selectedOptions() { return options.filter(option => option.selected); },
        checkValidity() { return !!this.value; },
    };
    const dateInput = {
        name: 'volunteered_at', value: date, max: '2026-11-15',
        checkValidity() { return !!this.value && this.value <= this.max; },
        reportValidity() { this.reported = true; },
    };
    const eventOptions = {
        '1': { dataset: { eventName: 'Cleanup', eventDate: '2026-10-01', locationIds: '1' } },
        '2': { dataset: { eventName: 'Workday', eventDate: '2026-11-01', locationIds: '2' } },
        '3': { dataset: { eventName: 'Future', eventDate: '2026-12-01', locationIds: '2' } },
        '4': { dataset: { eventName: 'Several locations', eventDate: '2026-11-02', locationIds: '1,2' } },
    };
    const eventInput = {
        name: 'special_event_id', value: '1',
        get selectedOptions() { return [eventOptions[this.value]]; },
        focus() {},
    };
    const summary = { hidden: true };
    const fields = {
        hidden: false,
        querySelector() { return !dateInput.checkValidity() ? dateInput : (!locationInput.checkValidity() ? locationInput : null); },
    };
    const toggle = { textContent: '', setAttribute(name, value) { this[name] = value; }, closest() { return form; } };
    const title = { textContent: '' };
    const detail = { textContent: '' };
    const dateStatus = { textContent: '' };
    const locationStatus = { textContent: '' };
    const elements = {
        '[name="special_event_id"]': eventInput, '[name="location_id"]': locationInput,
        '[name="volunteered_at"]': dateInput, '[data-context-summary]': summary,
        '[data-context-fields]': fields, '[data-context-toggle]': toggle,
        '[data-context-event]': title, '[data-context-detail]': detail,
        '[data-context-date-status]': dateStatus, '[data-event-location-status]': locationStatus,
    };
    form.querySelector = selector => elements[selector];
    for (const input of [eventInput, locationInput, dateInput]) {
        input.closest = selector => ['[data-event-locations]', '[data-volunteer-context]'].includes(selector) ? form : null;
        input.matches = selector => selector.split(',').some(part => part.trim() === `[name="${input.name}"]`);
    }
    return { form, eventInput, locationInput, dateInput, summary, fields, toggle, title, detail, dateStatus };
}

test('complete event context collapses while validation errors remain editable', () => {
    const complete = contextForm();
    updateVolunteerContext(complete.form, true);
    assert.equal(complete.fields.hidden, true);
    assert.equal(complete.summary.hidden, false);
    assert.equal(complete.title.textContent, 'Cleanup');
    assert.match(complete.detail.textContent, /Forest/);

    const invalid = contextForm({ errors: true });
    updateVolunteerContext(invalid.form, true);
    assert.equal(invalid.fields.hidden, false);
    assert.equal(invalid.dateInput.value, '2026-10-01');
});

test('changing events updates date and location while allowing overrides', async () => {
    const context = contextForm();
    updateVolunteerContext(context.form, true);
    await listeners.click({ target: { closest: selector => selector === '[data-context-toggle]' ? context.toggle : null } });
    assert.equal(context.fields.hidden, false);
    context.eventInput.value = '2';
    listeners.change({ target: context.eventInput });
    assert.equal(context.dateInput.value, '2026-11-01');
    assert.equal(context.locationInput.value, '2');
    assert.equal(context.title.textContent, 'Workday');
    context.dateInput.value = '2026-10-31';
    listeners.change({ target: context.dateInput });
    assert.match(context.detail.textContent, /31/);
    await listeners.click({ target: { closest: selector => selector === '[data-context-toggle]' ? context.toggle : null } });
    assert.equal(context.fields.hidden, true);
});

test('future dates and events with multiple locations remain editable', async () => {
    const futureLink = contextForm({ date: '2026-12-01' });
    updateVolunteerContext(futureLink.form, true);
    assert.equal(futureLink.fields.hidden, false);
    assert.match(futureLink.dateStatus.textContent, /future/);

    const context = contextForm();
    updateEventLocations(context.form);
    context.eventInput.value = '3';
    listeners.change({ target: context.eventInput });
    assert.equal(context.dateInput.value, '2026-12-01');
    assert.match(context.dateStatus.textContent, /future/);
    await listeners.click({ target: { closest: selector => selector === '[data-context-toggle]' ? context.toggle : null } });
    assert.equal(context.fields.hidden, false);
    assert.equal(context.dateInput.reported, true);

    context.eventInput.value = '4';
    listeners.change({ target: context.eventInput });
    assert.equal(context.locationInput.value, '');
    assert.equal(context.fields.hidden, false);
});
