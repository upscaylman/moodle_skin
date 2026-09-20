// Point d entree du bot. Aucune permission Administrator: le bot se limite a lire les commandes
// et a repondre en prive, plus les roles qu on lui confie explicitement.
import { Client, Events, GatewayIntentBits } from 'discord.js';
import { commands } from '../commands/index.js';
import { config } from '../config/index.js';

const client = new Client({ intents: [GatewayIntentBits.Guilds] });
const byName = new Map(commands.map((command) => [command.data.name, command]));

client.once(Events.ClientReady, (ready) => {
    console.log('Bot parrainage connecte: ' + ready.user.tag);
});

client.on(Events.InteractionCreate, async (interaction) => {
    if (!interaction.isChatInputCommand()) {
        return;
    }
    const command = byName.get(interaction.commandName);
    if (!command) {
        return;
    }
    try {
        await command.run(interaction);
    } catch (error) {
        console.error('Commande ' + interaction.commandName + ' en echec', error);
    }
});

client.login(config.token);
