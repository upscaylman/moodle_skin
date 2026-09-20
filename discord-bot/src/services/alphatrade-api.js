// Client des services web Moodle. Moodle est la source de verite: le bot ne stocke rien
// et n'a aucune base. Chaque commande interroge cette API en temps reel.
import { config } from '../config/index.js';

/**
 * Appelle une fonction de service web Moodle.
 *
 * @param {string} wsfunction nom de la fonction
 * @param {object} params parametres
 * @returns {Promise<object>} la reponse decodee
 */
async function call(wsfunction, params) {
    const body = new URLSearchParams({
        wstoken: config.moodleToken,
        wsfunction,
        moodlewsrestformat: 'json',
        ...params,
    });
    const response = await fetch(config.moodleUrl + '/webservice/rest/server.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
    });
    if (!response.ok) {
        throw new Error('Moodle a repondu ' + response.status);
    }
    const data = await response.json();
    if (data && data.exception) {
        // Message neutre cote Discord: jamais de detail technique ni de signal de fraude.
        const error = new Error(data.errorcode === 'notlinked' ? 'notlinked' : 'api');
        error.code = data.errorcode;
        throw error;
    }
    return data;
}

export const api = {
    stats: (discorduserid) => call('local_alphatrade_referral_get_stats', { discorduserid }),
    link: (discorduserid) => call('local_alphatrade_referral_get_link', { discorduserid }),
    referrals: (discorduserid) => call('local_alphatrade_referral_get_referrals', { discorduserid }),
    rewards: (discorduserid) => call('local_alphatrade_referral_get_rewards', { discorduserid }),
};
