<?php

namespace Mlangeni\Machinjiri\Installer\StarterKits;

class Blog
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
      'blog' => [
          'file' => '/resources/views/blog.view.php',
          'template' => self::BlogTemplate(),
        ],
      'home-controller' => [
          'file' => '/app/Controllers/BlogController.php',
          'template' => self::HomeControllerTemplate(),
        ],
      'layout' => [
          'file' => '/resources/views/layouts/blog.layout.php',
          'template' => self::LayoutTemplate(),
        ],
      'css' => [
          'file' => '/public/src/css/main.css',
          'template' => self::BlogStyles(),
        ],
      'js' => [
          'file' => '/public/src/js/app.js',
          'template' => self::BlogScript(),
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

Router::get('/', 'BlogController@index', 'blog.home');




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

class BlogController extends AbstractController
{
    /**
     * Display the blog template
     *
     * @param HttpRequest  \$request
     * @param HttpResponse \$response
     * @return string|HttpResponse
     */
    public function index(HttpRequest \$request, HttpResponse \$response)
    {
        // render the blog view
        return \$this->view('blog', ['name' => 'Blog Template']);
    }
    
    // Add your custom methods below
}
PHP;
  }

  private static function BlogTemplate(): string { return <<<'PHP'
<?php use Mlangeni\Machinjiri\Core\Views\View; ?>
<% extend 'layouts/blog' %>

<% section('content') %>
<nav class="navbar fixed-top navbar-expand-lg" id="mainNavbar">
  <div class="container">
      <a class="navbar-brand" href="#">
          Machinjiri<span class="brand-dot"></span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent"
      aria-controls="navContent" aria-expanded="false" aria-label="Toggle navigation">
      <i class="bi bi-list" style="font-size:1.5rem;"></i>
  </button>
  <div class="collapse navbar-collapse" id="navContent">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Technology</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Design</a></li>
          <li class="nav-item"><a class="nav-link" href="#">Culture</a></li>
          <li class="nav-item"><a class="nav-link" href="#">About</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2 position-relative">
          <button class="nav-icon-btn" id="searchToggle" title="Search">
              <i class="bi bi-search"></i>
          </button>
          <div class="search-slide" id="searchSlide">
              <input type="text" class="form-control" id="searchInput" placeholder="Search articles...">
          </div>
          <button class="nav-icon-btn d-none d-lg-flex" id="themeToggle" title="Theme">
              <i class="bi bi-moon-stars"></i>
          </button>
      </div>
  </div>
</div>
</nav>

<main class="container pt-4">
  <!-- ── Featured Post ── -->
  <article class="featured-post" id="featuredPost">
      <div class="featured-image" aria-label="Featured blog post image"></div>
      <div class="featured-content">
          <span class="category-badge">Featured</span>
          <h2>The Art of Mindful Productivity in a Distracted World</h2>
          <p class="excerpt">
              Discover how intentional focus and deep work can transform your creative output.
              We explore practical strategies backed by neuroscience to reclaim your attention.
          </p>
          <div class="meta">
              <img src="https://i.pravatar.cc/80?img=12" alt="Author" class="author-avatar" loading="lazy">
              <span><strong>Eleanor Vance</strong> &nbsp;·&nbsp; 8 min read &nbsp;·&nbsp; May 18, 2026</span>
          </div>
          <a href="#" class="read-more-arrow mt-2">Read full article <i class="bi bi-arrow-right"></i></a>
      </div>
  </article>

  <!-- ── Blog Grid + Sidebar ── -->
  <div class="row g-4">
      <!-- Blog Cards Column -->
      <div class="col-lg-8">
          <div class="d-flex justify-content-between align-items-center mb-3">
              <h4 class="fw-bold mb-0" style="letter-spacing:-0.02em;">Latest Articles</h4>
              <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" type="button"
                  data-bs-toggle="dropdown">
                  Newest
              </button>
              <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow-sm border-light">
                  <li><a class="dropdown-item" href="#">Newest</a></li>
                  <li><a class="dropdown-item" href="#">Most Popular</a></li>
                  <li><a class="dropdown-item" href="#">Longest Read</a></li>
              </ul>
          </div>
      </div>
      <div class="row g-4" id="blogGrid">
          <!-- Card 1 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Technology</span>
                      <h5>Building Resilient Systems with Modern Architecture Patterns</h5>
                      <p class="card-excerpt">A deep dive into microservices, event-driven design, and how to keep
                      systems standing when things go wrong.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=33" alt="Author" loading="lazy">
                              <span>Marcus Chen · 6 min</span>
                          </div>
                          <button class="like-btn" data-likes="42">
                              <i class="bi bi-heart"></i> <span>42</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Card 2 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Design</span>
                      <h5>The Subtle Psychology of Color in Digital Product Design</h5>
                      <p class="card-excerpt">How hue, saturation, and contrast shape user emotion and behavior
                      in ways most designers overlook.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=45" alt="Author" loading="lazy">
                              <span>Aria Kapoor · 5 min</span>
                          </div>
                          <button class="like-btn" data-likes="87">
                              <i class="bi bi-heart"></i> <span>87</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Card 3 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Culture</span>
                      <h5>Remote Work at Scale: Lessons from Five Years of Async Collaboration</h5>
                      <p class="card-excerpt">What works, what doesn't, and the surprising rituals that keep
                      distributed teams thriving.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=22" alt="Author" loading="lazy">
                              <span>James Okonkwo · 7 min</span>
                          </div>
                          <button class="like-btn" data-likes="156">
                              <i class="bi bi-heart"></i> <span>156</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Card 4 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Technology</span>
                      <h5>Rust in Production: A Practical Guide for Backend Teams</h5>
                      <p class="card-excerpt">Memory safety meets real-world deadlines. How teams are adopting
                      Rust without losing velocity.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=55" alt="Author" loading="lazy">
                              <span>Lena Müller · 9 min</span>
                          </div>
                          <button class="like-btn" data-likes="203">
                              <i class="bi bi-heart"></i> <span>203</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Card 5 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1432821596592-e2c18b78144f?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Design</span>
                      <h5>Typography That Speaks: Choosing Fonts with Intention</h5>
                      <p class="card-excerpt">Beyond aesthetics — how typeface selection impacts readability,
                      trust, and brand perception.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=16" alt="Author" loading="lazy">
                              <span>Sophie Laurent · 4 min</span>
                          </div>
                          <button class="like-btn" data-likes="61">
                              <i class="bi bi-heart"></i> <span>61</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Card 6 -->
          <div class="col-md-6">
              <div class="blog-card">
                  <div class="card-img-wrapper">
                      <img src="https://images.unsplash.com/photo-1559526324-4b87b5e9e4b5?w=600&h=375&fit=crop"
                      alt="Post" loading="lazy">
                  </div>
                  <div class="card-body">
                      <span class="category-badge">Culture</span>
                      <h5>The Quiet Revolution of Indie Publishing on the Web</h5>
                      <p class="card-excerpt">How personal blogs and newsletters are reshaping the media
                      landscape one subscriber at a time.</p>
                      <div class="card-meta">
                          <div class="author-mini">
                              <img src="https://i.pravatar.cc/60?img=8" alt="Author" loading="lazy">
                              <span>Raj Patel · 5 min</span>
                          </div>
                          <button class="like-btn" data-likes="119">
                              <i class="bi bi-heart"></i> <span>119</span>
                          </button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <!-- Pagination -->
      <nav class="mt-4" aria-label="Blog pagination">
          <ul class="pagination justify-content-center">
              <li class="page-item disabled"><a class="page-link rounded-pill px-3 border-light" href="#">Prev</a></li>
              <li class="page-item active"><a class="page-link rounded-pill px-3 border-light" href="#">1</a></li>
              <li class="page-item"><a class="page-link rounded-pill px-3 border-light" href="#">2</a></li>
              <li class="page-item"><a class="page-link rounded-pill px-3 border-light" href="#">3</a></li>
              <li class="page-item"><a class="page-link rounded-pill px-3 border-light" href="#">Next</a></li>
          </ul>
      </nav>
  </div>

  <!-- Sidebar Column -->
  <aside class="col-lg-4">
      <!-- About -->
      <div class="sidebar-section text-center">
          <img src="https://i.pravatar.cc/120?img=12" alt="Editor" class="rounded-circle mb-3"
          style="width:80px;height:80px;object-fit:cover;border:3px solid var(--border-light);" loading="lazy">
          <h6 class="mb-1 fw-bold" style="text-transform:none;letter-spacing:0;font-size:1rem;">Eleanor Vance</h6>
          <p class="text-muted small mb-0">Editor-in-Chief at Machinjiri. Writing about technology, design, and the
          human experience. Based in Portland.</p>
      </div>

      <!-- Recent Posts -->
      <div class="sidebar-section">
          <h6>Recent Posts</h6>
          <a href="#" class="sidebar-recent-item">
              <img src="https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=120&h=120&fit=crop"
              alt="Recent" class="recent-thumb" loading="lazy">
              <div class="recent-info">
                  <span class="recent-title">The Art of Mindful Productivity</span>
                  <span class="recent-date">May 18, 2026</span>
              </div>
          </a>
          <a href="#" class="sidebar-recent-item">
              <img src="https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=120&h=120&fit=crop"
              alt="Recent" class="recent-thumb" loading="lazy">
              <div class="recent-info">
                  <span class="recent-title">Building Resilient Systems</span>
                  <span class="recent-date">May 15, 2026</span>
              </div>
          </a>
          <a href="#" class="sidebar-recent-item">
              <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&h=120&fit=crop"
              alt="Recent" class="recent-thumb" loading="lazy">
              <div class="recent-info">
                  <span class="recent-title">Psychology of Color in Design</span>
                  <span class="recent-date">May 12, 2026</span>
              </div>
          </a>
          <a href="#" class="sidebar-recent-item">
              <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=120&h=120&fit=crop"
              alt="Recent" class="recent-thumb" loading="lazy">
              <div class="recent-info">
                  <span class="recent-title">Remote Work at Scale</span>
                  <span class="recent-date">May 9, 2026</span>
              </div>
          </a>
      </div>

      <!-- Tags -->
      <div class="sidebar-section">
          <h6>Popular Tags</h6>
          <div class="tag-cloud">
              <span class="tag">JavaScript</span>
              <span class="tag">Rust</span>
              <span class="tag">UX Design</span>
              <span class="tag">Productivity</span>
              <span class="tag">Remote Work</span>
              <span class="tag">Typography</span>
              <span class="tag">Microservices</span>
              <span class="tag">CSS</span>
              <span class="tag">AI</span>
              <span class="tag">Startups</span>
              <span class="tag">Open Source</span>
              <span class="tag">Writing</span>
          </div>
      </div>

      <!-- Newsletter -->
      <div class="newsletter-box" id="newsletterBox">
          <h5>Stay Inspired</h5>
          <p>Get the best articles delivered straight to your inbox. No spam, ever.</p>
          <div class="d-flex gap-2">
              <input type="email" class="form-control" id="newsletterEmail" placeholder="your@email.com">
              <button class="btn btn-subscribe" id="newsletterBtn">Subscribe</button>
          </div>
          <small class="d-block mt-2" style="color:#a8a29e;">Join 12,000+ readers.</small>
      </div>
  </aside>
</div>
</main>

<footer class="blog-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand">Machinjiri<span style="color:var(--accent);">.</span></div>
                <p class="text-muted small mt-2">A space for thoughtful writing on technology, design, and culture.
                Founded in 2024.</p>
                <div class="social-icons mt-3">
                    <a href="#" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" title="GitHub"><i class="bi bi-github"></i></a>
                    <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" title="RSS"><i class="bi bi-rss"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Explore</h6>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Technology</a></li>
                    <li><a href="#">Design</a></li>
                    <li><a href="#">Culture</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Company</h6>
                <ul class="footer-links">
                    <li><a href="#">About</a></li>
                    <li><a href="#">Team</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">Legal</h6>
                <ul class="footer-links">
                    <li><a href="#">Privacy</a></li>
                    <li><a href="#">Terms</a></li>
                    <li><a href="#">Cookies</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="fw-bold small text-uppercase text-muted mb-3">More</h6>
                <ul class="footer-links">
                    <li><a href="#">Newsletter</a></li>
                    <li><a href="#">RSS Feed</a></li>
                    <li><a href="#">Sitemap</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-3" style="border-color:var(--border-light);">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <p class="text-muted small mb-0">&copy; 2026 Machinjiri Blog. All rights reserved.</p>
            <p class="text-muted small mb-0">Crafted with <i class="bi bi-heart-fill text-danger"></i> for thoughtful
            readers.</p>
        </div>
    </div>
</footer>
<button class="back-to-top" id="backToTop" title="Back to top">
    <i class="bi bi-arrow-up"></i>
</button>
<div class="custom-toast" id="customToast"></div>
<% endsection %>
PHP;
  }
  
  private static function LayoutTemplate() { return <<<'PHP'
<?php use Mlangeni\Machinjiri\Core\Views\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machinjiri — {{ $name ?? "Blog" }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <?php View::style('css/main.css') ?>
</head>
<body>
  <?php View::yield('content')?>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
  </script>
  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js">
  </script>
  <?php View::script('js/app.js')?>
</body>
</html>
PHP;
  }
  
  private static function BlogStyles() { return <<<'STUB'
:root {
  --bg-primary: #fafaf9;
  --bg-secondary: #ffffff;
  --bg-tertiary: #f5f0eb;
  --text-primary: #1c1917;
  --text-secondary: #57534e;
  --text-muted: #a8a29e;
  --accent: #c2410c;
  --accent-hover: #9a3412;
  --accent-soft: #fff7ed;
  --border: #e7e5e4;
  --border-light: #f0efed;
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 12px 40px rgba(0, 0, 0, 0.08);
  --radius-sm: 8px;
  --radius: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
  --font-serif: 'Merriweather', Georgia, serif;
  --navbar-height: 68px;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  background-color: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  overflow-x: hidden;
  padding-top: var(--navbar-height);
}

/* ── Navbar ─────────────────────── */
.navbar {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid var(--border-light);
  height: var(--navbar-height);
  transition: all var(--transition);
  z-index: 1050;
  box-shadow: var(--shadow-sm);
}
.navbar.scrolled {
  box-shadow: var(--shadow-md);
  background: rgba(255, 255, 255, 0.95);
}
.navbar-brand {
  font-weight: 700;
  font-size: 1.5rem;
  letter-spacing: -0.02em;
  color: var(--text-primary) !important;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: color var(--transition);
}
.navbar-brand .brand-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent);
  display: inline-block;
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%,
  100% {
      transform: scale(1);
      opacity: 0.8;
  }
  50% {
      transform: scale(1.7);
      opacity: 1;
  }
}
.navbar-nav .nav-link {
  color: var(--text-secondary) !important;
  font-weight: 500;
  font-size: 0.95rem;
  padding: 0.5rem 1rem !important;
  border-radius: var(--radius-sm);
  transition: all var(--transition);
  position: relative;
  letter-spacing: -0.01em;
}
.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active {
  color: var(--text-primary) !important;
  background: var(--bg-tertiary);
}
.nav-icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition);
  position: relative;
  font-size: 1.1rem;
}
.nav-icon-btn:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border-color: var(--text-muted);
}
.search-slide {
  position: absolute;
  top: 100%;
  right: 0;
  width: 340px;
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  padding: 1rem;
  opacity: 0;
  visibility: hidden;
  transform: translateY(8px);
  transition: all var(--transition);
  z-index: 1060;
}
.search-slide.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(4px);
}
.search-slide input {
  border-radius: var(--radius) !important;
  border: 2px solid var(--border) !important;
  padding: 0.65rem 1rem !important;
  font-size: 0.95rem !important;
  transition: all var(--transition);
  background: var(--bg-primary) !important;
}
.search-slide input:focus {
  border-color: var(--accent) !important;
  box-shadow: 0 0 0 4px rgba(194, 65, 12, 0.08) !important;
  background: var(--bg-secondary) !important;
}

