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
 * Webhook de paiement : la plateforme de paiement annonce un paiement confirmé ou un
 * remboursement. Idempotent par orderid (ADR-7) : le même appel répété ne crée qu'une conversion.
 *
 * Authentification par en-tête « Authorization: Bearer <REFERRAL_API_SECRET> », comparé en temps
 * constant. Le secret vient d'une variable d'environnement, jamais du code ni de Git.
 *
 * Corps attendu (JSON) :
 *   {"event": "payment.completed", "orderid": "ORDER-123", "userid": 42,
 *    "amount": 740, "currency": "EUR", "courseid": 2}
 *   {"event": "payment.refunded", "orderid": "ORDER-123", "reason": "demande client"}
 *
 * @package   local_alphatrade_referral
 * @copyright 2026 Alpha Trade
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
require(__DIR__ . '/../../config.php');

use local_alphatrade_referral\local\engine;

header('Content-Type: application/json; charset=utf-8');

/**
 * Réponse JSON et fin de traitement.
 *
 * @param int $status
 * @param array $body
 * @return void
 */
function local_atref_respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    die();
}

$secret = engine::config('apisecret');
$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($secret === '' || !preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)
        || !hash_equals($secret, trim($matches[1]))) {
    local_atref_respond(401, ['error' => 'unauthorized']);
}

if (!engine::enabled()) {
    local_atref_respond(503, ['error' => 'disabled']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || empty($payload['event']) || empty($payload['orderid'])) {
    local_atref_respond(400, ['error' => 'bad_request']);
}
$orderid = clean_param((string) $payload['orderid'], PARAM_TEXT);

if ($payload['event'] === 'payment.refunded') {
    $conversion = $DB->get_record('local_atref_conversions', ['dedupekey' => 'payment:' . $orderid]);
    if (!$conversion) {
        local_atref_respond(200, ['status' => 'ignored']);
    }
    engine::reverse((int) $conversion->id, (string) ($payload['reason'] ?? 'webhook'));
    local_atref_respond(200, ['status' => 'reversed', 'conversionid' => (int) $conversion->id]);
}

if ($payload['event'] !== 'payment.completed' || empty($payload['userid'])) {
    local_atref_respond(400, ['error' => 'unsupported_event']);
}

$conversion = engine::record_conversion((int) $payload['userid'], [
    'dedupekey' => 'payment:' . $orderid,
    'orderid' => $orderid,
    'conversiontype' => 'payment',
    'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
    'currency' => isset($payload['currency']) ? clean_param($payload['currency'], PARAM_ALPHA) : null,
    'courseid' => isset($payload['courseid']) ? (int) $payload['courseid'] : null,
]);

// Pas de conversion : soit le filleul n'a pas de parrain, soit l'événement est déjà traité.
// Dans les deux cas la réponse est 200, pour que la plateforme de paiement ne rejoue pas.
local_atref_respond(200, $conversion
    ? ['status' => 'recorded', 'conversionid' => (int) $conversion->id]
    : ['status' => 'ignored']);
