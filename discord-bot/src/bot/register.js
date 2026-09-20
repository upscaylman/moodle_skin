// Enregistre les commandes slash aupres de Discord. A lancer une fois, puis a chaque changement
// de la liste des commandes: npm run register
import { REST, Routes } from 'discord.js';
import { commands } from '../commands/index.js';
import { config } from '../config/index.js';

const rest = new REST().setToken(config.token);
const body = commands.map((command) => command.data.toJSON());

const route = config.guildId
    ? Routes.applicationGuildCommands(config.clientId, config.guildId)
    : Routes.applicationCommands(config.clientId);

await rest.put(route, { body });
console.log(body.length + ' commande(s) enregistree(s).');