/* ── Hero / Featured ────────────── */
.featured-post {
  position: relative;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--bg-tertiary);
  margin-bottom: 2.5rem;
  cursor: pointer;
  transition: all var(--transition);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-wrap: wrap;
  min-height: 400px;
}
.featured-post:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}
.featured-post .featured-image {
  flex: 1 1 50%;
  min-height: 400px;
  background: url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=800&h=600&fit=crop')
      center/cover no-repeat;
  position: relative;
  transition: transform 0.6s ease;
}
.featured-post:hover .featured-image {
  transform: scale(1.03);
}
.featured-post .featured-content {
  flex: 1 1 50%;
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: var(--bg-secondary);
}
.featured-post .category-badge {
  display: inline-block;
  background: var(--accent-soft);
  color: var(--accent);
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0.35rem 0.85rem;
  border-radius: 50px;
  margin-bottom: 1rem;
  width: fit-content;
}
.featured-post h2 {
  font-family: var(--font-serif);
  font-weight: 700;
  font-size: 2rem;
  line-height: 1.25;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
  color: var(--text-primary);
}
.featured-post .excerpt {
  color: var(--text-secondary);
  font-size: 1rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}
.featured-post .meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.9rem;
  color: var(--text-muted);
}
.featured-post .meta .author-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border);
}
.featured-post .read-more-arrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  color: var(--accent);
  font-size: 0.95rem;
  transition: gap var(--transition);
  text-decoration: none;
  margin-top: 0.5rem;
}
.featured-post .read-more-arrow i {
  transition: transform var(--transition);
  font-size: 0.85rem;
}
.featured-post .read-more-arrow:hover i {
  transform: translateX(4px);
}

