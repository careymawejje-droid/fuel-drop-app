<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FuelDrop – Fuel delivered to you</title>
    <!-- Load the existing DM Sans font, Tailwind utilities, Lucide icons, and shared stylesheet. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Start loading the shared page styles before external scripts can block rendering. -->
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="fd-landing antialiased" data-page="">

@php
    // Keep the existing permission checks and destinations for order actions.
    $canPlaceOrder = auth()->check()
        && auth()->user()->hasRole('customer')
        && auth()->user()->can('view available fuels')
        && auth()->user()->can('place orders');
    $orderUrl = $canPlaceOrder ? route('customer.orders.create') : (auth()->check() ? route('home') : route('login'));
    $showOrderButtons = ! auth()->check() || $canPlaceOrder;

    // Preserve the three existing fuel-price hooks populated by app.js.
    $fuels = [
        ['key' => 'petrol',   'name' => 'Petrol'],
        ['key' => 'diesel',   'name' => 'Diesel'],
        ['key' => 'paraffin', 'name' => 'Paraffin'],
    ];

    // Supply the text and Lucide icon names for the three process steps.
    $steps = [
        ['icon' => 'fuel',        'title' => 'Choose your fuel', 'text' => 'Pick the type and quantity that works for you.'],
        ['icon' => 'map-pin',     'title' => 'Pin your location', 'text' => 'Tell us where to deliver your fuel.'],
        ['icon' => 'map',         'title' => 'Track and pay', 'text' => 'Follow your driver and pay the way you prefer.'],
    ];

    // Describe the service benefits displayed in the responsive feature grid.
    $benefits = [
        ['icon' => 'zap', 'title' => 'Fast ordering', 'text' => 'Place an order in just a few taps.'],
        ['icon' => 'badge-check', 'title' => 'Clear prices', 'text' => 'See fuel prices before you order.'],
        ['icon' => 'wallet', 'title' => 'Pay on delivery', 'text' => 'Choose a convenient way to pay.'],
        ['icon' => 'map-pinned', 'title' => 'Live tracking', 'text' => 'See your driver as they make their way.'],
        ['icon' => 'bell', 'title' => 'Order updates', 'text' => 'Stay up to date on your delivery.'],
        ['icon' => 'fuel', 'title' => 'Providers with stock', 'text' => 'Order from providers ready to serve.'],
    ];
