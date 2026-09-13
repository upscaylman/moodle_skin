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
 * Teacher form: create or edit a chart analysis exercise.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('charttitle', 'local_alphatrade'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'symbol', get_string('symbol', 'local_alphatrade'), ['size' => 30]);
        $mform->setType('symbol', PARAM_TEXT);
        $mform->addRule('symbol', null, 'required', null, 'client');
        $mform->addHelpButton('symbol', 'symbol', 'local_alphatrade');

        $mform->addElement('select', 'timeframe', get_string('timeframe', 'local_alphatrade'), page::timeframes());
        $mform->setDefault('timeframe', '60');

        $editoroptions = ['maxfiles' => 0, 'trusttext' => false];
        $mform->addElement('editor', 'instructions_editor', get_string('instructions', 'local_alphatrade'), null, $editoroptions);
        $mform->setType('instructions_editor', PARAM_RAW);

        $mform->addElement('editor', 'correction_editor', get_string('referencecorrection', 'local_alphatrade'), null, $editoroptions);
        $mform->setType('correction_editor', PARAM_RAW);
        $mform->addHelpButton('correction_editor', 'referencecorrection', 'local_alphatrade');

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_alphatrade'), ['size' => 4]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $mform->addElement('advcheckbox', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);

        $this->add_action_buttons();
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
        if (page::clean_symbol($data['symbol']) === '') {
            $errors['symbol'] = get_string('invalidsymbol', 'local_alphatrade');
        }
        return $errors;
    }
}
