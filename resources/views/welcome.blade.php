<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MIDDLE EAST NET Zero - Carbon Emissions Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌱</text></svg>">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #111827 !important;
            background-color: #ffffff !important;
        }
        .brand-gradient {
            background: linear-gradient(135deg, #0ea5a3 0%, #10b981 100%);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .btn-primary {
            background-color: #0ea5a3;
            color: white;
            border-color: #0ea5a3;
        }
        .btn-primary:hover {
            background-color: #0d9488;
            border-color: #0d9488;
            transform: translateY(-1px);
        }
        .btn-outline {
            background-color: transparent;
            color: #0ea5a3;
            border-color: #0ea5a3;
        }
        .btn-outline:hover {
            background-color: #0ea5a3;
            color: white;
        }
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .brand-logo img {
            height: 2rem;
            width: auto;
        }
        .brand-logo-text {
            color: white !important;
            font-weight: 600;
            font-size: 1.125rem;
        }
        .brand-logo-text-small {
            font-size: 0.75rem;
            font-weight: 500;
            line-height: 1.2;
            color: white !important;
        }
        .brand-logo-text-large {
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.2;
            color: white !important;
        }
        .tagline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: rgba(14, 165, 163, 0.1);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #0ea5a3;
            margin-bottom: 1.5rem;
        }
        .feature-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .scope-number {
            width: 2rem;
            height: 2rem;
            background-color: #0ea5a3;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            margin-right: 0.75rem;
        }
        .checkmark {
            color: #0ea5a3;
            margin-right: 0.5rem;
        }
        .section-bg {
            background: linear-gradient(135deg, #f0fdfa 0%, #f0fdf4 100%);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="brand-logo">
                    <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-8 w-auto">
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('login') }}" class="btn btn-outline">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <div class="tagline">
                Complete Carbon Management Platform
            </div>
            <h1 class="text-5xl md:text-7xl font-bold mb-6" style="color: #111827;">
                Everything you need to
                <span class="block" style="color: #0ea5a3;">measure and reduce your carbon footprint</span>
            </h1>
            <p class="text-xl md:text-2xl mb-8" style="color: #6b7280;">
                Comprehensive tools designed specifically for Middle East businesses to<br>
                track, report, and reduce emissions across all scopes.
            </p>
        </div>
    </section>

    <!-- Scope Coverage Section -->
    <section class="py-20 px-4 section-bg">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4" style="color: #111827;">Complete Scope 1, 2 & 3 Coverage</h2>
                <p class="text-xl" style="color: #6b7280;">Track all emission sources across your business operations with our comprehensive methodology</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Scope 1 -->
                <div class="feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">1</div>
                        <h3 class="text-xl font-bold" style="color: #111827;">Scope 1 Emissions</h3>
                    </div>
                    <p class="mb-6" style="color: #6b7280;">Direct emissions from owned or controlled sources</p>
                    <ul class="space-y-3">
                        <li class="flex items-center"><span class="checkmark">✓</span> Company vehicles</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> On-site fuel combustion</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Refrigerant leaks</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Industrial processes</li>
                    </ul>
                </div>
                <!-- Scope 2 -->
                <div class="feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">2</div>
                        <h3 class="text-xl font-bold" style="color: #111827;">Scope 2 Emissions</h3>
                    </div>
                    <p class="mb-6" style="color: #6b7280;">Indirect emissions from purchased energy</p>
                    <ul class="space-y-3">
                        <li class="flex items-center"><span class="checkmark">✓</span> Electricity consumption</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Steam and heating</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Cooling systems</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> MENA grid factors</li>
                    </ul>
                </div>
                <!-- Scope 3 -->
                <div class="feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">3</div>
                        <h3 class="text-xl font-bold" style="color: #111827;">Scope 3 Emissions</h3>
                    </div>
                    <p class="mb-6" style="color: #6b7280;">All other indirect emissions in value chain</p>
                    <ul class="space-y-3">
                        <li class="flex items-center"><span class="checkmark">✓</span> Business travel</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Supply chain</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Employee commuting</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Waste disposal</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Powerful Features Section -->
    <section class="py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4" style="color: #111827;">Powerful features for every team</h2>
                <p class="text-xl" style="color: #6b7280;">From data collection to reporting, we've built everything you need for comprehensive carbon management</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Emission Calculations -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Emission Calculations</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Automated Scope 1, 2 & 3 calculations using MENA-specific emission factors</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> AI-powered data validation and quality checks</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Support for 50+ emission sources and activities</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Real-time calculation updates and trend analysis</li>
                    </ul>
                </div>
                <!-- Regional Compliance -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Regional Compliance</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> UAE and Saudi Arabia sustainability reporting standards</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> GHG Protocol and ISO 14064 compliance</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Region-specific energy grid factors</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Local transportation and logistics emission data</li>
                    </ul>
                </div>
                <!-- Data Management -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Data Management</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Secure cloud storage with 99.9% uptime</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> API integrations with ERP and sustainability platforms</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Bulk data import from Excel and CSV files</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Automated data backup and version control</li>
                    </ul>
                </div>
                <!-- Reporting & Analytics -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Reporting & Analytics</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Compliance-ready PDF reports for stakeholders</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Interactive dashboards with drill-down capabilities</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Custom KPI tracking and goal setting</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Executive summary reports for board presentations</li>
                    </ul>
                </div>
                <!-- Team Collaboration -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Team Collaboration</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Multi-user access with role-based permissions</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Department-level data collection workflows</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Comment system for data verification</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Email notifications for task assignments</li>
                    </ul>
                </div>
                <!-- Advanced Analytics -->
                <div class="feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4" style="color: #111827;">Advanced Analytics</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Predictive emissions modeling and forecasting</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Benchmarking against industry peers</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Carbon reduction scenario planning</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Supply chain emissions hotspot analysis</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Everything You Need Section -->
    <section class="py-20 px-4 section-bg">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4" style="color: #111827;">Everything You Need for Carbon Management</h2>
                <p class="text-xl" style="color: #6b7280;">A complete toolkit to measure, manage, and reduce your organization's carbon footprint</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Automated Reporting -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Automated Reporting</h3>
                    <p class="text-sm" style="color: #6b7280;">Generate comprehensive carbon reports automatically</p>
                </div>
                <!-- Cloud-Based -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Cloud-Based</h3>
                    <p class="text-sm" style="color: #6b7280;">Access your data anywhere, anytime, on any device</p>
                </div>
                <!-- Secure Platform -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Secure Platform</h3>
                    <p class="text-sm" style="color: #6b7280;">Enterprise-grade security for your sensitive data</p>
                </div>
                <!-- Live Dashboard -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Live Dashboard</h3>
                    <p class="text-sm" style="color: #6b7280;">Real-time insights into your carbon performance</p>
                </div>
                <!-- Data Integration -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Data Integration</h3>
                    <p class="text-sm" style="color: #6b7280;">Connect with your existing systems seamlessly</p>
                </div>
                <!-- Verified Data -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Verified Data</h3>
                    <p class="text-sm" style="color: #6b7280;">Quality-assured emission factors and calculations</p>
                </div>
                <!-- Sustainability Insights -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Sustainability Insights</h3>
                    <p class="text-sm" style="color: #6b7280;">AI-powered recommendations for reduction strategies</p>
                </div>
                <!-- Audit Trail -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2" style="color: #111827;">Audit Trail</h3>
                    <p class="text-sm" style="color: #6b7280;">Complete transparency with detailed audit logs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Get Started Steps Section -->
    <section class="py-20 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4" style="color: #111827;">Get Started in Four Simple Steps</h2>
                <p class="text-xl" style="color: #6b7280;">Begin your carbon management journey today with our streamlined onboarding process</p>
            </div>
            <div class="grid md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-2xl font-bold" style="color: #0ea5a3;">01</div>
                    <div class="feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3" style="color: #111827;">Launch Your Account</h3>
                        <p style="color: #6b7280;">Sign up and set up your organization profile in minutes</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-2xl font-bold" style="color: #0ea5a3;">02</div>
                    <div class="feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3" style="color: #111827;">Connect Your Data</h3>
                        <p style="color: #6b7280;">Integrate your systems or upload data manually</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-2xl font-bold" style="color: #0ea5a3;">03</div>
                    <div class="feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3" style="color: #111827;">Track Emissions</h3>
                        <p style="color: #6b7280;">Monitor your carbon footprint in real-time</p>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-2xl font-bold" style="color: #0ea5a3;">04</div>
                    <div class="feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3" style="color: #111827;">Take Action</h3>
                        <p style="color: #6b7280;">Implement reduction strategies and report progress</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enterprise Security Section -->
    <section class="py-20 px-4 section-bg">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4" style="color: #0ea5a3;">Enterprise-Grade Security</h2>
                <p class="text-xl" style="color: #6b7280;">Your data security is our top priority. We employ industry-leading security measures to protect your sensitive information</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- 256-Bit Encryption -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3" style="color: #111827;">256-Bit Encryption</h3>
                    <p style="color: #6b7280;">Military-grade encryption for all data transmission and storage</p>
                </div>
                <!-- SOC 2 Certified -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3" style="color: #111827;">SOC 2 Certified</h3>
                    <p style="color: #6b7280;">Independently verified security controls and processes</p>
                </div>
                <!-- Regular Backups -->
                <div class="feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3" style="color: #111827;">Regular Backups</h3>
                    <p style="color: #6b7280;">Automated daily backups with 99.9% uptime guarantee</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 px-4">
        <div class="max-w-6xl mx-auto text-center">
            <div class="brand-logo justify-center mb-6">
                <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-8 w-auto">
            </div>
            <p class="text-gray-400 mb-6">Comprehensive carbon emissions tracking for businesses in the Middle East</p>
            <div class="flex justify-center space-x-6">
                <a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition-colors">Login</a>
                <a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition-colors">Signup</a>
            </div>
        </div>
    </footer>
</body>
</html>