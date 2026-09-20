// Configuration du bot: uniquement des variables d'environnement, aucun secret dans le code.
const required = (name) => {
    const value = process.env[name];
    if (!value) {
        throw new Error('Variable d environnement manquante: ' + name);
    }
    return value;
};

export const config = {
    token: required('DISCORD_BOT_TOKEN'),
    clientId: required('DISCORD_CLIENT_ID'),
    guildId: process.env.DISCORD_GUILD_ID || '',
    moodleUrl: (process.env.ALPHATRADE_URL || '').replace(/\/+$/, ''),
    moodleToken: required('ALPHATRADE_TOKEN'),
};