/* ── Blog Cards ────────────────── */
.blog-card {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--border-light);
  transition: all var(--transition);
  box-shadow: var(--shadow-sm);
  height: 100%;
  display: flex;
  flex-direction: column;
  cursor: pointer;
}
.blog-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-3px);
  border-color: transparent;
}
.blog-card .card-img-wrapper {
  position: relative;
  overflow: hidden;
  aspect-ratio: 16 / 10;
  background: var(--bg-tertiary);
}
.blog-card .card-img-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}
.blog-card:hover .card-img-wrapper img {
  transform: scale(1.06);
}
.blog-card .card-body {
  padding: 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.blog-card .category-badge {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--accent);
  margin-bottom: 0.5rem;
}
.blog-card h5 {
  font-family: var(--font-serif);
  font-weight: 700;
  font-size: 1.2rem;
  line-height: 1.35;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
  letter-spacing: -0.01em;
}
.blog-card .card-excerpt {
  color: var(--text-secondary);
  font-size: 0.9rem;
  line-height: 1.6;
  flex: 1;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.blog-card .card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.83rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border-light);
  padding-top: 0.85rem;
  margin-top: auto;
}
.blog-card .card-meta .author-mini {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.blog-card .card-meta .author-mini img {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  object-fit: cover;
}
.blog-card .like-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  transition: all var(--transition);
  font-size: 1.1rem;
  padding: 4px 8px;
  border-radius: 50px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.85rem;
}
.blog-card .like-btn:hover {
  color: #ef4444;
}
.blog-card .like-btn.liked {
  color: #ef4444;
}
.blog-card .like-btn.liked i {
  animation: heart-pop 0.4s ease;
}
@keyframes heart-pop {
  0% {
      transform: scale(1);
  }
  30% {
      transform: scale(1.35);
  }
  60% {
      transform: scale(0.9);
  }
  100% {
      transform: scale(1);
  }
}

