<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_alphatrade\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Backtesting Lab: add one historical trade (date, direction, entry, SL, TP, result in R).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class trade_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'addtrade');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('date_selector', 'tradedate', get_string('tradedate', 'local_alphatrade'));

        $mform->addElement('select', 'direction', get_string('direction', 'local_alphatrade'), [
            'long' => get_string('direction_long', 'local_alphatrade'),
            'short' => get_string('direction_short', 'local_alphatrade'),
        ]);

        foreach (['entry', 'stoploss', 'takeprofit'] as $field) {
            $mform->addElement('text', $field, get_string($field, 'local_alphatrade'), ['size' => 12, 'inputmode' => 'decimal']);
            $mform->setType($field, PARAM_RAW_TRIMMED);
        }

        $mform->addElement('text', 'resultr', get_string('resultr', 'local_alphatrade'), ['size' => 8, 'inputmode' => 'decimal']);
        $mform->setType('resultr', PARAM_RAW_TRIMMED);
        $mform->addRule('resultr', null, 'required', null, 'client');
        $mform->addHelpButton('resultr', 'resultr', 'local_alphatrade');

        $mform->addElement('textarea', 'notes', get_string('notes', 'local_alphatrade'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('notes', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savetrade', 'local_alphatrade'));
    }

    /**
     * Validation: numbers accept "1,5" as well as "1.5".
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach (['entry', 'stoploss', 'takeprofit'] as $field) {
            if ($data[$field] !== '' && self::to_float($data[$field]) === null) {
                $errors[$field] = get_string('invalidnumber', 'local_alphatrade');
            }
        }
        $result = self::to_float($data['resultr']);
        if ($result === null || abs($result) > 100) {
            $errors['resultr'] = get_string('invalidresultr', 'local_alphatrade');
        }
        return $errors;
    }

    /**
     * Parse a localised decimal.
     *
     * @param string|null $value
     * @return float|null
     */
    public static function to_float(?string $value): ?float {
        $value = str_replace([' ', ','], ['', '.'], trim((string) $value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }
}
