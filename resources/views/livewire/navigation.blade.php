<main>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        :root {
            --primary: #8B5FBF;
            --primary-light: #9D76C1;
            --secondary: #FF6B95;
            --accent: #FFD166;
            --dark: #2D3047;
            --light: #F8F9FA;
            --gray: #6C757D;
            --text: #333333;
        }

        .container.header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background: var(--dark);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--light);
        }

        .logo img {
            height: 60px;
            width: auto;
            border-radius: 15px;
        }

        .logo-text {
            display: flex;
            gap: 6px;
        }

        .word {
            color: white;
            animation: colorFlow 4.5s infinite ease-in-out;
        }

        .book {
            animation-delay: 0s;
        }

        .my {
            animation-delay: 1.5s;
        }

        .style {
            animation-delay: 3s;
        }

        @keyframes colorFlow {
            0% {
                color: white;
            }
            10%, 30% {
                color: var(--secondary);
            }
            50%, 100% {
                color: white;
            }
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        nav a:hover,
        nav a.active {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background: #ff4d7d;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 107, 149, 0.4);
        }

        .btn-outline {
            border: 2px solid white;
            color: white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
        }

        /* Mobile Menu Button */
        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .menu-toggle span {
            display: block;
            height: 3px;
            width: 100%;
            background-color: white;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        /* Tablet Responsive */
        @media (max-width: 1024px) {
            .header-content {
                flex-wrap: wrap;
            }

            nav {
                order: 3;
                width: 100%;
                margin-top: 1rem;
            }

            nav ul {
                justify-content: center;
                gap: 1rem;
            }
        }

        /* Mobile View */
        @media (max-width: 1024px) {
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border-radius: 9999px;
                background: rgba(255, 255, 255, 0.12);
            }

            nav {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(45, 48, 71, 0.96);
                backdrop-filter: blur(8px);
                padding: 1rem 1rem 1.25rem;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                border-bottom-left-radius: 16px;
                border-bottom-right-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
                display: none;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }

            nav.active {
                display: flex;
                animation: slideDown 0.3s ease forwards;
            }

            @keyframes slideDown {
                0% {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            nav ul {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
                width: 100%;
            }

            nav a {
                display: block;
                width: 100%;
                text-align: left;
                padding: 0.8rem 1rem;
                border-radius: 12px;
            }

            /* Hide desktop actions */
            .header-actions-desktop {
                display: none;
            }

            /* Show mobile actions */
            .header-actions {
                flex-direction: column;
                width: 100%;
                align-items: center;
                gap: 0.75rem;
                margin-top: 0.25rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 0.7rem 1rem;
                font-size: 1rem;
            }
        }

        /* Smaller phones */
        @media (max-width: 480px) {
            .container.header-content {
                padding: 0 15px;
            }

            header {
                padding: 1rem 0;
            }

            .logo {
                font-size: 1.3rem;
                gap: 8px;
            }

            .logo img {
                height: 40px;
            }

            nav a {
                font-size: 0.95rem;
            }

            .btn {
                width: 90%;
                font-size: 0.9rem;
                padding: 0.6rem;
            }
        }
        /* Default (Desktop): Hide mobile header buttons */
.header-actions {
    display: flex;
    gap: 10px;
}

/* If these buttons are meant for mobile only */
.mobile-header-actions {
    display: none;
}

/* Responsive for Mobile View */
@media (max-width: 1024px) {
    /* Hide desktop header buttons */
    .header-actions {
        display: none;
    }

    /* Show mobile header buttons */
.mobile-header-actions {
    display: flex;                /* Use flex for proper alignment */
    flex-direction: column;       /* Stack buttons vertically */
    gap: 0.75rem;                 /* Space between buttons */
    align-items: center;          /* Center horizontally */
    padding: 0.6rem 0;            /* Vertical padding */
    width: 100%;                  /* Take full width */
}

}

    </style>

    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="{{ asset('frontend/images/logo2.png') }}" alt="Book My Style Logo">
                <span class="logo-text">
                    <span class="word book">Book</span>
                    <span class="word my">My</span>
                    <span class="word style">Style</span>
                </span>
            </div>

            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav id="mainNav">
                <ul>
                    <li><a href="#" class="active">Home</a></li>
                    <li><a href="#">Salons</a></li>
                    <li><a href="#">Services</a></li>
                    <li><a href="{{ route('deals') }}">Deals</a></li>
                    <li><a href="#">About</a></li>
                </ul>

                <!-- Mobile-only buttons -->
                <div class="mobile-header-actions">
                    <a href="#" class="btn btn-outline"><i class="fas fa-user"></i> <span>Sign In</span></a>
                    <a href="#" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> <span>Become a Owner</span></a>
                </div>
            </nav>

            <!-- Desktop-only buttons -->
            <div class="header-actions">
                <a href="#" class="btn btn-outline"><i class="fas fa-user"></i> <span>Sign In</span></a>
                <a href="#" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> <span>Become a Owner</span></a>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const mainNav = document.getElementById('mainNav');

            menuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');

                const spans = menuToggle.querySelectorAll('span');
                if (mainNav.classList.contains('active')) {
                    spans[0].style.transform = 'rotate(45deg) translate(6px, 6px)';
                    spans[1].style.opacity = '0';
                    spans[2].style.transform = 'rotate(-45deg) translate(6px, -6px)';
                } else {
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
            });

            // Close menu when clicking a link
            const navLinks = mainNav.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mainNav.classList.remove('active');
                    const spans = menuToggle.querySelectorAll('span');
                    spans.forEach(span => span.removeAttribute('style'));
                });
            });

            // Close when clicking outside
            document.addEventListener('click', e => {
                if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                    mainNav.classList.remove('active');
                    const spans = menuToggle.querySelectorAll('span');
                    spans.forEach(span => span.removeAttribute('style'));
                }
            });
        });
    </script>
</main>