/* ── Sidebar ───────────────────── */
.sidebar-section {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 1.75rem;
  margin-bottom: 1.5rem;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}
.sidebar-section h6 {
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--text-muted);
  margin-bottom: 1.25rem;
}
.sidebar-section .tag-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.sidebar-section .tag-cloud .tag {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  padding: 0.4rem 0.85rem;
  border-radius: 50px;
  font-size: 0.83rem;
  font-weight: 500;
  text-decoration: none;
  transition: all var(--transition);
  cursor: pointer;
  border: 1px solid transparent;
}
.sidebar-section .tag-cloud .tag:hover {
  background: var(--accent-soft);
  color: var(--accent);
  border-color: var(--accent);
}
.sidebar-recent-item {
  display: flex;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--border-light);
  cursor: pointer;
  transition: all var(--transition);
  text-decoration: none;
  color: inherit;
}
.sidebar-recent-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.sidebar-recent-item:hover {
  opacity: 0.75;
}
.sidebar-recent-item .recent-thumb {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  flex-shrink: 0;
  background: var(--bg-tertiary);
}
.sidebar-recent-item .recent-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.sidebar-recent-item .recent-info .recent-title {
  font-weight: 600;
  font-size: 0.9rem;
  line-height: 1.3;
  color: var(--text-primary);
  margin-bottom: 2px;
}
.sidebar-recent-item .recent-info .recent-date {
  font-size: 0.78rem;
  color: var(--text-muted);
}

