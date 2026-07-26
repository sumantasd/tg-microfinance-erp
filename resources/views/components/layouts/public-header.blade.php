<!-- Sticky Public Corporate Website Header -->
<header class="public-navbar">
    <div class="container-xl">
        <div class="d-flex align-items-center justify-content-between">
            <!-- Brand Logo -->
            <a class="navbar-brand public-brand d-flex align-items-center gap-2 me-4" href="{{ url('/') }}">
                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-bank2 fs-5"></i>
                </div>
                <span class="fs-5">TG Microfinance</span>
            </a>

            <!-- Desktop Navigation Menu -->
            <nav class="d-none d-xl-flex align-items-center gap-1">
                <!-- Home -->
                <a class="nav-link public-nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Home</a>

                <!-- About Dropdown -->
                <div class="dropdown">
                    <a class="nav-link public-nav-link dropdown-toggle {{ request()->is('about*') ? 'active' : '' }}" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        About
                    </a>
                    <ul class="dropdown-menu public-dropdown-menu" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('about') ? 'active' : '' }}" href="{{ url('/about') }}"><i class="bi bi-info-circle me-2 text-primary"></i>About Us</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('about/mission') ? 'active' : '' }}" href="{{ url('/about/mission') }}"><i class="bi bi-bullseye me-2 text-primary"></i>Mission</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('about/vision') ? 'active' : '' }}" href="{{ url('/about/vision') }}"><i class="bi bi-eye me-2 text-primary"></i>Vision</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('about/board-of-directors') ? 'active' : '' }}" href="{{ url('/about/board-of-directors') }}"><i class="bi bi-person-badge me-2 text-primary"></i>Board of Directors</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('about/management-team') ? 'active' : '' }}" href="{{ url('/about/management-team') }}"><i class="bi bi-people me-2 text-primary"></i>Management Team</a></li>
                    </ul>
                </div>

                <!-- Products Dropdown -->
                <div class="dropdown">
                    <a class="nav-link public-nav-link dropdown-toggle {{ request()->is('products*') ? 'active' : '' }}" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Products
                    </a>
                    <ul class="dropdown-menu public-dropdown-menu" aria-labelledby="productsDropdown">
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('products/loan') ? 'active' : '' }}" href="{{ url('/products/loan') }}"><i class="bi bi-cash-stack me-2 text-primary"></i>Loan Products</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('products/savings') ? 'active' : '' }}" href="{{ url('/products/savings') }}"><i class="bi bi-piggy-bank me-2 text-primary"></i>Savings Products</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('products/interest-rates') ? 'active' : '' }}" href="{{ url('/products/interest-rates') }}"><i class="bi bi-percent me-2 text-primary"></i>Interest Rates</a></li>
                    </ul>
                </div>

                <!-- Services Dropdown -->
                <div class="dropdown">
                    <a class="nav-link public-nav-link dropdown-toggle {{ request()->is('services*') ? 'active' : '' }}" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Services
                    </a>
                    <ul class="dropdown-menu public-dropdown-menu" aria-labelledby="servicesDropdown">
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('services/digital-banking') ? 'active' : '' }}" href="{{ url('/services/digital-banking') }}"><i class="bi bi-phone me-2 text-primary"></i>Digital Banking</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('services/collection-services') ? 'active' : '' }}" href="{{ url('/services/collection-services') }}"><i class="bi bi-journal-check me-2 text-primary"></i>Collection Services</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('services/financial-advisory') ? 'active' : '' }}" href="{{ url('/services/financial-advisory') }}"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Financial Advisory</a></li>
                    </ul>
                </div>

                <!-- Resources Dropdown -->
                <div class="dropdown">
                    <a class="nav-link public-nav-link dropdown-toggle {{ request()->is('resources*') ? 'active' : '' }}" href="#" id="resourcesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Resources
                    </a>
                    <ul class="dropdown-menu public-dropdown-menu" aria-labelledby="resourcesDropdown">
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('resources/gallery') ? 'active' : '' }}" href="{{ url('/resources/gallery') }}"><i class="bi bi-images me-2 text-primary"></i>Gallery</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('resources/downloads') ? 'active' : '' }}" href="{{ url('/resources/downloads') }}"><i class="bi bi-file-earmark-arrow-down me-2 text-primary"></i>Downloads</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('resources/news') ? 'active' : '' }}" href="{{ url('/resources/news') }}"><i class="bi bi-newspaper me-2 text-primary"></i>News</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('resources/faq') ? 'active' : '' }}" href="{{ url('/resources/faq') }}"><i class="bi bi-question-circle me-2 text-primary"></i>FAQ</a></li>
                        <li><a class="dropdown-item public-dropdown-item {{ request()->is('resources/career') ? 'active' : '' }}" href="{{ url('/resources/career') }}"><i class="bi bi-briefcase me-2 text-primary"></i>Career</a></li>
                    </ul>
                </div>

                <!-- Branches -->
                <a class="nav-link public-nav-link {{ request()->is('branches') ? 'active' : '' }}" href="{{ url('/branches') }}">Branches</a>

                <!-- Contact -->
                <a class="nav-link public-nav-link {{ request()->is('contact') ? 'active' : '' }}" href="{{ url('/contact') }}">Contact</a>
            </nav>

            <!-- Desktop CTAs -->
            <div class="d-none d-xl-flex align-items-center gap-2">
                <a href="{{ url('/apply-loan') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Apply Loan</span>
                </a>
                <a href="{{ url('/login') }}" class="btn btn-outline-secondary rounded-pill px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-lock-fill"></i>
                    <span>Staff Login</span>
                </a>
            </div>

            <!-- Mobile Offcanvas Toggler Button -->
            <button class="btn btn-light d-xl-none border-0 shadow-none p-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavOffcanvas" aria-controls="mobileNavOffcanvas" aria-label="Toggle navigation">
                <i class="bi bi-list fs-3 text-dark"></i>
            </button>
        </div>
    </div>
