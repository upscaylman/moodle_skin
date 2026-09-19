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

/**
 * Espace Partenaire : « Suivre → Accompagner → Orienter → Mesurer ». Lecture seule sur les élèves
 * attribués : qui sont mes élèves, où en sont-ils, qui a besoin d'attention (architecture § 5 et 7).
 * Le partenaire ne corrige jamais et ne modifie aucun contenu.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_alphatrade\local\page;
use local_alphatrade\local\partner;
use local_alphatrade\local\programme;

$view = optional_param('view', '', PARAM_ALPHA);
$view = in_array($view, ['students', 'courses', 'progress']) ? $view : '';

$keys = ['' => 'partner', 'students' => 'partnerstudents', 'courses' => 'partnercourses', 'progress' => 'partnerprogress'];
$titles = [
    '' => ['partner_home', 'sub_partnerhome'],
    'students' => ['partner_students', 'sub_partnerstudents'],
    'courses' => ['partner_courses', 'sub_partnercourses'],
    'progress' => ['partner_progress', 'sub_partnerprogress'],
];
page::setup('/local/alphatrade/partner.php', $keys[$view], get_string($titles[$view][0], 'local_alphatrade'),
    $view ? ['view' => $view] : [], get_string($titles[$view][1], 'local_alphatrade'));

$course = programme::get_course();
if (!$course) {
    throw new moodle_exception('noprogrammecourse', 'local_alphatrade');
}
require_capability('local/alphatrade:viewpartner', context_course::instance($course->id));

$data = partner::export($USER->id, $view);
$data['isdashboard'] = $view === '';
$data['isstudents'] = $view === 'students';
$data['iscourses'] = $view === 'courses';
$data['isprogress'] = $view === 'progress';
$data['studentsurl'] = (new moodle_url('/local/alphatrade/partner.php', ['view' => 'students']))->out(false);
$data['progressurl'] = (new moodle_url('/local/alphatrade/partner.php', ['view' => 'progress']))->out(false);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_alphatrade/partner', $data);
echo $OUTPUT->footer();
