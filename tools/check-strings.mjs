// Checks that every language string used by theme_alphatrade and local_alphatrade exists in EN and FR,
// and that no copy contains an em dash or an emoji. Usage: node tools/check-strings.mjs
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const plugins = {
    theme_alphatrade: join(root, 'theme', 'alphatrade'),
    local_alphatrade: join(root, 'local', 'alphatrade'),
};

// Keys built dynamically in PHP (prefix + value).
const dynamic = {
    theme_alphatrade: [
        ...['home', 'parcours', 'practice', 'backtest', 'journal', 'tools', 'resources', 'community', 'profile', 'teacher',
            'reviews', 'studentbacktests', 'programmecourse', 'grades', 'siteadmin'].map((k) => 'nav_' + k),
        ...['programme', 'method', 'team', 'faq'].map((k) => 'pub_nav_' + k),
        ...['weeks', 'hours', 'modules', 'trainers'].map((k) => 'pub_fact_' + k),
        ...['theory', 'psychology', 'quant'].flatMap((k) => ['pub_pillar_' + k, 'pub_pillar_' + k + '_desc']),
        'pub_w1', 'pub_w3', 'pub_w4', 'pub_w5', 'pub_w6', 'pub_w7', 'pub_w8', 'pub_w9', 'pub_w10', 'pub_w11', 'pub_w12',
        'pub_month', 'pub_month1_theme', 'pub_month2_theme', 'pub_month3_theme',
        ...['understand', 'analyse', 'test', 'measure', 'build'].flatMap((k) => ['pub_method_' + k, 'pub_method_' + k + '_desc']),
        ...['nesrine', 'julien'].flatMap((k) => ['pub_team_' + k, 'pub_team_' + k + '_role', 'pub_team_' + k + '_bio',
            'pub_team_' + k + '_quote']),
        ...['who', 'capital', 'signals', 'rhythm', 'certificate', 'online'].flatMap((k) => ['pub_faq_' + k, 'pub_faq_' + k + '_answer']),
        'privacy:metadata', 'region-side-pre',
    ],
    local_alphatrade: [
        ...['done', 'current', 'todo', 'locked'].map((k) => 'status_' + k),
        'month1theme', 'month2theme', 'month3theme',
        ...['bullish', 'bearish', 'neutral'].map((k) => 'bias_' + k),
        ...['critstructure', 'critliquidity', 'critbias', 'critentry'],
        ...['forex', 'indices', 'crypto', 'gold', 'stocks', 'other'].map((k) => 'assetclass_' + k),
        ...['calm', 'confident', 'hesitant', 'stressed', 'frustrated', 'euphoric'].map((k) => 'emotion_' + k),
        'direction_long', 'direction_short',
        ...['risk', 'position', 'rr', 'stats'].flatMap((k) => ['tool_' + k, 'tool_' + k + '_desc']),
        ...['capital', 'riskpct', 'entry', 'stoploss', 'takeprofit', 'pointvalue'].map((k) => 'tool_field_' + k),
        ...['pdf', 'video', 'checklist', 'template'].flatMap((k) => ['restype_' + k, 'restypes_' + k]),
        ...['submitted', 'reviewed', 'all'].map((k) => 'filter_' + k),
        ...['cond_lessons', 'cond_quizzes', 'cond_challenges', 'cond_backtest', 'cond_project'],
        'verdict_small', 'verdict_positive', 'verdict_negative',
        'messageprovider:analysisreviewed', 'local/alphatrade:viewteacher', 'local/alphatrade:viewstudentdata',
        'local/alphatrade:reviewanalysis', 'local/alphatrade:managecharts',
        ...['userid', 'name', 'market', 'timeframe', 'period', 'description', 'timecreated', 'timemodified', 'strategyid',
            'tradedate', 'direction', 'entry', 'stoploss', 'takeprofit', 'resultr', 'notes', 'asset', 'assetclass', 'setup',
            'riskpct', 'emotion', 'reason', 'marketcontext', 'followedplan', 'review', 'chartid', 'structure', 'liquidity',
            'bias', 'scenario', 'status', 'score', 'feedback', 'timereviewed', 'local_alphatrade_strategy',
            'local_alphatrade_bttrade', 'local_alphatrade_journal', 'local_alphatrade_analysis', 'core_files',
            'core_message'].map((k) => 'privacy:metadata:' + k),
    ],
};

const scan = (dir, out = []) => {
    for (const name of readdirSync(dir)) {
        const path = join(dir, name);
        if (statSync(path).isDirectory()) {
            if (!['lang', 'fonts', 'pix', 'scss'].includes(name)) {
                scan(path, out);
            }
        } else if (/\.(php|mustache)$/.test(name)) {
            out.push(path);
        }
    }
    return out;
};

const used = { theme_alphatrade: new Set(dynamic.theme_alphatrade), local_alphatrade: new Set(dynamic.local_alphatrade) };
const patterns = [
    /get_string\(\s*'([^']+)'\s*,\s*'(theme_alphatrade|local_alphatrade)'/g,
    /\{\{#(?:str|cleanstr)\}\}\s*([a-z0-9_:\-\/]+)\s*,\s*(theme_alphatrade|local_alphatrade)/g,
    /string_exists\(\s*'([^']+)'\s*,\s*'(theme_alphatrade|local_alphatrade)'/g,
];
for (const dir of Object.values(plugins)) {
    for (const file of scan(dir)) {
        const source = readFileSync(file, 'utf8');
        for (const pattern of patterns) {
            for (const match of source.matchAll(pattern)) {
                used[match[2]].add(match[1]);
            }
        }
        for (const match of source.matchAll(/addHelpButton\(\s*'[^']+'\s*,\s*'([^']+)'\s*,\s*'(local_alphatrade|theme_alphatrade)'/g)) {
            used[match[2]].add(match[1]);
            used[match[2]].add(match[1] + '_help');
        }
    }
}

let errors = 0;
const emoji = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B50}\u{2705}\u{274C}]/u;
for (const [component, dir] of Object.entries(plugins)) {
    const langs = {};
    for (const lang of ['en', 'fr']) {
        const source = readFileSync(join(dir, 'lang', lang, component + '.php'), 'utf8');
        langs[lang] = new Set([...source.matchAll(/\$string\['([^']+)'\]/g)].map((m) => m[1]));
        if (source.includes('—')) {
            console.error(`${component} ${lang}: em dash found`);
            errors++;
        }
        if (emoji.test(source)) {
            console.error(`${component} ${lang}: emoji found`);
            errors++;
        }
    }
    for (const key of [...used[component]].sort()) {
        for (const lang of ['en', 'fr']) {
            if (!langs[lang].has(key)) {
                console.error(`MISSING ${lang} ${component}: ${key}`);
                errors++;
            }
        }
    }
    for (const key of langs.en) {
        if (!langs.fr.has(key)) {
            console.error(`MISSING fr ${component}: ${key} (present in en)`);
            errors++;
        }
    }
    const unused = [...langs.en].filter((key) => !used[component].has(key) && key !== 'pluginname');
    if (unused.length) {
        console.log(`${component}: ${unused.length} unused string(s): ${unused.join(', ')}`);
    }
    console.log(`${component}: ${used[component].size} strings used`);
}
process.exit(errors ? 1 : 0);
