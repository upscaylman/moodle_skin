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
 * Teacher form: correct a chart analysis (criteria, score, feedback).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class review_form extends \moodleform {

    /** @var string[] Correction criteria (wireframe v2: structure, liquidity, bias, entry). */
    const CRITERIA = ['critstructure', 'critliquidity', 'critbias', 'critentry'];

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $levels = [
            2 => get_string('crit_right', 'local_alphatrade'),
            1 => get_string('crit_partial', 'local_alphatrade'),
            0 => get_string('crit_wrong', 'local_alphatrade'),
        ];
        foreach (self::CRITERIA as $criterion) {
            $mform->addElement('select', $criterion, get_string($criterion, 'local_alphatrade'), $levels);
            $mform->setDefault($criterion, 2);
        }

        $mform->addElement('text', 'score', get_string('scoreout100', 'local_alphatrade'), ['size' => 4]);
        $mform->setType('score', PARAM_INT);
        $mform->addRule('score', null, 'required', null, 'client');
        $mform->addRule('score', null, 'numeric', null, 'client');

        $mform->addElement('textarea', 'feedback', get_string('feedback', 'local_alphatrade'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('feedback', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savereview', 'local_alphatrade'));
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
        if ($data['score'] < 0 || $data['score'] > 100) {
            $errors['score'] = get_string('scorerange', 'local_alphatrade');
        }
        return $errors;
    }
}
