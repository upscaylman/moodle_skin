// Les cinq commandes slash du parrainage. Elles ne font que mettre en forme ce que Moodle renvoie.
import { SlashCommandBuilder } from 'discord.js';
import { api } from '../services/alphatrade-api.js';
import { config } from '../config/index.js';

const NOT_LINKED = 'Votre compte Discord n est pas encore lie a Alpha Trade. Ouvrez votre tableau de bord '
    + 'Parrainage sur la plateforme, puis cliquez sur « Lier mon compte Discord ».';

/**
 * Repond en prive: les chiffres de parrainage ne s affichent jamais dans un salon public.
 *
 * @param {object} interaction interaction Discord
 * @param {Function} build fonction qui produit le texte
 */
async function reply(interaction, build) {
    await interaction.deferReply({ ephemeral: true });
    try {
        await interaction.editReply(await build(interaction.user.id));
    } catch (error) {
        await interaction.editReply(error.message === 'notlinked'
            ? NOT_LINKED
            : 'Service indisponible pour le moment. Reessayez dans quelques minutes.');
    }
}

export const commands = [
    {
        data: new SlashCommandBuilder().setName('parrainage')
            .setDescription('Votre lien de parrainage et vos chiffres'),
        run: (interaction) => reply(interaction, async (id) => {
            const [link, stats] = await Promise.all([api.link(id), api.stats(id)]);
            return [
                '**Votre lien** : ' + link.link,
                '**Clics** : ' + stats.clicks + '  ·  **Filleuls** : ' + stats.referrals
                    + '  ·  **Conversions** : ' + stats.conversions,
                '**Gagne** : ' + stats.earned.toFixed(2) + ' ' + stats.currency
                    + '  ·  **En attente** : ' + stats.pending.toFixed(2) + ' ' + stats.currency,
            ].join('\n');
        }),
    },
    {
        data: new SlashCommandBuilder().setName('lien').setDescription('Votre lien de parrainage'),
        run: (interaction) => reply(interaction, async (id) => {
            const link = await api.link(id);
            return 'Votre code : **' + link.code + '**\n' + link.link;
        }),
    },
    {
        data: new SlashCommandBuilder().setName('statistiques')
            .setDescription('Vos statistiques de parrainage'),
        run: (interaction) => reply(interaction, async (id) => {
            const stats = await api.stats(id);
            return [
                'Clics : **' + stats.clicks + '**',
                'Filleuls : **' + stats.referrals + '**',
                'Conversions : **' + stats.conversions + '**',
                'Gagne : **' + stats.earned.toFixed(2) + ' ' + stats.currency + '**',
                'En attente : **' + stats.pending.toFixed(2) + ' ' + stats.currency + '**',
            ].join('\n');
        }),
    },
    {
        data: new SlashCommandBuilder().setName('filleuls').setDescription('La liste de vos filleuls'),
        run: (interaction) => reply(interaction, async (id) => {
            const rows = await api.referrals(id);
            if (!rows.length) {
                return 'Aucun filleul pour le moment. Partagez votre lien avec /lien.';
            }
            return rows.map((row) => '· ' + row.name + ' — ' + row.status + ' (' + row.date + ')').join('\n');
        }),
    },
    {
        data: new SlashCommandBuilder().setName('aide-parrainage')
            .setDescription('Comment fonctionne le parrainage'),
        run: (interaction) => reply(interaction, async () => [
            '**Comment ca marche**',
            '1. Partagez votre lien (/lien).',
            '2. La personne cree son compte apres avoir suivi le lien : elle devient votre filleule, definitivement.',
            '3. Quand son paiement est confirme, une conversion est enregistree.',
            '4. Apres le delai de verification, votre recompense est approuvee.',
            '',
            'Votre tableau de bord complet : ' + config.moodleUrl + '/local/alphatrade_referral/index.php',
        ].join('\n')),
    },
];
