@extends('layouts.public')

@section('title', 'MIDDLE EAST NET Zero - Carbon Emissions Tracking')

@section('content')
    <section class="mkt-hero mkt-hero-xl">
        <div class="mkt-container max-w-4xl">
            <div class="tagline">
                Complete Carbon Management Platform
            </div>
            <h1>
                Everything you need to
                <span class="block mkt-text-brand">measure and reduce your carbon footprint</span>
            </h1>
            <p class="mkt-lead mkt-lead-lg">
                Comprehensive tools designed specifically for Middle East businesses to
                track, report, and reduce emissions across all scopes.
            </p>
        </div>
    </section>

    <section class="mkt-section mkt-section-bg">
        <div class="mkt-container">
            <div class="mkt-section-head">
                <h2>Complete Scope 1, 2 &amp; 3 Coverage</h2>
                <p>Track all emission sources across your business operations with our comprehensive methodology</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Scope 1 -->
                <div class="mkt-feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">1</div>
                        <h3 class="text-xl font-bold text-gray-900">Scope 1 Emissions</h3>
                    </div>
                    <p class="mb-6 text-gray-500">Direct emissions from owned or controlled sources</p>
                    <ul class="space-y-3">
                        <li class="flex items-center"><span class="checkmark">✓</span> Company vehicles</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> On-site fuel combustion</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Refrigerant leaks</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Industrial processes</li>
                    </ul>
                </div>
                <!-- Scope 2 -->
                <div class="mkt-feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">2</div>
                        <h3 class="text-xl font-bold text-gray-900">Scope 2 Emissions</h3>
                    </div>
                    <p class="mb-6 text-gray-500">Indirect emissions from purchased energy</p>
                    <ul class="space-y-3">
                        <li class="flex items-center"><span class="checkmark">✓</span> Electricity consumption</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Steam and heating</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Cooling systems</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> MENA grid factors</li>
                    </ul>
                </div>
                <!-- Scope 3 -->
                <div class="mkt-feature-card">
                    <div class="flex items-center mb-4">
                        <div class="scope-number">3</div>
                        <h3 class="text-xl font-bold text-gray-900">Scope 3 Emissions</h3>
                    </div>
                    <p class="mb-6 text-gray-500">All other indirect emissions in value chain</p>
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

    <section class="mkt-section">
        <div class="mkt-container">
            <div class="mkt-section-head">
                <h2>Powerful features for every team</h2>
                <p>From data collection to reporting, we've built everything you need for comprehensive carbon management</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Emission Calculations -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Emission Calculations</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Automated Scope 1, 2 & 3 calculations using MENA-specific emission factors</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> AI-powered data validation and quality checks</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Support for 50+ emission sources and activities</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Real-time calculation updates and trend analysis</li>
                    </ul>
                </div>
                <!-- Regional Compliance -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Regional Compliance</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> UAE and Saudi Arabia sustainability reporting standards</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> GHG Protocol and ISO 14064 compliance</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Region-specific energy grid factors</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Local transportation and logistics emission data</li>
                    </ul>
                </div>
                <!-- Data Management -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Data Management</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Secure cloud storage with 99.9% uptime</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> API integrations with ERP and sustainability platforms</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Bulk data import from Excel and CSV files</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Automated data backup and version control</li>
                    </ul>
                </div>
                <!-- Reporting & Analytics -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Reporting & Analytics</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Compliance-ready PDF reports for stakeholders</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Interactive dashboards with drill-down capabilities</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Custom KPI tracking and goal setting</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Executive summary reports for board presentations</li>
                    </ul>
                </div>
                <!-- Team Collaboration -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Team Collaboration</h3>
                    <ul class="space-y-2">
                        <li class="flex items-center"><span class="checkmark">✓</span> Multi-user access with role-based permissions</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Department-level data collection workflows</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Comment system for data verification</li>
                        <li class="flex items-center"><span class="checkmark">✓</span> Email notifications for task assignments</li>
                    </ul>
                </div>
                <!-- Advanced Analytics -->
                <div class="mkt-feature-card">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-gray-900">Advanced Analytics</h3>
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

    <section class="mkt-section mkt-section-bg">
        <div class="mkt-container">
            <div class="mkt-section-head">
                <h2>Everything You Need for Carbon Management</h2>
                <p>A complete toolkit to measure, manage, and reduce your organization's carbon footprint</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Automated Reporting -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Automated Reporting</h3>
                    <p class="text-sm text-gray-500">Generate comprehensive carbon reports automatically</p>
                </div>
                <!-- Cloud-Based -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Cloud-Based</h3>
                    <p class="text-sm text-gray-500">Access your data anywhere, anytime, on any device</p>
                </div>
                <!-- Secure Platform -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Secure Platform</h3>
                    <p class="text-sm text-gray-500">Enterprise-grade security for your sensitive data</p>
                </div>
                <!-- Live Dashboard -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Live Dashboard</h3>
                    <p class="text-sm text-gray-500">Real-time insights into your carbon performance</p>
                </div>
                <!-- Data Integration -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Data Integration</h3>
                    <p class="text-sm text-gray-500">Connect with your existing systems seamlessly</p>
                </div>
                <!-- Verified Data -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Verified Data</h3>
                    <p class="text-sm text-gray-500">Quality-assured emission factors and calculations</p>
                </div>
                <!-- Sustainability Insights -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Sustainability Insights</h3>
                    <p class="text-sm text-gray-500">AI-powered recommendations for reduction strategies</p>
                </div>
                <!-- Audit Trail -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold mb-2 text-gray-900">Audit Trail</h3>
                    <p class="text-sm text-gray-500">Complete transparency with detailed audit logs</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mkt-section">
        <div class="mkt-container">
            <div class="mkt-section-head">
                <h2>Get Started in Four Simple Steps</h2>
                <p>Begin your carbon management journey today with our streamlined onboarding process</p>
            </div>
            <div class="grid md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="relative">
                    <div class="mkt-step-badge">01</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Launch Your Account</h3>
                        <p class="text-gray-500">Sign up and set up your organization profile in minutes</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="relative">
                    <div class="mkt-step-badge">02</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Connect Your Data</h3>
                        <p class="text-gray-500">Integrate your systems or upload data manually</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="relative">
                    <div class="mkt-step-badge">03</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Track Emissions</h3>
                        <p class="text-gray-500">Monitor your carbon footprint in real-time</p>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="relative">
                    <div class="mkt-step-badge">04</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold mb-3 text-gray-900">Take Action</h3>
                        <p class="text-gray-500">Implement reduction strategies and report progress</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mkt-section mkt-section-dark">
        <div class="mkt-container">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-semibold mb-4">For sustainability professionals</span>
                    <h2 class="text-4xl font-bold mb-4">Are you a carbon consultant in the UAE?</h2>
                    <p class="text-lg text-slate-300 mb-6">
                        Join the MENetZero verified consultant directory. Our platform prepares SME inventories and disclosures —
                        you review, sign off, and deliver the human trust layer clients expect.
                    </p>
                    <ul class="space-y-3 text-slate-300 mb-8">
                        <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Free listing for vetted consultants at launch</li>
                        <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Qualified leads from Starter &amp; Growth subscribers</li>
                        <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Marketplace payouts via platform escrow (coming soon)</li>
                    </ul>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary">Apply as a consultant</a>
                        <a href="{{ route('consultant-list.index') }}" class="mkt-btn mkt-btn-white-outline">Browse directory</a>
                    </div>
                </div>
                <div class="mkt-glass-panel">
                    <h3 class="text-xl font-semibold mb-4">How it works</h3>
                    <ol class="space-y-4 text-slate-300 text-sm">
                        <li class="flex gap-3"><span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-500/30 flex items-center justify-center font-bold text-teal-300">1</span><span><strong class="text-white">Register</strong> at app.menetzero.com/consultant with your practice details</span></li>
                        <li class="flex gap-3"><span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-500/30 flex items-center justify-center font-bold text-teal-300">2</span><span><strong class="text-white">Upload</strong> trade license and CV for admin verification</span></li>
                        <li class="flex gap-3"><span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-500/30 flex items-center justify-center font-bold text-teal-300">3</span><span><strong class="text-white">Get listed</strong> and receive client introduction requests</span></li>
                        <li class="flex gap-3"><span class="flex-shrink-0 w-8 h-8 rounded-full bg-teal-500/30 flex items-center justify-center font-bold text-teal-300">4</span><span><strong class="text-white">Deliver</strong> review packs — paid engagements via MenetZero escrow</span></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="mkt-section mkt-section-bg">
        <div class="mkt-container">
            <div class="mkt-section-head">
                <h2 class="mkt-text-brand">Enterprise-Grade Security</h2>
                <p>Your data security is our top priority. We employ industry-leading security measures to protect your sensitive information</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- 256-Bit Encryption -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">256-Bit Encryption</h3>
                    <p class="text-gray-500">Military-grade encryption for all data transmission and storage</p>
                </div>
                <!-- SOC 2 Certified -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">SOC 2 Certified</h3>
                    <p class="text-gray-500">Independently verified security controls and processes</p>
                </div>
                <!-- Regular Backups -->
                <div class="mkt-feature-card text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-gray-900">Regular Backups</h3>
                    <p class="text-gray-500">Automated daily backups with 99.9% uptime guarantee</p>
                </div>
            </div>
        </div>
    </section>

@endsection