@endphp

    <!-- Header: brand, section links, and auth-aware account actions. -->
    <header class="fd-header">
        <div class="fd-container fd-header-inner">
            <a href="{{ route('landing') }}" class="fd-brand" aria-label="FuelDrop home">
                <span class="fd-brand-tile"><i data-lucide="droplet" aria-hidden="true"></i></span>
                <span>FuelDrop</span>
            </a>
            <nav class="fd-main-nav" aria-label="Main navigation">
                <a href="#how">How it works</a>
                <a href="#why">Why FuelDrop</a>
                <a href="#prices">Prices</a>
            </nav>
            <nav class="fd-account-nav" aria-label="Account navigation">
                @auth
                    <a class="fd-button fd-button-light" href="{{ route('home') }}">Dashboard</a>
                    <a class="fd-header-contact" href="{{ route('contact') }}">Contact</a>
                @else
                    @if (Route::has('login'))
                        <a class="fd-button fd-button-light" href="{{ route('login') }}">Sign in</a>
                    @endif
                    @if (Route::has('register'))
                        <a class="fd-header-contact" href="{{ route('register') }}">Get started</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero: introduce the service, show the Uganda map, and provide a quick quote path. -->
        <section class="fd-dark-section fd-hero">
            <div class="fd-container">
                <div class="fd-hero-grid">
                    <div class="fd-hero-copy">
                        <p class="fd-eyebrow">Fuel delivery across Uganda</p>
                        <h1>Fuel, delivered to where you are.</h1>
                        <p class="fd-hero-description">Order petrol or diesel in a few taps. Track the driver and pay from your phone.</p>
                        <div class="fd-button-row">
                            @if ($showOrderButtons)
                                <a href="{{ $orderUrl }}" class="fd-button fd-button-amber">Order fuel</a>
                            @endif
                            <a href="#how" class="fd-button fd-button-outline">See how it works</a>
                        </div>
                    </div>
                    <div class="fd-map-card">
                        <img src="{{ asset('assets/img/uganda-map.jpg') }}" alt="Map of Uganda showing FuelDrop's delivery area" width="900" height="900" class="fd-hero-image">
                        <div class="fd-map-offer">
                            <p>Order Petrol or Diesel</p>
                            @if ($showOrderButtons)
                                <a href="{{ $orderUrl }}" class="fd-button fd-button-amber fd-button-small">Order now</a>
                            @endif
                        </div>
                        <span class="fd-map-label">Uganda</span>
                    </div>
                </div>

                <div class="fd-quote-bar" aria-label="Quick fuel quote details">
                    <label>Fuel
                        <select aria-label="Fuel type">
                            <option>Petrol</option>
                            <option>Diesel</option>
                            <option>Paraffin</option>
                        </select>
                    </label>
                    <label>Quantity (litres)
                        <input type="number" min="1" placeholder="e.g. 40" aria-label="Quantity in litres">
                    </label>
                    <label>District
                        <input type="text" placeholder="Your district" aria-label="Delivery district">
                    </label>
                    @if ($showOrderButtons)
                        <a href="{{ $orderUrl }}" class="fd-button fd-button-amber">Get quote</a>
                    @endif
                </div>
            </div>
        </section>

        <!-- How it works: a supporting image and three easy-to-follow delivery steps. -->
        <section class="fd-section fd-how" id="how">
            <div class="fd-container fd-split">
                <figure class="fd-photo-card fd-how-photo">
                    <img src="{{ asset('assets/img/fuel-gauge.jpg') }}" alt="Vehicle fuel gauge close to empty" width="720" height="1280" loading="lazy">
                    <figcaption>Running low? Order before it hits empty.</figcaption>
                </figure>
                <div class="fd-how-copy">
                    <p class="fd-section-kicker">Simple from start to finish</p>
                    <h2>How it works</h2>
                    <div class="fd-steps">
                        @foreach ($steps as $step)
                            <article class="fd-step">
                                <span class="fd-icon-tile"><i data-lucide="{{ $step['icon'] }}" aria-hidden="true"></i></span>
                                <div>
                                    <p class="fd-step-number">Step {{ $loop->iteration }}</p>
                                    <h3>{{ $step['title'] }}</h3>
                                    <p>{{ $step['text'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits: six concise reasons to use FuelDrop. -->
        <section class="fd-section fd-benefits" id="why">
            <div class="fd-container">
                <div class="fd-centered-heading">
                    <p class="fd-section-kicker">Made for your day</p>
                    <h2>Why choose FuelDrop?</h2>
                </div>
                <div class="fd-benefit-grid">
                    @foreach ($benefits as $benefit)
                        <article class="fd-benefit">
                            <span class="fd-icon-tile"><i data-lucide="{{ $benefit['icon'] }}" aria-hidden="true"></i></span>
                            <h3>{{ $benefit['title'] }}</h3>
                            <p>{{ $benefit['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Fuels and prices: keep the existing dynamic data-price hooks and order links. -->
        <section class="fd-section fd-prices" id="prices">
            <div class="fd-container fd-split fd-price-split">
                <figure class="fd-photo-card fd-pump-photo">
                    <img src="{{ asset('assets/img/fuel-pump.jpg') }}" alt="Fuel pump nozzle ready to fill a vehicle" width="800" height="1200" loading="lazy">
                    <figcaption>Know the price before you order.</figcaption>
                </figure>
                <div class="fd-price-copy">
                    <p class="fd-section-kicker">Fuel for the journey</p>
                    <h2>Fuels and prices</h2>
                    <p class="fd-price-intro">Compare the current price per litre before placing your order.</p>
                    <div class="fd-fuel-list">
                        @foreach ($fuels as $fuel)
                            <article class="fd-fuel-row">
                                <div>
                                    <h3>{{ $fuel['name'] }}</h3>
                                    <p>UGX <span data-price="{{ $fuel['key'] }}"></span> per litre</p>
                                </div>
                                @if ($showOrderButtons)
                                    <a href="{{ $orderUrl }}" class="fd-button fd-button-light">Order {{ strtolower($fuel['name']) }}</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- Fleet banner: direct business users to the existing contact page. -->
        <section class="fd-section fd-fleet-wrap">
            <div class="fd-container">
                <div class="fd-dark-section fd-fleet-banner">
                    <div>
                        <p class="fd-eyebrow">Fuel supply for your operation</p>
                        <h2>Running a fleet or a site?</h2>
                        <p>Manage vehicles, schedule recurring deliveries and get one invoice for every order.</p>
                    </div>
                    <a href="{{ route('contact') }}" class="fd-button fd-button-amber">Talk to us</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer: repeat the primary section links and existing contact route. -->
    <footer class="fd-footer">
        <div class="fd-container fd-footer-inner">
            <a href="{{ route('landing') }}" class="fd-brand"><span class="fd-brand-tile"><i data-lucide="droplet" aria-hidden="true"></i></span><span>FuelDrop</span></a>
            <nav aria-label="Footer navigation">
                <a href="#how">How it works</a>
                <a href="#prices">Prices</a>
                @auth
                    <a href="{{ route('home') }}">Dashboard</a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}">Sign in</a>
                    @endif
                @endauth
                <a href="{{ route('contact') }}">Contact</a>
            </nav>
        </div>
    </footer>

    <!-- Initialize the existing Lucide icon placeholders and shared page logic. -->
    <script>lucide.createIcons();</script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
