import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { test } from 'node:test';
import assert from 'node:assert/strict';

const updateEventLocations = runInNewContext(
    `${readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8')}\nupdateEventLocations;`,
    { document: { addEventListener() {} } },
);

function formFor(ids, locationIds, selected = '', existing = {}) {
    let value = selected;
    const options = ['', ...locationIds].map(id => ({
        value: id,
        disabled: false,
        hidden: false,
        get selected() { return value === id; },
    }));
    const location = {
        options,
        get value() { return value; },
        set value(next) { value = next; },
    };
    const event = { value: 'event-1', selectedOptions: [{ dataset: { locationIds: ids.join(',') } }] };
    const status = { textContent: '' };
    const form = {
        dataset: existing,
        querySelector(selector) {
            return {
                '[name="special_event_id"]': event,
                '[name="location_id"]': location,
                '[data-event-location-status]': status,
            }[selector];
        },
    };
    return { form, event, location, status };
}

test('a single assigned location is selected for a preselected event', () => {
    const { form, location } = formFor(['1'], ['1', '2']);
    updateEventLocations(form);
    assert.equal(location.value, '1');
});

test('a single assigned location replaces an incompatible remembered location', () => {
    const { form, location, status } = formFor(['1'], ['1', '2'], '2');
    updateEventLocations(form);
    assert.equal(location.value, '1');
    assert.equal(location.options[2].disabled, true);
    assert.match(status.textContent, /selected automatically/);
});

test('multiple locations and unrestricted events do not select a location', () => {
    for (const ids of [['1', '2'], []]) {
        const { form, location } = formFor(ids, ['1', '2']);
        updateEventLocations(form);
        assert.equal(location.value, '');
    }
});

test('an unavailable assigned location is not selected', () => {
    const { form, location, status } = formFor(['1'], ['2']);
    updateEventLocations(form);
    assert.equal(location.value, '');
    assert.match(status.textContent, /No assigned locations are available/);
});

test('editing a historical entry preserves its original location', () => {
    const { form, location, status } = formFor(['1'], ['1', '2'], '2', {
        existingEvent: 'event-1',
        existingLocation: '2',
    });
    updateEventLocations(form);
    assert.equal(location.value, '2');
    assert.match(status.textContent, /original location/);
});
