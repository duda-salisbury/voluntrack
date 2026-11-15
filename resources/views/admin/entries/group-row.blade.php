<div class="group-row" data-group-row>
    <div><label class="form-label" for="group-name-{{ $index }}">Volunteer name</label><input id="group-name-{{ $index }}" name="rows[{{ $index }}][name]" class="form-control" value="{{ $row['name'] ?? '' }}" maxlength="255" required></div>
    <div><label class="form-label" for="group-hours-{{ $index }}">Hours</label><input id="group-hours-{{ $index }}" name="rows[{{ $index }}][hours]" class="form-control" type="number" inputmode="numeric" min="0" max="24" step="1" value="{{ $row['hours'] ?? '' }}" placeholder="0"></div>
    <div><label class="form-label" for="group-minutes-{{ $index }}">Minutes</label><input id="group-minutes-{{ $index }}" name="rows[{{ $index }}][minutes]" class="form-control" type="number" inputmode="numeric" min="0" max="59" step="1" value="{{ $row['minutes'] ?? '' }}" placeholder="0"></div>
    <button type="button" class="btn btn-outline-secondary" data-remove-group-row aria-label="Remove contribution">Remove</button>
</div>
