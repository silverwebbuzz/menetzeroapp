<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonTracker - Professional Carbon Emissions Tracking</title>
    @vite([\"resources/css/app.css\", \"resources/js/app.js\"])
</head>
<body class="bg-gray-50">
    <section class="bg-gradient-to-br from-eco-green to-eco-green-dark text-white py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Track Your Carbon Footprint
                    <span class="block text-green-200">Like a Pro</span>
                </h1>
                <p class="text-xl md:text-2xl text-green-100 mb-8 max-w-3xl mx-auto">
                    Comprehensive carbon emissions tracking for businesses.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href=\"{{ route(\"register\") }}\" class="bg-white text-eco-green hover:bg-gray-100 font-medium py-3 px-8 rounded-lg">
                        Start Free Trial
                    </a>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
