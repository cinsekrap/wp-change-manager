<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    /**
     * GET /install — Show the installer wizard.
     */
    public function index()
    {
        if (file_exists(storage_path('installed.lock'))) {
            abort(404);
        }

        return view('installer');
    }

    /**
     * POST /install/check — Run environment checks.
     */
    public function checkEnvironment()
    {
        $results = [];

        // PHP version
        $results['php_version'] = [
            'label' => 'PHP >= 8.2 (current: ' . PHP_VERSION . ')',
            'pass' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'critical' => true,
        ];

        // Required extensions
        $extensions = [
            'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
            'xml', 'ctype', 'json', 'bcmath', 'fileinfo',
        ];

        foreach ($extensions as $ext) {
            $results['ext_' . $ext] = [
                'label' => "Extension: {$ext}",
                'pass' => extension_loaded($ext),
                'critical' => true,
            ];
        }

        // Directory permissions
        $results['storage_writable'] = [
            'label' => 'storage/ directory writable',
            'pass' => is_writable(storage_path()),
            'critical' => true,
        ];

        $results['cache_writable'] = [
            'label' => 'bootstrap/cache/ directory writable',
            'pass' => is_writable(base_path('bootstrap/cache')),
            'critical' => true,
        ];

        // HTTPS check (warning only)
        $isHttps = request()->secure() ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $results['https'] = [
            'label' => 'HTTPS connection',
            'pass' => $isHttps,
            'critical' => false,
        ];

        $allCriticalPass = collect($results)
            ->where('critical', true)
            ->every(fn ($check) => $check['pass']);

        return response()->json([
            'checks' => $results,
            'can_proceed' => $allCriticalPass,
        ]);
    }

    /**
     * POST /install/database — Test database connection and store credentials.
     */
    public function setupDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $host = $request->input('db_host');
        $port = $request->input('db_port');
        $database = $request->input('db_database');
        $username = $request->input('db_username');
        $password = $request->input('db_password', '');

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            // Quick query to verify access
            $pdo->query('SELECT 1');
        } catch (\PDOException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        }

        // Store in session
        $request->session()->put('installer.db', [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ]);

        return response()->json(['success' => true, 'message' => 'Database connection successful.']);
    }

    /**
     * POST /install/application — Configure application settings.
     */
    public function setupApplication(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url|max:255',
        ]);

        // Generate APP_KEY
        $key = \Illuminate\Encryption\Encrypter::generateKey('AES-256-CBC');
        $appKey = 'base64:' . base64_encode($key);

        // Generate DEPLOY_TOKEN
        $deployToken = Str::random(64);

        $request->session()->put('installer.app', [
            'name' => $request->input('app_name'),
            'url' => rtrim($request->input('app_url'), '/'),
            'key' => $appKey,
            'deploy_token' => $deployToken,
        ]);

        return response()->json([
            'success' => true,
            'deploy_token' => $deployToken,
        ]);
    }

    /**
     * POST /install/migrate — Write .env and run migrations.
     */
    public function runMigrations(Request $request)
    {
        $db = $request->session()->get('installer.db');
        $app = $request->session()->get('installer.app');

        if (! $db || ! $app) {
            return response()->json([
                'success' => false,
                'message' => 'Missing configuration. Please complete the previous steps.',
            ], 422);
        }

        // Build .env content
        $envContent = $this->buildEnvContent($app, $db);

        // Write .env
        try {
            file_put_contents(base_path('.env'), $envContent);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to write .env file: ' . $e->getMessage(),
            ], 500);
        }

        // Clear cached config so Laravel picks up the new .env
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Non-fatal, continue
        }

        // Set the database config at runtime so migrations use the new credentials
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $db['host'],
            'database.connections.mysql.port' => $db['port'],
            'database.connections.mysql.database' => $db['database'],
            'database.connections.mysql.username' => $db['username'],
            'database.connections.mysql.password' => $db['password'],
        ]);

        // Purge existing DB connections so they reconnect with new config
        app('db')->purge('mysql');

        // Set the app key at runtime
        config(['app.key' => $app['key']]);

        // Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Migrations completed successfully.',
            'output' => $output,
        ]);
    }

    /**
     * POST /install/admin — Create the super admin user and seed data.
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Ensure runtime DB config is set from session
        $this->applyRuntimeConfig($request);

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin: ' . $e->getMessage(),
            ], 500);
        }

        // Run seeders
        try {
            Artisan::call('db:seed', ['--class' => 'CptTypeSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CheckQuestionSeeder', '--force' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeding failed: ' . $e->getMessage(),
            ], 500);
        }

        // Store admin email in session for the summary
        $request->session()->put('installer.admin_email', $user->email);

        return response()->json([
            'success' => true,
            'message' => 'Admin account created and data seeded.',
        ]);
    }

    /**
     * POST /install/complete — Create the lock file and finish.
     */
    public function complete(Request $request)
    {
        $lockContent = 'Installed: ' . now()->format('Y-m-d H:i:s') . "\n"
            . 'Do not delete this file. Removing it will expose the installer.' . "\n";

        try {
            file_put_contents(storage_path('installed.lock'), $lockContent);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lock file: ' . $e->getMessage(),
            ], 500);
        }

        // Clean up bootstrap .env.install
        $installEnv = base_path('.env.install');
        if (file_exists($installEnv)) {
            @unlink($installEnv);
        }

        // Clear installer session data
        $request->session()->forget('installer');

        return response()->json([
            'success' => true,
            'message' => 'Installation complete.',
            'redirect' => '/admin/login',
        ]);
    }

    /**
     * Build the .env file content from collected settings.
     */
    private function buildEnvContent(array $app, array $db): string
    {
        $appName = str_contains($app['name'], ' ') ? '"' . $app['name'] . '"' : $app['name'];

        return <<<ENV
APP_NAME={$appName}
APP_ENV=production
APP_KEY={$app['key']}
APP_DEBUG=false
APP_URL={$app['url']}

DB_CONNECTION=mysql
DB_HOST={$db['host']}
DB_PORT={$db['port']}
DB_DATABASE={$db['database']}
DB_USERNAME={$db['username']}
DB_PASSWORD={$db['password']}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

DEPLOY_TOKEN={$app['deploy_token']}
ENV;
    }

    /**
     * Apply runtime database and app config from session data.
     * Needed for steps after migration (admin creation, seeding).
     */
    private function applyRuntimeConfig(Request $request): void
    {
        $db = $request->session()->get('installer.db');
        $app = $request->session()->get('installer.app');

        if ($db) {
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $db['host'],
                'database.connections.mysql.port' => $db['port'],
                'database.connections.mysql.database' => $db['database'],
                'database.connections.mysql.username' => $db['username'],
                'database.connections.mysql.password' => $db['password'],
            ]);
            app('db')->purge('mysql');
        }

        if ($app) {
            config(['app.key' => $app['key']]);
        }
    }
}