</header>

<!-- Bootstrap 5 Offcanvas Mobile Navigation Drawer -->
<div class="offcanvas offcanvas-end public-offcanvas" tabindex="-1" id="mobileNavOffcanvas" aria-labelledby="mobileNavOffcanvasLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2" id="mobileNavOffcanvasLabel">
            <div class="bg-primary text-white rounded-circle p-1.5 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                <i class="bi bi-bank2 fs-6"></i>
            </div>
            <span class="fw-bold text-white fs-6">TG Microfinance</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column justify-content-between">
        <!-- Navigation List -->
        <div class="nav flex-column gap-1 mb-4">
            <a class="nav-link public-nav-link text-dark fw-bold py-2 {{ request()->is('/') ? 'text-primary' : '' }}" href="{{ url('/') }}">
                <i class="bi bi-house me-2 text-primary"></i> Home
            </a>

            <!-- Mobile About Accordion -->
            <div class="accordion accordion-flush mb-1" id="mobileAboutAccordion">
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ request()->is('about*') ? 'text-primary' : 'collapsed' }} px-2 py-2 bg-transparent text-dark fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileAboutCollapse" aria-expanded="false">
                            <i class="bi bi-info-circle me-2 text-primary"></i> About
                        </button>
                    </h2>
                    <div id="mobileAboutCollapse" class="accordion-collapse collapse {{ request()->is('about*') ? 'show' : '' }}" data-bs-parent="#mobileAboutAccordion">
                        <div class="ps-4 py-1 d-flex flex-column gap-1">
                            <a href="{{ url('/about') }}" class="small text-secondary text-decoration-none py-1">About Us</a>
                            <a href="{{ url('/about/mission') }}" class="small text-secondary text-decoration-none py-1">Mission</a>
                            <a href="{{ url('/about/vision') }}" class="small text-secondary text-decoration-none py-1">Vision</a>
                            <a href="{{ url('/about/board-of-directors') }}" class="small text-secondary text-decoration-none py-1">Board of Directors</a>
                            <a href="{{ url('/about/management-team') }}" class="small text-secondary text-decoration-none py-1">Management Team</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Products Accordion -->
            <div class="accordion accordion-flush mb-1" id="mobileProductsAccordion">
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ request()->is('products*') ? 'text-primary' : 'collapsed' }} px-2 py-2 bg-transparent text-dark fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileProductsCollapse" aria-expanded="false">
                            <i class="bi bi-box-seam me-2 text-primary"></i> Products
                        </button>
                    </h2>
                    <div id="mobileProductsCollapse" class="accordion-collapse collapse {{ request()->is('products*') ? 'show' : '' }}" data-bs-parent="#mobileProductsAccordion">
                        <div class="ps-4 py-1 d-flex flex-column gap-1">
                            <a href="{{ url('/products/loan') }}" class="small text-secondary text-decoration-none py-1">Loan Products</a>
                            <a href="{{ url('/products/savings') }}" class="small text-secondary text-decoration-none py-1">Savings Products</a>
                            <a href="{{ url('/products/interest-rates') }}" class="small text-secondary text-decoration-none py-1">Interest Rates</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Services Accordion -->
            <div class="accordion accordion-flush mb-1" id="mobileServicesAccordion">
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ request()->is('services*') ? 'text-primary' : 'collapsed' }} px-2 py-2 bg-transparent text-dark fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileServicesCollapse" aria-expanded="false">
                            <i class="bi bi-gear me-2 text-primary"></i> Services
                        </button>
                    </h2>
                    <div id="mobileServicesCollapse" class="accordion-collapse collapse {{ request()->is('services*') ? 'show' : '' }}" data-bs-parent="#mobileServicesAccordion">
                        <div class="ps-4 py-1 d-flex flex-column gap-1">
                            <a href="{{ url('/services/digital-banking') }}" class="small text-secondary text-decoration-none py-1">Digital Banking</a>
                            <a href="{{ url('/services/collection-services') }}" class="small text-secondary text-decoration-none py-1">Collection Services</a>
                            <a href="{{ url('/services/financial-advisory') }}" class="small text-secondary text-decoration-none py-1">Financial Advisory</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Resources Accordion -->
            <div class="accordion accordion-flush mb-1" id="mobileResourcesAccordion">
                <div class="accordion-item bg-transparent border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ request()->is('resources*') ? 'text-primary' : 'collapsed' }} px-2 py-2 bg-transparent text-dark fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileResourcesCollapse" aria-expanded="false">
                            <i class="bi bi-folder2-open me-2 text-primary"></i> Resources
                        </button>
                    </h2>
                    <div id="mobileResourcesCollapse" class="accordion-collapse collapse {{ request()->is('resources*') ? 'show' : '' }}" data-bs-parent="#mobileResourcesAccordion">
                        <div class="ps-4 py-1 d-flex flex-column gap-1">
                            <a href="{{ url('/resources/gallery') }}" class="small text-secondary text-decoration-none py-1">Gallery</a>
                            <a href="{{ url('/resources/downloads') }}" class="small text-secondary text-decoration-none py-1">Downloads</a>
                            <a href="{{ url('/resources/news') }}" class="small text-secondary text-decoration-none py-1">News</a>
                            <a href="{{ url('/resources/faq') }}" class="small text-secondary text-decoration-none py-1">FAQ</a>
                            <a href="{{ url('/resources/career') }}" class="small text-secondary text-decoration-none py-1">Career</a>
                        </div>
                    </div>
                </div>
            </div>

            <a class="nav-link public-nav-link text-dark fw-bold py-2 {{ request()->is('branches') ? 'text-primary' : '' }}" href="{{ url('/branches') }}">
                <i class="bi bi-geo-alt me-2 text-primary"></i> Branches
            </a>

            <a class="nav-link public-nav-link text-dark fw-bold py-2 {{ request()->is('contact') ? 'text-primary' : '' }}" href="{{ url('/contact') }}">
                <i class="bi bi-envelope me-2 text-primary"></i> Contact
            </a>
        </div>

        <!-- Mobile Action Buttons -->
        <div class="pt-3 border-top d-flex flex-column gap-2">
            <a href="{{ url('/apply-loan') }}" class="btn btn-primary rounded-pill py-2.5 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-file-earmark-text"></i> Apply Loan
            </a>
            <a href="{{ url('/login') }}" class="btn btn-outline-secondary rounded-pill py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-lock-fill"></i> Staff Login
            </a>
        </div>
    </div>
</div>