/* ── Newsletter ────────────────── */
.newsletter-box {
  background: linear-gradient(135deg, #1c1917 0%, #292524 100%);
  border-radius: var(--radius-lg);
  padding: 2rem;
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-md);
}
.newsletter-box::after {
  content: '';
  position: absolute;
  top: -40px;
  right: -40px;
  width: 140px;
  height: 140px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 50%;
  pointer-events: none;
}
.newsletter-box h5 {
  font-weight: 700;
  font-size: 1.3rem;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}
.newsletter-box p {
  color: #d6d3d1;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
}
.newsletter-box input {
  border-radius: 50px !important;
  padding: 0.7rem 1.2rem !important;
  border: 2px solid transparent !important;
  font-size: 0.9rem !important;
  background: rgba(255, 255, 255, 0.1) !important;
  color: #fff !important;
  transition: all var(--transition);
}
.newsletter-box input::placeholder {
  color: #a8a29e;
}
.newsletter-box input:focus {
  border-color: rgba(255, 255, 255, 0.4) !important;
  background: rgba(255, 255, 255, 0.15) !important;
  box-shadow: none !important;
  outline: none;
}
.newsletter-box .btn-subscribe {
  border-radius: 50px !important;
  padding: 0.7rem 1.5rem !important;
  font-weight: 600 !important;
  background: #fff !important;
  color: #1c1917 !important;
  border: none !important;
  transition: all var(--transition);
  white-space: nowrap;
}
.newsletter-box .btn-subscribe:hover {
  background: #e7e5e4 !important;
  transform: translateY(-1px);
}

