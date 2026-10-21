const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '../..');
const output = path.join(root, '.local', `cpanel-${new Date().toISOString().replace(/[:.]/g, '-')}`);
const app = path.join(output, 'application');
const web = path.join(output, 'public-files');
fs.mkdirSync(app, { recursive: true });
function copy(from, to) {
  const stat = fs.lstatSync(from);
  if (stat.isSymbolicLink()) return;
  if (stat.isDirectory()) {
    fs.mkdirSync(to, { recursive: true });
    for (const name of fs.readdirSync(from)) {
      if (['.git', '.local', '.codex', '.agents', '.aws', '.env', 'hot', 'node_modules'].includes(name)
          || name.startsWith('.env.') || /\.(sql|sqlite|zip|log|bak|key|pem|tmp)$/.test(name)) continue;
      copy(path.join(from, name), path.join(to, name));
    }
  } else fs.copyFileSync(from, to);
}
for (const dir of ['app', 'config', 'resources', 'routes', 'database/migrations']) copy(path.join(root, dir), path.join(app, dir));
for (const file of ['artisan', 'composer.json', 'composer.lock', 'bootstrap/app.php', 'bootstrap/providers.php', 'database/seeders/SubscriptionPackageSeeder.php']) {
  const dest = path.join(app, file);
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.copyFileSync(path.join(root, file), dest);
}
for (const dir of ['bootstrap/cache', 'storage/app/public', 'storage/app/private', 'storage/app/deployment', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'ops']) fs.mkdirSync(path.join(app, dir), { recursive: true });
fs.copyFileSync(path.join(root, '.env.production.example'), path.join(app, '.env.example'));
fs.copyFileSync(path.join(root, 'deployment/cpanel/setup.php'), path.join(app, 'ops/setup.php'));
fs.writeFileSync(path.join(app, 'ops/admin.json.example'), JSON.stringify({ name: '', email: '', password: '' }, null, 2));
copy(path.join(root, 'public'), web);
// This subdomain has a fixed public document root, separate from the private app.
const index = fs.readFileSync(path.join(web, 'index.php'), 'utf8').replaceAll("__DIR__.'/../", "'/home/limachlk/bookmystyle/");
fs.writeFileSync(path.join(web, 'index.php'), index);
fs.writeFileSync(path.join(root, '.local/cpanel-current.json'), JSON.stringify({ output, app, web }));
fs.copyFileSync(path.join(root, 'deployment/cpanel/UPLOAD-STEPS.md'), path.join(output, 'READ-ME-FIRST.md'));
console.log(JSON.stringify({ output, app, web }));
