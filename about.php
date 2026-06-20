<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/templates/header.php';
?>
<div class="about-page">
  <!-- Hero -->
  <section class="about-hero">
    <div class="container">
      <h1>About BD Fashion</h1>
      <p class="lead">Bangladesh's most trusted custom apparel studio — where premium quality, diverse style, and fast delivery meet.</p>
    </div>
  </section>

  <!-- Who We Are -->
  <section class="about-section">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-6">
          <span class="badge badge-ts badge-ts-primary mb-3">Who We Are</span>
          <h2>A full-scale fashion studio for the modern wardrobe</h2>
          <p>BD Fashion is a contemporary apparel brand built for creators, teams, and individuals across Bangladesh. While we love a perfect custom t-shirt, we've expanded into a full-scale fashion studio engineering premium outerwear, everyday style staples, and comfortable streetwear.</p>
          <p class="mb-0">Every stitch, cut, and print reflects our commitment to quality — because what you wear should feel as good as it looks.</p>
        </div>
        <div class="col-lg-6">
          <div class="about-visual">
            <div class="about-visual-grid">
              <div class="av-item" style="background:linear-gradient(135deg, var(--primary-soft), var(--primary-light))"><i class="bi bi-handbag"></i></div>
              <div class="av-item" style="background:linear-gradient(135deg, #f3e8ff, #ede9fe)"><i class="bi bi-hoodie"></i></div>
              <div class="av-item" style="background:linear-gradient(135deg, var(--orange-soft), #ffedd5)"><i class="bi bi-t-shirt"></i></div>
              <div class="av-item" style="background:linear-gradient(135deg, #fce7f3, #fdf2f8)"><i class="bi bi-backpack"></i></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Our Story -->
  <section class="about-section about-section-alt">
    <div class="container">
      <div class="row g-5 align-items-center flex-row-reverse">
        <div class="col-lg-6">
          <span class="badge badge-ts badge-ts-primary mb-3">Our Story</span>
          <h2>From a local print studio to a full online fashion experience</h2>
          <p>What started as a small t-shirt printing operation has grown into something much bigger. As our community grew, so did their wardrobes.</p>
          <p>Today, we help hundreds of customers bring their unique identities to life — not just through custom shirts, but with meticulously designed denim jackets, tailored blouses, hoodies, and oversized cardigans perfect for work, play, and everything in between.</p>
          <p class="mb-0">We didn't just expand our catalog — we expanded what's possible.</p>
        </div>
        <div class="col-lg-6">
          <div class="about-stats">
            <div class="stat-card">
              <span class="stat-number">500+</span>
              <span class="stat-label">Happy Clients</span>
            </div>
            <div class="stat-card">
              <span class="stat-number">50+</span>
              <span class="stat-label">Apparel Styles</span>
            </div>
            <div class="stat-card">
              <span class="stat-number">99%</span>
              <span class="stat-label">Satisfaction</span>
            </div>
            <div class="stat-card">
              <span class="stat-number">3&mdash;5</span>
              <span class="stat-label">Day Delivery</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Mission & Vision -->
  <section class="about-section">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6">
          <div class="about-mv-card">
            <div class="mv-icon"><i class="bi bi-bullseye"></i></div>
            <h3>Our Mission</h3>
            <p>To make premium, T-shirt, and trend-setting apparel accessible, affordable, and professional — so everyone can dress with absolute confidence.</p>
          </div>
        </div>
        <div class="col-md-6">
          <div class="about-mv-card">
            <div class="mv-icon"><i class="bi bi-eye"></i></div>
            <h3>Our Vision</h3>
            <p>To redefine the Bangladeshi apparel landscape by combining exceptional manufacturing quality, fast local shipping, and unparalleled design flexibility for every customer.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Why Choose Us -->
  <section class="about-section about-section-alt">
    <div class="container">
      <div class="text-center mb-5">
        <span class="badge badge-ts badge-ts-primary mb-3">Why Choose Us</span>
        <h2>Built for the way you dress today</h2>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="about-feature-card">
            <div class="fc-icon"><i class="bi bi-collection"></i></div>
            <h4>A Complete Wardrobe</h4>
            <p>We've expanded far beyond t-shirts. Explore our curated selection of drop-shoulder denim jackets, elegant blouses, and dual-tone cardigans.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="about-feature-card">
            <div class="fc-icon"><i class="bi bi-shield-check"></i></div>
            <h4>Premium Materials</h4>
            <p>We source soft, durable fabrics and use retail-grade finishes that hold up wash after wash. No shortcuts, no compromises.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="about-feature-card">
            <div class="fc-icon"><i class="bi bi-palette"></i></div>
            <h4>Easy Customization</h4>
            <p>Use our intuitive online studio to adapt graphics, logos, and custom text onto a wider range of apparel styles than ever before.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