/* ── Footer ────────────────────── */
.blog-footer {
  background: var(--bg-secondary);
  border-top: 1px solid var(--border-light);
  padding: 3rem 0 2rem;
  margin-top: 3rem;
}
.blog-footer .footer-brand {
  font-weight: 700;
  font-size: 1.3rem;
  letter-spacing: -0.02em;
  margin-bottom: 0.5rem;
}
.blog-footer .footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}
.blog-footer .footer-links li {
  margin-bottom: 0.4rem;
}
.blog-footer .footer-links a {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
  transition: color var(--transition);
}
.blog-footer .footer-links a:hover {
  color: var(--accent);
}
.blog-footer .social-icons a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  margin-right: 6px;
  transition: all var(--transition);
  text-decoration: none;
  font-size: 1rem;
}
.blog-footer .social-icons a:hover {
  background: var(--accent-soft);
  color: var(--accent);
  transform: translateY(-2px);
}

/* ── Back to Top ───────────────── */
.back-to-top {
  position: fixed;
  bottom: 28px;
  right: 28px;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 1040;
  opacity: 0;
  visibility: hidden;
  transform: translateY(16px);
  transition: all var(--transition);
  color: var(--text-secondary);
  font-size: 1.2rem;
}
.back-to-top.visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.back-to-top:hover {
  background: var(--bg-tertiary);
  color: var(--accent);
  transform: translateY(-3px);
  box-shadow: var(--shadow-lg);
}

