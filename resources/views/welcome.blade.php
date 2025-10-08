<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIDDLE EAST NET Zero - Professional Carbon Emissions Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { 
            font-family: 'Inter', system-ui, -apple-system, sans-serif; 
            color: #111827 !important; 
            background-color: #f9fafb !important; 
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
            border-radius: 1rem; 
            font-weight: 500; 
            text-decoration: none; 
            border: 1px solid transparent; 
            cursor: pointer; 
            transition: all 0.15s ease-in-out; 
        }
        .btn-secondary { 
            background-color: #10b981; 
            color: white; 
            border-color: #10b981; 
        }
        .btn-secondary:hover { 
            background-color: #059669; 
            border-color: #059669; 
            transform: translateY(-1px); 
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
        .brand-logo-icon { 
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
    </style>
</head>
<body class="bg-gray-50">
    <section class="brand-gradient text-white section">
        <div class="container">
            <div class="text-center">
                <div class="mb-8">
                    <div class="brand-logo">
                        <div class="brand-logo-icon" style="background-color: white; color: var(--brand-primary);">ME</div>
                        <div style="color: white;">
                            <div class="brand-logo-text-small" style="color: white;">MIDDLE EAST</div>
                            <div class="brand-logo-text-large" style="color: white;">NET Zero</div>
                        </div>
                    </div>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6 text-white">
                    Track Your Carbon Footprint
                    <span class="block text-white/90">Like a Pro</span>
                </h1>
                <p class="text-xl md:text-2xl text-white/95 mb-8 max-w-3xl mx-auto">
                    Comprehensive carbon emissions tracking for businesses in the Middle East.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="btn btn-secondary btn-lg">
                        Start Free Trial
                    </a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