.about-page {
  --about-accent: var(--primary);
}
.about-hero {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  padding: 5rem 0 4rem;
  text-align: center;
}
.theme-dark .about-hero {
  background: linear-gradient(135deg, #022c22 0%, #064e3b 100%);
}
.about-hero h1 {
  font-size: 2.8rem;
  font-weight: 900;
  letter-spacing: -0.03em;
  margin-bottom: 0.75rem;
}
.about-hero .lead {
  font-size: 1.1rem;
  max-width: 600px;
  margin: 0 auto;
  color: var(--text-secondary);
}
.theme-dark .about-hero h1 { color: #e2e8f0; }
.theme-dark .about-hero .lead { color: #94a3b8; }

.about-section {
  padding: 5rem 0;
}
.about-section-alt {
  background: var(--surface-soft);
}
.theme-dark .about-section-alt {
  background: var(--bg);
}
.about-section h2 {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  margin-bottom: 1rem;
  line-height: 1.25;
}
.about-section p {
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: 1rem;
}
.about-visual-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.av-item {
  aspect-ratio: 1 / 1;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: var(--text);
  transition: transform 0.3s;
}
.av-item:hover {
  transform: translateY(-4px);
}
.av-item:first-child {
  border-radius: var(--radius) 0 0 var(--radius);
}
.av-item:last-child {
  border-radius: 0 var(--radius) var(--radius) 0;
}
.about-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.stat-card {
  background: var(--surface);
  border-radius: var(--radius);
  padding: 2rem 1.5rem;
  text-align: center;
  border: 1px solid var(--border);
  transition: transform 0.3s, box-shadow 0.3s;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}
.theme-dark .stat-card {
  background: var(--surface);
}
.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 900;
  color: var(--primary);
  line-height: 1;
  margin-bottom: 0.3rem;
}
.stat-label {
  font-size: 0.85rem;
  color: var(--muted);
  font-weight: 500;
}
.about-mv-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2.5rem;
  height: 100%;
  transition: transform 0.3s, box-shadow 0.3s;
}
.about-mv-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}
.theme-dark .about-mv-card {
  background: var(--surface);
}
.mv-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: var(--primary-soft);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1.2rem;
}
.about-mv-card h3 {
  font-weight: 700;
  font-size: 1.2rem;
  margin-bottom: 0.75rem;
}
.about-mv-card p {
  color: var(--text-secondary);
  line-height: 1.7;
  margin: 0;
  font-size: 0.95rem;
}
.about-feature-card {
  text-align: center;
  padding: 2.5rem 1.5rem;
  border-radius: var(--radius);
  background: var(--surface);
  border: 1px solid var(--border);
  height: 100%;
  transition: transform 0.3s, box-shadow 0.3s;
}
.about-feature-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}
.theme-dark .about-feature-card {
  background: var(--surface);
}
.fc-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: var(--primary-soft);
  color: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin: 0 auto 1.2rem;
}
.about-feature-card h4 {
  font-weight: 700;
  font-size: 1.05rem;
  margin-bottom: 0.6rem;
}
.about-feature-card p {
  color: var(--text-secondary);
  font-size: 0.9rem;
  line-height: 1.6;
  margin: 0;
}
.theme-dark .av-item { background: var(--surface-soft) !important; }

@media (max-width: 767px) {
  .about-hero h1 { font-size: 2rem; }
  .about-section { padding: 3rem 0; }
  .about-visual-grid { gap: 0.5rem; }
  .av-item { font-size: 2rem; }
  .about-stats { gap: 0.5rem; }
  .stat-card { padding: 1.2rem; }
  .stat-number { font-size: 1.5rem; }
}
</style>

<?php require_once __DIR__.'/templates/footer.php'; ?>
