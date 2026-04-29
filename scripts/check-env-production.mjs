import { existsSync } from 'fs';
import { resolve } from 'path';

const file = resolve(process.cwd(), 'build_hosting', '.env.production');
if (!existsSync(file)) {
    process.stderr.write(
        'Falta build_hosting/.env.production. Cree ese archivo (vea la sección “Build hosting” al final de .env.example) y ejecute npm run build:deploy.\n',
    );
    process.exit(1);
}
