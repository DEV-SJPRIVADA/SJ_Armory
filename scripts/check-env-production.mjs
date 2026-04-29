import { existsSync } from 'fs';
import { resolve } from 'path';

const file = resolve(process.cwd(), '.env.production');
if (!existsSync(file)) {
    process.stderr.write(
        'Falta .env.production. Copie .env.production.example → .env.production y defina VITE_PUSHER_APP_KEY.\n',
    );
    process.exit(1);
}
