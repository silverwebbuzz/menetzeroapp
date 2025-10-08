<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIDDLE EAST NET Zero - Professional Carbon Emissions Tracking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/design-system.css') }}">
</head>
<body class="bg-gray-50">
    <section class="brand-gradient text-white section">
        <div class="container">
            <div class="text-center">
                <div class="mb-8">
                    @include('components.brand-logo')
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Track Your Carbon Footprint
                    <span class="block text-white/80">Like a Pro</span>
                </h1>
                <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
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
