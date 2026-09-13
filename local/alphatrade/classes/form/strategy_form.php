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

use local_alphatrade\local\page;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Backtesting Lab: create or edit a strategy.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class strategy_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('strategyname', 'local_alphatrade'), ['size' => 40,
            'placeholder' => get_string('strategyname_ph', 'local_alphatrade')]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'market', get_string('market', 'local_alphatrade'), ['size' => 24,
            'placeholder' => 'FX:EURUSD']);
        $mform->setType('market', PARAM_TEXT);
        $mform->addRule('market', null, 'required', null, 'client');
        $mform->addHelpButton('market', 'symbol', 'local_alphatrade');

        $mform->addElement('select', 'timeframe', get_string('timeframe', 'local_alphatrade'), page::timeframes());
        $mform->setDefault('timeframe', '60');

        $mform->addElement('text', 'period', get_string('period', 'local_alphatrade'), ['size' => 24,
            'placeholder' => get_string('period_ph', 'local_alphatrade')]);
        $mform->setType('period', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('strategyrules', 'local_alphatrade'), ['rows' => 5, 'cols' => 60]);
        $mform->setType('description', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savestrategy', 'local_alphatrade'));
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (page::clean_symbol($data['market']) === '') {
            $errors['market'] = get_string('invalidsymbol', 'local_alphatrade');
        }
        return $errors;
    }
}
