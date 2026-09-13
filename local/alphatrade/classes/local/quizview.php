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

namespace local_alphatrade\local;

use cm_info;
use moodle_page;
use moodle_url;

/**
 * Maquette "Évaluation": question counter on the attempt page, result card on the review page.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizview {

    /**
     * Extra template data for the quiz header.
     *
     * @param moodle_page $page
     * @param cm_info $cm
     * @param int $userid
     * @return array
     */
    public static function context(moodle_page $page, cm_info $cm, int $userid): array {
        global $CFG;

        $data = ['hasquestion' => false, 'hasresult' => false];
        $pagetype = $page->pagetype;
        $attemptid = $page->url ? (int) $page->url->get_param('attempt') : 0;
        if (!$attemptid || !in_array($pagetype, ['mod-quiz-attempt', 'mod-quiz-review'])) {
            return $data;
        }

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->libdir . '/gradelib.php');
        try {
            $attempt = \mod_quiz\quiz_attempt::create($attemptid);
        } catch (\Throwable $e) {
            return $data;
        }
        if ($attempt->get_userid() != $userid) {
            return $data;
        }

        $slots = array_values($attempt->get_slots());
        $total = count($slots);
        if (!$total) {
            return $data;
        }

        if ($pagetype === 'mod-quiz-attempt') {
            $pageslots = $attempt->get_slots((int) $page->url->get_param('page'));
            $first = $pageslots ? reset($pageslots) : $slots[0];
            $index = array_search($first, $slots);
            $index = $index === false ? 1 : $index + 1;
            $data['hasquestion'] = true;
            $data['questioncounter'] = get_string('questioncounter', 'local_alphatrade', ['index' => $index, 'total' => $total]);
            $data['questionpercent'] = (int) round(100 * $index / $total);
            return $data;
        }

        if ($attempt->is_finished()) {
            $quiz = $attempt->get_quiz();
            $marks = $attempt->get_sum_marks();
            if ($marks === null || $quiz->sumgrades <= 0) {
                return $data;
            }
            $percent = (int) round(100 * $marks / $quiz->sumgrades);
            $grade = quiz_rescale_grade($marks, $quiz, false);
            $gradeitem = \grade_item::fetch(['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id,
                'courseid' => $quiz->course]);
            $passed = $gradeitem && $gradeitem->gradepass > 0 ? $grade >= $gradeitem->gradepass : $percent >= 50;

            $lesson = programme::lesson_context($cm, $userid);
            $data['hasresult'] = true;
            $data['result'] = [
                'score' => page::num((float) $marks, 0) . ' / ' . page::num((float) $quiz->sumgrades, 0),
                'percent' => $percent . ' %',
                'passed' => $passed,
                'label' => get_string($passed ? 'quizpassed' : 'quizfailed', 'local_alphatrade'),
                'nexturl' => $passed && $lesson ? $lesson['nexturl'] : ($lesson ? $lesson['moduleurl']
                    : (new moodle_url('/local/alphatrade/parcours.php'))->out(false)),
                'nextlabel' => get_string($passed ? 'continueparcours' : 'reviewlessons', 'local_alphatrade'),
            ];
        }
        return $data;
    }
}