/* ── Toast / Notification ──────── */
.custom-toast {
  position: fixed;
  bottom: 28px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: #1c1917;
  color: #fff;
  padding: 0.75rem 1.5rem;
  border-radius: 50px;
  font-weight: 500;
  font-size: 0.9rem;
  z-index: 9999;
  box-shadow: var(--shadow-lg);
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}
.custom-toast.show {
  transform: translateX(-50%) translateY(0);
}

/* ── Responsive ────────────────── */
@media (max-width: 991px) {
  .featured-post {
      flex-direction: column;
      min-height: auto;
  }
  .featured-post .featured-image {
      min-height: 260px;
      flex: none;
      width: 100%;
  }
  .featured-post .featured-content {
      padding: 2rem 1.5rem;
  }
  .featured-post h2 {
      font-size: 1.5rem;
  }
  .search-slide {
      width: calc(100vw - 2rem);
      right: -1rem;
      position: fixed;
      top: var(--navbar-height);
  }
}
@media (max-width: 767px) {
  .blog-card h5 {
      font-size: 1.05rem;
  }
  .featured-post h2 {
      font-size: 1.3rem;
  }
  .newsletter-box {
      padding: 1.5rem;
  }
  .newsletter-box .d-flex {
      flex-direction: column;
      gap: 0.6rem;
  }
}
STUB;
  }
  
  private static function BlogScript() { return <<<'JS'
$(function () {

  // ── Navbar scroll effect ──
  const $navbar = $('#mainNavbar');
  $(window).on('scroll', function () {
      if ($(this).scrollTop() > 30) {
          $navbar.addClass('scrolled');
      } else {
          $navbar.removeClass('scrolled');
      }
      // Back to top
      if ($(this).scrollTop() > 600) {
          $('#backToTop').addClass('visible');
      } else {
          $('#backToTop').removeClass('visible');
      }
  });

  // ── Back to top click ──
  $('#backToTop').on('click', function () {
      $('html, body').animate({ scrollTop: 0 }, 500);
  });

  // ── Search toggle ──
  $('#searchToggle').on('click', function (e) {
      e.stopPropagation();
      $('#searchSlide').toggleClass('show');
      if ($('#searchSlide').hasClass('show')) {
          setTimeout(() => $('#searchInput').focus(), 150);
      }
  });
  $(document).on('click', function (e) {
      if (!$(e.target).closest('#searchSlide, #searchToggle').length) {
          $('#searchSlide').removeClass('show');
      }
  });
  $('#searchInput').on('keydown', function (e) {
      if (e.key === 'Enter') {
          const query = $(this).val().trim();
          if (query) {
              showToast('Searching for: "' + query + '"');
              $('#searchSlide').removeClass('show');
              $(this).val('');
          }
      }
  });

  // ── Like buttons ──
  $(document).on('click', '.like-btn', function () {
      const $btn = $(this);
      const $icon = $btn.find('i');
      const $count = $btn.find('span');
      let currentLikes = parseInt($btn.attr('data-likes')) || 0;

      if ($btn.hasClass('liked')) {
          // Unlike
          $btn.removeClass('liked');
          $icon.removeClass('bi-heart-fill').addClass('bi-heart');
          currentLikes--;
          $btn.attr('data-likes', currentLikes);
          $count.text(currentLikes);
      } else {
          // Like
          $btn.addClass('liked');
          $icon.removeClass('bi-heart').addClass('bi-heart-fill');
          currentLikes++;
          $btn.attr('data-likes', currentLikes);
          $count.text(currentLikes);
          // Re-trigger heart animation
          $icon.css('animation', 'none');
          void $icon[0].offsetWidth;
          $icon.css('animation', 'heart-pop 0.4s ease');
      }
  });

  // ── Newsletter subscription ──
  $('#newsletterBtn').on('click', function () {
      const email = $('#newsletterEmail').val().trim();
      if (!email || !isValidEmail(email)) {
          showToast('Please enter a valid email address.');
          $('#newsletterEmail').addClass('is-invalid');
          setTimeout(() => $('#newsletterEmail').removeClass('is-invalid'), 2000);
          return;
      }
      // Simulate subscription
      $(this).prop('disabled', true).text('Subscribed!');
      showToast('🎉 Welcome aboard! Check your inbox.');
      $('#newsletterEmail').val('');
      setTimeout(() => {
          $('#newsletterBtn').prop('disabled', false).text('Subscribe');
      }, 2500);
  });
  $('#newsletterEmail').on('keydown', function (e) {
      if (e.key === 'Enter') {
          $('#newsletterBtn').trigger('click');
      }
  });

  function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // ── Toast helper ──
  function showToast(message) {
      const $toast = $('#customToast');
      $toast.text(message).addClass('show');
      clearTimeout($toast.data('timeout'));
      $toast.data('timeout', setTimeout(() => {
          $toast.removeClass('show');
      }, 2200));
  }

  // ── Tag click ──
  $('.tag-cloud .tag').on('click', function () {
      const tag = $(this).text().trim();
      showToast('Filtering by tag: "' + tag + '"');
  });

  // ── Featured post click ──
  $('#featuredPost').on('click', function (e) {
      if (!$(e.target).closest('a').length) {
          showToast('Opening featured article...');
      }
  });

  // ── Blog card click ──
  $('.blog-card').on('click', function (e) {
      if (!$(e.target).closest('.like-btn, a').length) {
          const title = $(this).find('h5').text().trim();
          showToast('Opening: "' + title + '"');
      }
  });

  // ── Sidebar recent item click ──
  $('.sidebar-recent-item').on('click', function (e) {
      e.preventDefault();
      const title = $(this).find('.recent-title').text().trim();
      showToast('Opening: "' + title + '"');
  });

  // ── Smooth scroll for nav links ──
  $('.navbar-nav .nav-link').on('click', function (e) {
      $('.navbar-nav .nav-link').removeClass('active');
      $(this).addClass('active');
      // Close mobile navbar
      const navbarCollapse = document.getElementById('navContent');
      if (navbarCollapse.classList.contains('show')) {
          new bootstrap.Collapse(navbarCollapse).hide();
      }
  });

  // ── Theme toggle (simple demo) ──
  $('#themeToggle').on('click', function () {
      const icon = $(this).find('i');
      if (icon.hasClass('bi-moon-stars')) {
          icon.removeClass('bi-moon-stars').addClass('bi-sun');
          showToast('Dark mode concept — implement your theme!');
      } else {
          icon.removeClass('bi-sun').addClass('bi-moon-stars');
          showToast('Light mode');
      }
  });

  // ── Initialize: show first load toast ──
  setTimeout(() => {
      showToast('Welcome to Machinjiri — enjoy the read!');
  }, 800);

  console.log('%c️ Machinjiri Blog %cready.',
      'font-weight:bold;font-size:1.1rem;color:#c2410c;',
      'color:#57534e;');
});
JS;
  }
}