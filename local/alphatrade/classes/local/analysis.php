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

use stdClass;

/**
 * Chart analysis submissions: options and template data (student and teacher views).
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analysis {

    /** @var string[] */
    const BIASES = ['bullish', 'bearish', 'neutral'];

    /** @var string[] Correction criteria columns. */
    const CRITERIA = ['critstructure', 'critliquidity', 'critbias', 'critentry'];

    /**
     * Radio options for the bias.
     *
     * @param string $selected
     * @return array
     */
    public static function bias_options(string $selected): array {
        $icons = ['bullish' => 'ph-trend-up', 'bearish' => 'ph-trend-down', 'neutral' => 'ph-arrows-left-right'];
        $options = [];
        foreach (self::BIASES as $bias) {
            $options[] = [
                'value' => $bias,
                'label' => get_string('bias_' . $bias, 'local_alphatrade'),
                'icon' => $icons[$bias],
                'checked' => $bias === $selected,
            ];
        }
        return $options;
    }

    /**
     * Submission (answers + correction when reviewed) for templates.
     *
     * @param stdClass $submission
     * @param stdClass $chart
     * @param \context $context
     * @return array
     */
    public static function export_submission(stdClass $submission, stdClass $chart, \context $context): array {
        $text = function(?string $value): string {
            return nl2br(s((string) $value));
        };
        $data = [
            'structure' => $text($submission->structure),
            'liquidity' => $text($submission->liquidity),
            'scenario' => $text($submission->scenario),
            'bias' => $submission->bias ? get_string('bias_' . $submission->bias, 'local_alphatrade') : '-',
            'submitted' => userdate($submission->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
            'isreviewed' => $submission->status === 'reviewed',
        ];
        if ($data['isreviewed']) {
            $levels = [
                2 => ['class' => 'is-done', 'icon' => 'ph-fill ph-check-circle', 'label' => get_string('crit_right', 'local_alphatrade')],
                1 => ['class' => 'is-warning', 'icon' => 'ph-fill ph-warning-circle', 'label' => get_string('crit_partial', 'local_alphatrade')],
                0 => ['class' => 'is-danger', 'icon' => 'ph-fill ph-x-circle', 'label' => get_string('crit_wrong', 'local_alphatrade')],
            ];
            $data['criteria'] = [];
            foreach (self::CRITERIA as $criterion) {
                $level = (int) ($submission->$criterion ?? 0);
                $data['criteria'][] = array_merge($levels[$level] ?? $levels[0], [
                    'name' => get_string($criterion, 'local_alphatrade'),
                ]);
            }
            $data['score'] = (int) $submission->score;
            $data['feedback'] = $text($submission->feedback);
            $data['hasfeedback'] = trim((string) $submission->feedback) !== '';
            $data['correction'] = format_text($chart->correction, $chart->correctionformat, ['context' => $context]);
            $data['hascorrection'] = trim(strip_tags((string) $chart->correction)) !== '';
            $data['reviewed'] = $submission->timereviewed
                ? userdate($submission->timereviewed, get_string('strftimedatetimeshort', 'langconfig')) : '';
        }
        return $data;
    }
}
