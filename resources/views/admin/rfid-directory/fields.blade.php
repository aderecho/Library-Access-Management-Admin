                                        <fieldset class="rfid-edit-section">
                                            <legend>RFID and access</legend>
                                            <div class="rfid-edit-fields">
                                        <label class="rfid-field-wide">RFID
                                            <input name="rfid_code" value="{{ $record->rfid_code }}" maxlength="255" autocomplete="off" required autofocus>
                                        </label>
                                        <label>Status
                                            <input name="status" value="{{ $record->status }}" maxlength="255" required>
                                        </label>
                                        <label>Active
                                            <select name="is_active">
                                                <option value="1" @selected($record->is_active)>Active</option>
                                                <option value="0" @selected(! $record->is_active)>Inactive</option>
                                            </select>
                                        </label>
                                            </div>
                                        </fieldset>
                                        <fieldset class="rfid-edit-section">
                                            <legend>Personal information</legend>
                                            <div class="rfid-edit-fields">
                                        <label class="rfid-field-wide">{{ $record->cardholder_type === 'student' ? 'Campus ID' : 'Employee No.' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'campus_id' : 'employee_number' }}" value="{{ $record->identifier }}" maxlength="255" required>
                                        </label>
                                        @foreach(['first_name' => 'First Name', 'middle_name' => 'Middle Name', 'last_name' => 'Last Name', 'suffix' => 'Suffix'] as $field => $label)
                                            <label>{{ $label }}
                                                <input name="{{ $field }}" value="{{ $record->{$field} }}" maxlength="255" @required(in_array($field, ['first_name', 'last_name']))>
                                            </label>
                                        @endforeach
                                            </div>
                                        </fieldset>
                                        <fieldset class="rfid-edit-section">
                                            <legend>{{ $record->cardholder_type === 'student' ? 'Academic details' : 'Employment details' }}</legend>
                                            <div class="rfid-edit-fields">
                                        <label>{{ $record->cardholder_type === 'student' ? 'Program' : 'Position' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'program' : 'position' }}" value="{{ $record->primary_detail }}" maxlength="255">
                                        </label>
                                        <label>{{ $record->cardholder_type === 'student' ? 'College' : 'Office' }}
                                            <input name="{{ $record->cardholder_type === 'student' ? 'college' : 'office' }}" value="{{ $record->secondary_detail }}" maxlength="255">
                                        </label>
                                        @if($record->cardholder_type === 'student')
                                            <label>Year Level
                                                <input name="year_level" value="{{ $record->year_level }}" maxlength="255">
                                            </label>
                                        @endif
                                            </div>
                                        </fieldset>
