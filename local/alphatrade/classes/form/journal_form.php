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

use local_alphatrade\local\journal;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Trading journal entry: the trade, "before the trade", "after the trade".
 * Links psychology, trading and numbers in one record.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class journal_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'edit', 1);
        $mform->setType('edit', PARAM_INT);

        // The trade.
        $mform->addElement('header', 'tradeheader', get_string('journal_trade', 'local_alphatrade'));
        $mform->setExpanded('tradeheader');

        $mform->addElement('date_time_selector', 'tradedate', get_string('tradedate', 'local_alphatrade'));

        $mform->addElement('text', 'asset', get_string('asset', 'local_alphatrade'), ['size' => 16, 'placeholder' => 'EURUSD']);
        $mform->setType('asset', PARAM_TEXT);
        $mform->addRule('asset', null, 'required', null, 'client');
        $mform->addRule('asset', get_string('maximumchars', '', 32), 'maxlength', 32, 'client');

        $mform->addElement('select', 'assetclass', get_string('assetclass', 'local_alphatrade'), journal::options('assetclass'));

        $mform->addElement('select', 'direction', get_string('direction', 'local_alphatrade'), [
            'long' => get_string('direction_long', 'local_alphatrade'),
            'short' => get_string('direction_short', 'local_alphatrade'),
        ]);

        $mform->addElement('text', 'setup', get_string('setup', 'local_alphatrade'), ['size' => 30,
            'placeholder' => get_string('setup_ph', 'local_alphatrade')]);
        $mform->setType('setup', PARAM_TEXT);
        $mform->addRule('setup', get_string('maximumchars', '', 128), 'maxlength', 128, 'client');

        foreach (['entry', 'stoploss', 'takeprofit', 'riskpct', 'resultr'] as $field) {
            $mform->addElement('text', $field, get_string($field, 'local_alphatrade'), ['size' => 12, 'inputmode' => 'decimal']);
            $mform->setType($field, PARAM_RAW_TRIMMED);
        }
        $mform->addHelpButton('resultr', 'resultr', 'local_alphatrade');

        // Before the trade.
        $mform->addElement('header', 'beforeheader', get_string('journal_before', 'local_alphatrade'));
        $mform->setExpanded('beforeheader');
        $mform->addElement('textarea', 'reason', get_string('journal_reason', 'local_alphatrade'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('reason', PARAM_TEXT);
        $mform->addElement('textarea', 'marketcontext', get_string('journal_context', 'local_alphatrade'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('marketcontext', PARAM_TEXT);

        // After the trade.
        $mform->addElement('header', 'afterheader', get_string('journal_after', 'local_alphatrade'));
        $mform->setExpanded('afterheader');
        $mform->addElement('select', 'followedplan', get_string('journal_followedplan', 'local_alphatrade'), [
            '' => get_string('choosedots'),
            '1' => get_string('yes'),
            '0' => get_string('no'),
        ]);
        $mform->addElement('select', 'emotion', get_string('emotion', 'local_alphatrade'),
            ['' => get_string('choosedots')] + journal::options('emotion'));
        $mform->addElement('textarea', 'review', get_string('journal_review', 'local_alphatrade'), ['rows' => 3, 'cols' => 60]);
        $mform->setType('review', PARAM_TEXT);
        $mform->addElement('filemanager', 'screenshot_filemanager', get_string('screenshot', 'local_alphatrade'), null,
            self::filemanager_options());

        $this->add_action_buttons(true, get_string('savetrade', 'local_alphatrade'));
    }

    /**
     * Screenshot upload options.
     *
     * @return array
     */
    public static function filemanager_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 2,
            'maxbytes' => 5 * 1024 * 1024,
            'accepted_types' => ['web_image'],
        ];
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
        foreach (['entry', 'stoploss', 'takeprofit', 'riskpct', 'resultr'] as $field) {
            if ($data[$field] !== '' && trade_form::to_float($data[$field]) === null) {
                $errors[$field] = get_string('invalidnumber', 'local_alphatrade');
            }
        }
        return $errors;
    }
}
