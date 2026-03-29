<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install - ACME Change</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:ital,opsz,wght@0,6..12,300;0,6..12,400;0,6..12,600;0,6..12,700;1,6..12,400&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Nunito Sans"', 'sans-serif'],
                    },
                    colors: {
                        burgundy: {
                            50: '#fdf2f6',
                            100: '#fce7ef',
                            200: '#fad0e0',
                            300: '#f6a9c5',
                            400: '#ef72a0',
                            500: '#e5477e',
                            600: '#d32a5e',
                            700: '#B52159',
                            800: '#961c3e',
                            900: '#7d1b37',
                        },
                    },
                },
            },
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Nunito Sans', sans-serif; }

        .step-hidden { display: none; }
        .step-active { display: block; }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #B52159;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        input:focus {
            outline: none;
            border-color: #B52159;
            box-shadow: 0 0 0 3px rgba(181, 33, 89, 0.15);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="max-w-2xl mx-auto px-4 py-8 sm:py-12" x-data="installer()" x-cloak>
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">ACME Change</h1>
            <p class="text-gray-500 mt-1">Installation Wizard</p>
        </div>

        {{-- Progress bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                <template x-for="step in 6" :key="step">
                    <div class="flex items-center" :class="step < 6 ? 'flex-1' : ''">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors duration-200"
                            :class="currentStep >= step ? 'bg-burgundy-700 text-white' : 'bg-gray-200 text-gray-500'"
                            x-text="step"
                        ></div>
                        <div
                            x-show="step < 6"
                            class="flex-1 h-1 mx-2 rounded transition-colors duration-200"
                            :class="currentStep > step ? 'bg-burgundy-700' : 'bg-gray-200'"
                        ></div>
                    </div>
                </template>
            </div>
            <div class="text-center text-sm text-gray-500" x-text="stepLabels[currentStep - 1]"></div>
        </div>

        {{-- HTTPS warning --}}
        <div
            x-show="!isHttps"
            x-cloak
            class="bg-amber-50 border border-amber-300 text-amber-800 rounded-xl px-4 py-3 mb-6 text-sm flex items-start gap-2"
        >
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>You are not using HTTPS. It is strongly recommended to install an SSL certificate before going live.</span>
        </div>

        {{-- Card container --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">

            {{-- Step 1: Environment Check --}}
            <div x-show="currentStep === 1" class="fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Welcome</h2>
                <p class="text-gray-500 mb-6">Let's get your installation set up. First, we'll check your server environment.</p>

                <div x-show="checksLoading" class="flex items-center gap-3 text-gray-500 py-8 justify-center">
                    <div class="spinner"></div>
                    <span>Checking environment...</span>
                </div>

                <div x-show="!checksLoading && checks.length > 0" class="space-y-2 mb-6">
                    <template x-for="check in checks" :key="check.key">
                        <div class="flex items-center gap-3 py-2 px-3 rounded-lg" :class="check.pass ? 'bg-green-50' : (check.critical ? 'bg-red-50' : 'bg-amber-50')">
                            <template x-if="check.pass">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="!check.pass && check.critical">
                                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </template>
                            <template x-if="!check.pass && !check.critical">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <span class="text-sm" :class="check.pass ? 'text-green-800' : (check.critical ? 'text-red-800' : 'text-amber-800')" x-text="check.label"></span>
                        </div>
                    </template>
                </div>

                <div x-show="checksError" class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm" x-text="checksError"></div>

                <div class="flex justify-end">
                    <button
                        @click="goToStep(2)"
                        :disabled="!canProceedFromChecks"
                        class="px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="canProceedFromChecks ? 'bg-burgundy-700 hover:bg-burgundy-800' : 'bg-gray-300'"
                    >
                        Next
                    </button>
                </div>
            </div>

            {{-- Step 2: Database Configuration --}}
            <div x-show="currentStep === 2" class="fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Database Configuration</h2>
                <p class="text-gray-500 mb-6">Enter your MySQL database connection details.</p>

                <div class="space-y-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Host</label>
                            <input type="text" x-model="db.host" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="127.0.0.1">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Port</label>
                            <input type="text" x-model="db.port" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="3306">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Database Name</label>
                        <input type="text" x-model="db.database" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="acme_change">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                        <input type="text" x-model="db.username" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="root">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" x-model="db.password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="">
                    </div>
                </div>

                <div x-show="dbMessage" class="rounded-xl px-4 py-3 mb-4 text-sm" :class="dbSuccess ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'" x-text="dbMessage"></div>

                <div class="flex justify-between">
                    <button @click="goToStep(1)" class="px-6 py-2.5 rounded-full text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Back
                    </button>
                    <div class="flex gap-3">
                        <button
                            @click="testDatabase()"
                            :disabled="dbTesting"
                            class="px-6 py-2.5 rounded-full text-sm font-semibold border border-burgundy-700 text-burgundy-700 hover:bg-burgundy-50 transition-colors disabled:opacity-50"
                        >
                            <span x-show="!dbTesting">Test Connection</span>
                            <span x-show="dbTesting" class="flex items-center gap-2"><span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Testing...</span>
                        </button>
                        <button
                            @click="goToStep(3)"
                            :disabled="!dbSuccess"
                            class="px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="dbSuccess ? 'bg-burgundy-700 hover:bg-burgundy-800' : 'bg-gray-300'"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>

            {{-- Step 3: Application Setup --}}
            <div x-show="currentStep === 3" class="fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Application Setup</h2>
                <p class="text-gray-500 mb-6">Configure your application name and URL.</p>

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Application Name</label>
                        <input type="text" x-model="app.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ACME Change">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Application URL</label>
                        <input type="url" x-model="app.url" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="https://change.example.com">
                    </div>
                    <div x-show="deployToken">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Deploy Token</label>
                        <div class="flex gap-2">
                            <input type="text" :value="deployToken" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono text-gray-600">
                            <button @click="copyToken()" class="px-4 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors flex-shrink-0" :class="tokenCopied ? 'border-green-300 text-green-600 bg-green-50' : ''">
                                <span x-show="!tokenCopied">Copy</span>
                                <span x-show="tokenCopied">Copied</span>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Save this token. It is used for automated deployments.</p>
                    </div>
                </div>

                <div x-show="appMessage" class="rounded-xl px-4 py-3 mb-4 text-sm" :class="appSuccess ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'" x-text="appMessage"></div>

                <div class="flex justify-between">
                    <button @click="goToStep(2)" class="px-6 py-2.5 rounded-full text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Back
                    </button>
                    <button
                        @click="saveApplication()"
                        :disabled="appSaving || !app.name || !app.url"
                        class="px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="(!appSaving && app.name && app.url) ? 'bg-burgundy-700 hover:bg-burgundy-800' : 'bg-gray-300'"
                    >
                        <span x-show="!appSaving">Next</span>
                        <span x-show="appSaving" class="flex items-center gap-2"><span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Saving...</span>
                    </button>
                </div>
            </div>

            {{-- Step 4: Running Migrations --}}
            <div x-show="currentStep === 4" class="fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Database Migration</h2>
                <p class="text-gray-500 mb-6">Setting up the database tables and structure.</p>

                <div x-show="migrateRunning" class="flex items-center gap-3 text-gray-500 py-8 justify-center">
                    <div class="spinner"></div>
                    <span>Running migrations... This may take a moment.</span>
                </div>

                <div x-show="migrateSuccess" class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-6 text-sm text-green-700 flex items-start gap-2">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="migrateMessage"></span>
                </div>

                <div x-show="migrateError" class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-6 text-sm text-red-700" x-text="migrateMessage"></div>

                <div class="flex justify-between" x-show="!migrateRunning">
                    <button @click="goToStep(3)" x-show="migrateError" class="px-6 py-2.5 rounded-full text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Back
                    </button>
                    <div x-show="!migrateError"></div>
                    <button
                        @click="goToStep(5)"
                        :disabled="!migrateSuccess"
                        class="px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="migrateSuccess ? 'bg-burgundy-700 hover:bg-burgundy-800' : 'bg-gray-300'"
                    >
                        Next
                    </button>
                </div>
            </div>

            {{-- Step 5: Create Admin Account --}}
            <div x-show="currentStep === 5" class="fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Create Admin Account</h2>
                <p class="text-gray-500 mb-6">Set up the super administrator account.</p>

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                        <input type="text" x-model="admin.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="John Smith">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" x-model="admin.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="admin@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" x-model="admin.password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Minimum 8 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" x-model="admin.password_confirmation" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Repeat password">
                    </div>
                </div>

                <div x-show="adminMessage" class="rounded-xl px-4 py-3 mb-4 text-sm" :class="adminSuccess ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'" x-text="adminMessage"></div>

                <div class="flex justify-between">
                    <div></div>
                    <button
                        @click="createAdmin()"
                        :disabled="adminCreating || !admin.name || !admin.email || !admin.password || !admin.password_confirmation"
                        class="px-6 py-2.5 rounded-full text-sm font-semibold text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="(!adminCreating && admin.name && admin.email && admin.password && admin.password_confirmation) ? 'bg-burgundy-700 hover:bg-burgundy-800' : 'bg-gray-300'"
                    >
                        <span x-show="!adminCreating">Create Account</span>
                        <span x-show="adminCreating" class="flex items-center gap-2"><span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Creating...</span>
                    </button>
                </div>
            </div>

            {{-- Step 6: Complete --}}
            <div x-show="currentStep === 6" class="fade-in text-center py-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h2 class="text-xl font-bold text-gray-900 mb-1">Installation Complete!</h2>
                <p class="text-gray-500 mb-8">Your application has been configured and is ready to use.</p>

                <div class="bg-gray-50 rounded-xl p-4 mb-8 text-left text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Application Name</span>
                        <span class="font-semibold text-gray-900" x-text="app.name"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">URL</span>
                        <span class="font-semibold text-gray-900" x-text="app.url"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Admin Email</span>
                        <span class="font-semibold text-gray-900" x-text="admin.email"></span>
                    </div>
                </div>

                <a href="/admin/login" class="inline-block px-8 py-3 rounded-full text-sm font-semibold text-white bg-burgundy-700 hover:bg-burgundy-800 transition-colors">
                    Go to Login
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">ACME Change &mdash; Installation Wizard</p>
    </div>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>

    <script>
        function installer() {
            return {
                currentStep: 1,
                stepLabels: [
                    'Environment Check',
                    'Database Configuration',
                    'Application Setup',
                    'Database Migration',
                    'Create Admin',
                    'Complete',
                ],
                isHttps: window.location.protocol === 'https:',

                // Step 1 state
                checks: [],
                checksLoading: false,
                checksError: null,
                canProceedFromChecks: false,

                // Step 2 state
                db: {
                    host: '127.0.0.1',
                    port: '3306',
                    database: '',
                    username: '',
                    password: '',
                },
                dbTesting: false,
                dbSuccess: false,
                dbMessage: '',

                // Step 3 state
                app: {
                    name: 'ACME Change',
                    url: window.location.origin,
                },
                appSaving: false,
                appSuccess: false,
                appMessage: '',
                deployToken: '',
                tokenCopied: false,

                // Step 4 state
                migrateRunning: false,
                migrateSuccess: false,
                migrateError: false,
                migrateMessage: '',

                // Step 5 state
                admin: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                },
                adminCreating: false,
                adminSuccess: false,
                adminMessage: '',

                // Step 6 state
                completeFinished: false,

                init() {
                    this.runChecks();
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                },

                async apiPost(url, data = {}) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        body: JSON.stringify(data),
                    });

                    const json = await response.json();

                    if (!response.ok) {
                        // Handle validation errors
                        if (json.errors) {
                            const messages = Object.values(json.errors).flat().join(' ');
                            throw new Error(messages);
                        }
                        throw new Error(json.message || 'Request failed.');
                    }

                    return json;
                },

                goToStep(step) {
                    this.currentStep = step;

                    // Auto-run migrations when entering step 4
                    if (step === 4 && !this.migrateSuccess && !this.migrateRunning) {
                        this.runMigrations();
                    }
                },

                // Step 1: Environment checks
                async runChecks() {
                    this.checksLoading = true;
                    this.checksError = null;

                    try {
                        const data = await this.apiPost('/install/check');
                        this.checks = Object.entries(data.checks).map(([key, check]) => ({
                            key,
                            ...check,
                        }));
                        this.canProceedFromChecks = data.can_proceed;
                    } catch (e) {
                        this.checksError = 'Failed to run environment checks: ' + e.message;
                    } finally {
                        this.checksLoading = false;
                    }
                },

                // Step 2: Test database
                async testDatabase() {
                    this.dbTesting = true;
                    this.dbMessage = '';
                    this.dbSuccess = false;

                    try {
                        const data = await this.apiPost('/install/database', {
                            db_host: this.db.host,
                            db_port: this.db.port,
                            db_database: this.db.database,
                            db_username: this.db.username,
                            db_password: this.db.password,
                        });
                        this.dbSuccess = true;
                        this.dbMessage = data.message;
                    } catch (e) {
                        this.dbSuccess = false;
                        this.dbMessage = e.message;
                    } finally {
                        this.dbTesting = false;
                    }
                },

                // Step 3: Save application settings
                async saveApplication() {
                    this.appSaving = true;
                    this.appMessage = '';

                    try {
                        const data = await this.apiPost('/install/application', {
                            app_name: this.app.name,
                            app_url: this.app.url,
                        });
                        this.appSuccess = true;
                        this.deployToken = data.deploy_token;
                        this.goToStep(4);
                    } catch (e) {
                        this.appSuccess = false;
                        this.appMessage = e.message;
                    } finally {
                        this.appSaving = false;
                    }
                },

                async copyToken() {
                    try {
                        await navigator.clipboard.writeText(this.deployToken);
                        this.tokenCopied = true;
                        setTimeout(() => this.tokenCopied = false, 2000);
                    } catch (e) {
                        // Fallback for non-HTTPS
                        const input = document.createElement('textarea');
                        input.value = this.deployToken;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        this.tokenCopied = true;
                        setTimeout(() => this.tokenCopied = false, 2000);
                    }
                },

                // Step 4: Run migrations
                async runMigrations() {
                    this.migrateRunning = true;
                    this.migrateSuccess = false;
                    this.migrateError = false;
                    this.migrateMessage = '';

                    try {
                        const data = await this.apiPost('/install/migrate');
                        this.migrateSuccess = true;
                        this.migrateMessage = data.message;
                    } catch (e) {
                        this.migrateError = true;
                        this.migrateMessage = e.message;
                    } finally {
                        this.migrateRunning = false;
                    }
                },

                // Step 5: Create admin
                async createAdmin() {
                    this.adminCreating = true;
                    this.adminMessage = '';
                    this.adminSuccess = false;

                    try {
                        await this.apiPost('/install/admin', {
                            name: this.admin.name,
                            email: this.admin.email,
                            password: this.admin.password,
                            password_confirmation: this.admin.password_confirmation,
                        });
                        this.adminSuccess = true;
                        // Move to complete step and finalize
                        this.currentStep = 6;
                        this.finalize();
                    } catch (e) {
                        this.adminSuccess = false;
                        this.adminMessage = e.message;
                    } finally {
                        this.adminCreating = false;
                    }
                },

                // Step 6: Finalize installation
                async finalize() {
                    try {
                        await this.apiPost('/install/complete');
                        this.completeFinished = true;
                    } catch (e) {
                        // Non-fatal — the lock file might fail but installation is done
                        console.error('Failed to create lock file:', e);
                    }
                },
            };
        }
    </script>
</body>
</html>
