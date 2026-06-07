<?php

namespace Preciouslyson\MachinjiriInstaller\StarterKits;

class DefaultKit
{
  public static $options = [];
  public static function getOptions(array $options): void 
  {
    self::$options = $options;
  }
  public static function files(): array 
  {
    return [
      'routes' => [
          'file' => '/routes/web.php',
          'template' => self::RoutesTemplate(),
        ],
      'welcome' => [
          'file' => '/resources/views/welcome.view.php',
          'template' => self::WelcomeTemplate(),
        ],
      'home-controller' => [
          'file' => '/app/Controllers/HomeController.php',
          'template' => self::HomeControllerTemplate(),
        ],
    ];
  }
  
  private static function RoutesTemplate(): string { return <<<'PHP'
<?php
use Mlangeni\Machinjiri\Core\Routing\Router;
/**
 * Web Routes
 * Define your web routes here.
 * You can create additional route files as needed.
 * Remember to keep your routes organized and manageable.
 */

/* Welcome Route */
Router::get('/', 'HomeController@index', 'welcome');




/* Dispatch the router to handle the incoming request */
Router::dispatch();

PHP;
    }

  private static function HomeControllerTemplate(): string { return <<<PHP
<?php

namespace Mlangeni\Machinjiri\App\Controllers;

use Mlangeni\Machinjiri\Core\Artisans\Base\AbstractController;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;

class HomeController extends AbstractController
{
    /**
     * Display the welcome page.
     *
     * @param HttpRequest  \$request
     * @param HttpResponse \$response
     * @return string|HttpResponse
     */
    public function index(HttpRequest \$request, HttpResponse \$response)
    {
        // Example: render a view
        return \$this->view('welcome');
    }
    
    // Add your custom methods below
}
PHP;
  }

  private static function WelcomeTemplate(): string { 
    $version = (self::$options['version'] === '*') ? 'Latest' : self::$options['version'];
    $date = self::$options['date'];
    $appName = self::$options['project_name'];

    return <<<HTML
<?php use Mlangeni\\Machinjiri\\Core\\Views\\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Machinjiri - Your Cozy Dev Space</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #FCF7F0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', 'Poppins', 'Roboto', sans-serif;
            color: #2E2C2A;
            line-height: 1.5;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
        }

        /* cozy background texture */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(#E8DCCC 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
        }

        .cozy-container {
            max-width: 1280px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* header section – soft and glowing */
        .welcome-header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeSlideUp 0.6s ease-out;
        }

