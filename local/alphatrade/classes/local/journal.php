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

use context_user;
use moodle_url;
use stdClass;

/**
 * Trading journal: options, access and template data.
 *
 * @package   local_alphatrade
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class journal {

    /** @var string[] Asset classes (journal filters). */
    const ASSETCLASSES = ['forex', 'indices', 'crypto', 'gold', 'stocks', 'other'];

    /** @var string[] Emotional state => Phosphor icon (no emoji anywhere). */
    const EMOTIONS = [
        'calm' => 'ph-smiley',
        'neutral' => 'ph-smiley-meh',
        'confident' => 'ph-smiley-wink',
        'stressed' => 'ph-smiley-nervous',
        'frustrated' => 'ph-smiley-angry',
        'disappointed' => 'ph-smiley-sad',
    ];

    /** @var string File area for trade screenshots. */
    const FILEAREA = 'screenshot';

    /**
     * Options for selects.
     *
     * @param string $type assetclass|emotion
     * @return array
     */
    public static function options(string $type): array {
        $keys = $type === 'emotion' ? array_keys(self::EMOTIONS) : self::ASSETCLASSES;
        $options = [];
        foreach ($keys as $key) {
            $options[$key] = get_string($type . '_' . $key, 'local_alphatrade');
        }
        return $options;
    }

    /**
     * Owner or trainer.
     *
     * @param stdClass $entry
     * @return bool
     */
    public static function can_view(stdClass $entry): bool {
        global $USER;
        return $entry->userid == $USER->id || page::is_staff();
    }

    /**
     * Screenshot URLs of an entry.
     *
     * @param stdClass $entry
     * @return array
     */
    public static function get_screenshots(stdClass $entry): array {
        $context = context_user::instance($entry->userid, IGNORE_MISSING);
        if (!$context) {
            return [];
        }
        $urls = [];
        $files = get_file_storage()->get_area_files($context->id, 'local_alphatrade', self::FILEAREA, $entry->id,
            'filename', false);
        foreach ($files as $file) {
            if (!$file->is_valid_image()) {
                continue;
            }
            $urls[] = [
                'url' => moodle_url::make_pluginfile_url($context->id, 'local_alphatrade', self::FILEAREA, $entry->id,
                    $file->get_filepath(), $file->get_filename())->out(false),
                'name' => $file->get_filename(),
            ];
        }
        return $urls;
    }

    /**
     * One row of the journal list.
     *
     * @param stdClass $entry
     * @return array
     */
    public static function export_row(stdClass $entry): array {
        $result = $entry->resultr === null ? null : (float) $entry->resultr;
        return [
            'id' => $entry->id,
            'date' => userdate($entry->tradedate, '%d/%m'),
            'url' => (new moodle_url('/local/alphatrade/journalentry.php', ['id' => $entry->id]))->out(false),
            'asset' => page::display_symbol($entry->asset),
            'assetclass' => get_string('assetclass_' . $entry->assetclass, 'local_alphatrade'),
            'setup' => (string) $entry->setup,
            'islong' => $entry->direction === 'long',
            'direction' => get_string('direction_' . $entry->direction, 'local_alphatrade'),
            'result' => page::format_r($result),
            'resultclass' => page::value_class($result),
            'hasemotion' => !empty($entry->emotion) && isset(self::EMOTIONS[$entry->emotion]),
            'emotion' => !empty($entry->emotion) && isset(self::EMOTIONS[$entry->emotion])
                ? get_string('emotion_' . $entry->emotion, 'local_alphatrade') : '',
            'emotionicon' => self::EMOTIONS[$entry->emotion] ?? '',
        ];
    }
}