        .cozy-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #FFF3E6;
            padding: 0.5rem 1.2rem;
            border-radius: 60px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #C97E5A;
            border: 1px solid #FFE2CC;
            margin-bottom: 1.2rem;
            backdrop-filter: blur(2px);
        }

        .cozy-badge i {
            font-size: 1rem;
        }

        .welcome-header h1 {
            font-size: 3.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #E68A5E, #C4633A);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .welcome-header h1 i {
            background: none;
            color: #E68A5E;
            font-size: 2.8rem;
        }

        .tagline {
            font-size: 1.2rem;
            color: #6B5E53;
            max-width: 580px;
            margin: 0 auto;
            background: rgba(255, 245, 235, 0.7);
            padding: 0.6rem 1.4rem;
            border-radius: 48px;
            backdrop-filter: blur(4px);
        }

        /* cozy card style */
        .card {
            background: #FFFFFFDD;
            backdrop-filter: blur(2px);
            border-radius: 32px;
            padding: 1.8rem;
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.25s ease;
            border: 1px solid #F2E5D8;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 32px -12px rgba(90, 50, 25, 0.12);
            border-color: #FADDC7;
        }

        /* grid layout */
        .grid-2cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.8rem;
            margin-bottom: 2.5rem;
        }

        .detail-list {
            list-style: none;
            margin-top: 0.8rem;
        }

        .detail-list li {
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-list li i {
            color: #E68A5E;
            width: 22px;
            font-size: 1rem;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #F0EEE9;
            padding: 0.35rem 1rem;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #926B4B;
            margin-top: 1rem;
            border: 1px solid #E9DDD0;
        }

        /* quick actions grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0 2rem;
        }

        .action-item {
            background: #FFFFFF;
            border-radius: 28px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid #F1E3D6;
            box-shadow: 0 6px 12px -6px rgba(0, 0, 0, 0.03);
        }

        .action-item:hover {
            background: #FFF9F3;
            border-color: #EECEB6;
            transform: scale(0.98);
        }

        .action-icon {
            background: #FCE9DE;
            width: 64px;
            height: 64px;
            border-radius: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: #E68A5E;
        }

        .action-item h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #3E332A;
        }

        .action-item p {
            font-size: 0.85rem;
            color: #7C6B5C;
            margin-bottom: 1.2rem;
        }

        .cozy-btn {
            background: #E68A5E;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
        }

        .cozy-btn.outline {
            background: transparent;
            border: 1px solid #E6CFBC;
            color: #A57253;
        }

        .cozy-btn.outline:hover {
            background: #F8EDE3;
            border-color: #E68A5E;
            color: #C4633A;
        }

        .cozy-btn:hover {
            background: #CD7350;
            transform: translateY(-2px);
        }

        /* steps area */
        .steps-wrapper {
            background: #FFF9F3;
            border-radius: 32px;
            padding: 2rem;
            margin: 1.5rem 0;
            border: 1px solid #F7E8DB;
        }

        .steps-title {
            font-size: 1.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.8rem;
            color: #3E332A;
        }

        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .step-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem 1.4rem;
            border-radius: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            border: 1px solid #F3E4D5;
            transition: all 0.2s;
        }

        .step-number {
            background: #FCE5D8;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1.2rem;
            color: #E68A5E;
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .step-content p {
            font-size: 0.9rem;
            color: #6F5E4E;
        }

        .step-content code {
            background: #F5EDE4;
            padding: 0.2rem 0.6rem;
            border-radius: 24px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #C4633A;
        }

        .footer-cozy {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: #A48E78;
            border-top: 1px solid #F0E0D2;
            padding-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .footer-links a {
            color: #B47C5A;
            text-decoration: none;
            margin: 0 0.7rem;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #E68A5E;
            text-decoration: underline;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 680px) {
            body { padding: 1.2rem; }
            .welcome-header h1 { font-size: 2.2rem; }
            .tagline { font-size: 1rem; }
            .step-row { flex-direction: column; align-items: flex-start; }
            .footer-cozy { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="cozy-container">
    <div class="welcome-header">
        <div class="cozy-badge">
            <i class="fas fa-mug-hot"></i> <span>fresh install · ready to create</span> <i class="fas fa-heart" style="color: #E68A5E;"></i>
        </div>
        <h1>
            <i class="fas fa-feather-alt"></i> 
            You're all set!
        </h1>
        <div class="tagline">Machinjiri is installed — cozy, fast, and waiting for your ideas.
        </div>
    </div>

    <!-- main content grid: system details + components -->
    <div class="grid-2cols">
        <div class="card">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.2rem;">
                <i class="fas fa-rocket" style="font-size: 1.8rem; color: #E68A5E;"></i>
                <h2 style="font-size: 1.5rem; font-weight: 600;">App snapshot</h2>
            </div>
            <div>
                <p><strong>Name:</strong> Machinjiri • <span style="background:#F7EFE7; padding:2px 8px; border-radius:20px;">$appName</span></p>
                <p><strong>Release:</strong> $version <i class="fas fa-check-circle" style="color:#7AA77B;"></i></p>
                <p><strong>Born on:</strong> 2026-05-18 <i class="fas fa-calendar-alt" style="color:#B58E6B;"></i></p>
                <div class="status-chip">
                    <i class="fas fa-shield-alt"></i> Verified & cozy-ready
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                <i class="fas fa-puzzle-piece" style="font-size: 1.6rem; color: #E68A5E;"></i>
                <h2 style="font-size: 1.5rem; font-weight: 600;">Components installed</h2>
            </div>
            <ul class="detail-list">
                <li><i class="fas fa-cubes"></i> Core Framework + CLI</li>
                <li><i class="fas fa-database"></i> Database ORM (Eloquent-like)</li>
                <li><i class="fas fa-id-card"></i> Auth & Session system</li>
                <li><i class="fas fa-cloud-sun"></i> API Toolkit & Middleware</li>
                <li><i class="fas fa-vial"></i> Testing Suite ready</li>
                <li><i class="fas fa-laptop-code"></i> Dev server + hot reload</li>
            </ul>
        </div>
    </div>

    <!-- Quick actions - catchy & cozy -->
    <div class="action-grid">
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-book-open"></i></div>
            <h3>Read the docs</h3>
            <p>Cozy tutorials, API references, and best practices.</p>
            <a href="https://github.com/preciouslyson/machinjiri" target="_blank" class="cozy-btn outline">Explore docs →</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-terminal"></i></div>
            <h3>CLI power</h3>
            <p>Generate models, controllers, and migrations.</p>
            <a href="https://github.com/preciouslyson/machinjiri#Console" class="cozy-btn outline">php artisan list</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-users"></i></div>
            <h3>Community</h3>
            <p>Join discord, share your cozy creations.</p>
            <a href="#" class="cozy-btn outline">Join the hub →</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-mug-saucer"></i></div>
            <h3>First app</h3>
            <p>Build a "Hello, cozy world" in 2 minutes.</p>
            <a href="#" class="cozy-btn outline">Start building</a>
        </div>
    </div>

    <!-- Next steps - cozy roadmap -->
    <div class="steps-wrapper">
        <div class="steps-title">
            <i class="fas fa-map-signs"></i> your next cozy steps
        </div>
        <div class="steps-container">
            <div class="step-row">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Run the dev server</h4>
                    <p>In your project root, type <code>php artisan server:start</code> and visit <code>http://localhost:3000</code></p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Create your first route</h4>
                    <p>Open <code>routes/web.php</code> and add: <code>Route::get('/welcome', 'HomeController@index', 'welcome');</code></p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Explore the starter kit</h4>
                    <p>Check <code>/resources/views</code> and customize your cozy layout.</p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>Configure .env</h4>
                    <p>Set database, app URL and enjoy full framework magic.</p>
                </div>
            </div>
        </div>
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="https://github.com/preciouslyson/machinjiri#Introduction" class="cozy-btn"><i class="fas fa-graduation-cap"></i> full getting started guide</a>
        </div>
    </div>

    <!-- footer cozy love -->
    <div class="footer-cozy">
        <div>
            <i class="fas fa-heart" style="color: #E68A5E;"></i> Machinjiri — where code meets comfort
        </div>
        <div class="footer-links">
            <a href="https://github.com/preciouslyson/machinjiri"><i class="fab fa-github"></i> GitHub</a>
            <a href="https://github.com/preciouslyson/machinjiri/support"><i class="fas fa-life-ring"></i> Support</a>
            <a href="https://github.com/preciouslyson/machinjiri/feedback"><i class="fas fa-comment-dots"></i> Feedback</a>
        </div>
        <div>
            &copy; 2024 - $date
        </div>
    </div>
</div>
</body>
</html>
HTML;
    }
}