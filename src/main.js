import './style.css'

// ============================================
// HÉLIANS — Main JS
// ============================================

const EXPERTISES = [
  { id: 'expropriation', page: '/expropriation.html' },
  { id: 'preemption', page: '/preemption.html' },
  { id: 'immobilier', page: '/immobilier.html' },
  { id: 'construction', page: '/construction.html' },
  { id: 'hopitaux', page: '/hopitaux.html' },
  { id: 'urbanisme', page: '/urbanisme.html' },
  { id: 'baux', page: '/baux.html' },
]

// --- Helpers ---
// Handles content that can be either an array of strings or an HTML string
const toHtml = (v, cls = '') => {
  if (Array.isArray(v)) return v.map(p => `<p${cls ? ` class="${cls}"` : ''}>${p}</p>`).join('')
  if (typeof v === 'string' && v) return cls ? `<div class="${cls}">${v}</div>` : v
  return ''
}
const toArray = v => Array.isArray(v) ? v : (typeof v === 'string' && v ? [v] : [])
const toStringArray = v => toArray(v).filter(x => typeof x === 'string')
const toObjectArray = v => toArray(v).filter(x => typeof x === 'object' && x !== null)

// --- Init ---
document.addEventListener('DOMContentLoaded', init)

async function init() {
  const data = await fetchContent()
  if (!data) return

  const page = document.body.dataset.page
  const app = document.getElementById('app')
  if (!app) return

  app.innerHTML = renderNav(data, page) + renderMobileDrawer(data) + renderPage(data, page) + renderFooter(data) + renderArticleModal()

  initNavScroll()
  initMobileMenu()
  initScrollReveal()

  if (page === 'contact') initContactForm()
  if (page === 'blog') initBlogCards(data)
}

// --- Fetch content ---
async function fetchContent() {
  try {
    const res = await fetch('/content.php')
    if (res.ok) return await res.json()
  } catch (e) { /* fallback */ }
  try {
    const res = await fetch('/content.json')
    if (res.ok) return await res.json()
  } catch (e) { /* silent */ }
  return null
}

// --- Helper: get expertise title from data ---
function getExpertiseTitle(data, id) {
  return data[id]?.titre || id
}

// --- Navigation ---
function renderNav(data, currentPage) {
  const isExpertisePage = EXPERTISES.some(e => e.id === currentPage)

  return `
  <nav class="nav" id="navbar">
    <div class="nav__inner">
      <a href="/" class="nav__logo">
        <img src="/images/logo.jpg" alt="Hélians" />
        <div>
          <span class="nav__logo-text">Hélians</span>
          <span class="nav__logo-sub">Avocats Conseils</span>
        </div>
      </a>
      <div class="nav__links">
        <a href="/" class="nav__link ${currentPage === 'accueil' ? 'active' : ''}">Accueil</a>
        <a href="/cabinet.html" class="nav__link ${currentPage === 'cabinet' ? 'active' : ''}">Le Cabinet</a>
        <div class="nav__dropdown">
          <span class="nav__link nav__dropdown-toggle ${isExpertisePage ? 'active' : ''}">Expertises</span>
          <div class="nav__dropdown-menu">
            ${EXPERTISES.map(e => `<a href="${e.page}" class="nav__dropdown-item ${currentPage === e.id ? 'active' : ''}">${getExpertiseTitle(data, e.id)}</a>`).join('')}
          </div>
        </div>
        <a href="/blog.html" class="nav__link ${currentPage === 'blog' ? 'active' : ''}">Blog</a>
        <a href="/contact.html" class="nav__link nav__link--cta">Contact</a>
      </div>
      <button class="nav__hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>`
}

function renderMobileDrawer(data) {
  return `
  <div class="mobile-drawer__overlay" id="drawer-overlay"></div>
  <div class="mobile-drawer" id="mobile-drawer">
    <a href="/" class="mobile-drawer__link">Accueil</a>
    <a href="/cabinet.html" class="mobile-drawer__link">Le Cabinet</a>
    <div class="mobile-drawer__section-title">Nos expertises</div>
    ${EXPERTISES.map(e => `<a href="${e.page}" class="mobile-drawer__sub-link">${getExpertiseTitle(data, e.id)}</a>`).join('')}
    <a href="/blog.html" class="mobile-drawer__link">Blog</a>
    <a href="/contact.html" class="mobile-drawer__cta">Nous contacter</a>
  </div>`
}

// --- Page router ---
function renderPage(data, page) {
  const renderers = {
    accueil: renderAccueil,
    cabinet: renderCabinet,
    expropriation: renderExpertise,
    preemption: renderExpertise,
    immobilier: renderExpertise,
    construction: renderExpertise,
    hopitaux: renderExpertise,
    urbanisme: renderExpertise,
    baux: renderExpertise,
    blog: renderBlog,
    contact: renderContact,
    mentions: renderMentions,
  }
  const renderer = renderers[page]
  return renderer ? renderer(data, page) : '<main class="container page-top"><p>Page non trouvée.</p></main>'
}

// ============================================
// PAGE RENDERERS
// ============================================

function renderAccueil(data) {
  const accueil = data.accueil || {}
  const hero = accueil.hero || {}
  const cabinet = data.cabinet || {}

  return `
  <main>
    <section class="hero">
      <div class="container">
        <p class="hero__eyebrow">Cabinet d'avocats · Paris</p>
        <h1 class="hero__title">${(hero.titre || '').replace(/(droit)\s/, '$1<br>')}</h1>
        <p class="hero__subtitle">${hero.description || hero.sousTitre || ''}</p>
        <div class="hero__actions">
          <a href="/contact.html" class="btn btn--primary">Nous contacter</a>
          <a href="/cabinet.html" class="btn btn--ghost">Découvrir le cabinet</a>
        </div>
      </div>
    </section>

    <section class="container">
      <h2 class="section-heading reveal">Nos domaines d'expertise</h2>
      <div class="expertises-grid stagger-children">
        ${(accueil.expertises || EXPERTISES).map((exp, i) => {
          const e = EXPERTISES[i] || {}
          const titre = exp.titre || getExpertiseTitle(data, e.id)
          const desc = exp.description || ''
          const page = e.page || '/'
          return `
          <a href="${page}" class="expertise-card reveal">
            <div class="expertise-card__number">${String(i + 1).padStart(2, '0')}</div>
            <h3 class="expertise-card__title">${titre}</h3>
            <p class="expertise-card__text">${desc}</p>
          </a>`
        }).join('')}
      </div>
    </section>

    <div class="container"><div class="divider"></div></div>

    <section class="container">
      <div class="grid-editorial">
        <div class="grid-editorial__main reveal">
          <h2 class="section-heading">Le cabinet</h2>
          ${toHtml(cabinet.description)}
          <div style="margin-top: 2rem;">
            <a href="/cabinet.html" class="btn btn--outline">En savoir plus</a>
          </div>
        </div>
        <div class="grid-editorial__sidebar reveal">
          <div class="sidebar-card">
            <h4 class="sidebar-card__title">En bref</h4>
            <ul class="sidebar-card__list">
              <li><strong>+30 ans</strong> — d'expérience en droit immobilier</li>
              <li><strong>3 avocats</strong> — spécialisés à votre service</li>
              <li><strong>Paris 1er</strong> — 7 rue d'Argenteuil</li>
              <li><strong>Conseil & contentieux</strong> — approche sur mesure</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </main>`
}

function renderCabinet(data) {
  const cabinet = data.cabinet || {}
  const equipe = cabinet.equipe || {}
  const membres = equipe.membres || []

  const photoMap = {
    'Gilles CAILLET': '/images/gilles-caillet.jpg',
    'Xavier VIDALIE': '/images/xavier-vidalie.jpg',
    'Noémie PAJOT': '/images/noemie-pajot.jpg',
  }

  return `
  <main class="expertise-page">
    <div class="container">
      <p class="expertise-page__eyebrow reveal">Le cabinet</p>
      <h1 class="expertise-page__title reveal">${cabinet.titre || 'Le Cabinet Hélians'}</h1>

      ${toHtml(cabinet.description, 'reveal')}

      <div class="divider"></div>

      <h2 class="section-heading reveal">${equipe.titre || 'Notre équipe'}</h2>
      <div class="team-grid stagger-children">
        ${membres.map(m => `
        <div class="team-member reveal">
          <img src="${m.photo || photoMap[m.nom] || ''}" alt="${m.nom}" class="team-member__photo" />
          <h3 class="team-member__name">${m.nom || ''}</h3>
          <p class="team-member__role">${m.role || ''}</p>
          ${(m.biographie || []).map(b => `<p class="team-member__bio">${b}</p>`).join('')}
          ${m.formations ? `
          <ul class="team-member__formation">
            ${m.formations.map(f => `<li>${f}</li>`).join('')}
          </ul>` : ''}
        </div>`).join('')}
      </div>

      <div class="divider"></div>

      <h2 class="section-heading reveal">Notre approche</h2>
      <div class="about-values stagger-children">
        <div class="about-value reveal">
          <h4 class="about-value__title">Conseil personnalisé</h4>
          <p class="about-value__text">Après une analyse approfondie de chaque dossier, nous élaborons une stratégie adaptée à votre situation spécifique.</p>
        </div>
        <div class="about-value reveal">
          <h4 class="about-value__title">Dialogue privilégié</h4>
          <p class="about-value__text">Nous privilégions la discussion amiable et la médiation. Le contentieux n'intervient que lorsqu'il constitue la voie la plus efficace.</p>
        </div>
        <div class="about-value reveal">
          <h4 class="about-value__title">Réseau d'experts</h4>
          <p class="about-value__text">Nous collaborons avec un réseau de confrères et d'experts (notaires, experts techniques, huissiers) pour une approche pluridisciplinaire.</p>
        </div>
      </div>
    </div>
  </main>`
}

function renderExpertise(data, page) {
  const section = data[page] || {}
  const refs = section.references || {}
  const dossiers = refs.dossiers || []
  const paragraphs = [
    ...toStringArray(section.introduction),
    ...toStringArray(section.expertise),
    ...toStringArray(section.prestations),
  ]
  // Prestations structurées (objets {titre, description})
  const prestationsObj = toObjectArray(section.prestations)
  // Competences (for préemption)
  const competences = toStringArray(section.competences)
  // Domaines (for expropriation, immobilier)
  const domaines = section.domaines || []
  // Types (for préemption)
  const types = section.types || null

  return `
  <main class="expertise-page">
    <div class="container">
      <div class="grid-editorial">
        <div class="grid-editorial__main">
          <p class="expertise-page__eyebrow reveal">Nos expertises</p>
          <h1 class="expertise-page__title reveal">${section.titre || ''}</h1>

          <div class="expertise-page__content">
            ${paragraphs.map(p => `<p class="reveal">${p}</p>`).join('')}

            ${competences.length > 0 ? `
            <h3 class="reveal">Nos compétences</h3>
            <ul>
              ${competences.map(c => `<li class="reveal">${c}</li>`).join('')}
            </ul>` : ''}

            ${Array.isArray(domaines) && domaines.length > 0 ? `
            <h3 class="reveal">Domaines d'intervention</h3>
            <ul>
              ${domaines.map(d => `<li class="reveal"><strong>${typeof d === 'object' ? d.titre : d}</strong>${typeof d === 'object' && d.description ? ` — ${d.description}` : ''}</li>`).join('')}
            </ul>` : ''}

            ${prestationsObj.length > 0 ? `
            <h3 class="reveal">Nos prestations</h3>
            <ul>
              ${prestationsObj.map(p => `<li class="reveal"><strong>${p.titre || ''}</strong>${p.description ? ` — ${p.description}` : ''}</li>`).join('')}
            </ul>` : ''}

            ${types ? `
            <h3 class="reveal">Types de préemption</h3>
            <p class="reveal">${types}</p>` : ''}
          </div>
        </div>

        <div class="grid-editorial__sidebar">
          <div class="sidebar-nav">
            <h4 class="sidebar-nav__title">Nos expertises</h4>
            ${EXPERTISES.map(e => `<a href="${e.page}" class="sidebar-nav__link ${page === e.id ? 'active' : ''}">${getExpertiseTitle(data, e.id)}</a>`).join('')}
          </div>

          ${dossiers.length > 0 ? `
          <div class="sidebar-card">
            <h4 class="sidebar-card__title">${refs.titre || 'Nos références'}</h4>
            <ul class="sidebar-card__list">
              ${dossiers.map(r => `<li>${r}</li>`).join('')}
            </ul>
          </div>` : ''}
        </div>
      </div>
    </div>
  </main>`
}

function renderBlog(data) {
  const blog = data.blog || {}
  const articles = (blog.articles || []).filter(a => a.status === 'published' || a.statut === 'publie')

  if (articles.length === 0) {
    return `
    <main class="expertise-page">
      <div class="container">
        <p class="expertise-page__eyebrow reveal">Actualités</p>
        <h1 class="expertise-page__title reveal">Blog juridique</h1>
        <p class="reveal" style="color: var(--grey-500); margin-top: 2rem;">Aucun article publié pour le moment. Revenez bientôt !</p>
      </div>
    </main>`
  }

  return `
  <main class="expertise-page">
    <div class="container">
      <p class="expertise-page__eyebrow reveal">Actualités</p>
      <h1 class="expertise-page__title reveal">Blog juridique</h1>

      <div class="blog-grid stagger-children">
        ${articles.map((article, i) => `
        <div class="blog-card ${i === 0 ? 'blog-card--featured' : ''} reveal" data-article-id="${article.id || i}">
          ${article.image ? `<img class="blog-card__image" src="${article.image}" alt="${article.titre || article.title || ''}" loading="lazy">` : ''}
          <p class="blog-card__date">${formatDate(article.date)}</p>
          <h3 class="blog-card__title">${article.titre || article.title || ''}</h3>
          <p class="blog-card__excerpt">${article.description || article.extrait || article.excerpt || ''}</p>
          ${(article.tags || article.etiquettes) ? `
          <div class="blog-card__tags">
            ${(article.tags || article.etiquettes || []).map(t => `<span class="blog-card__tag">${t}</span>`).join('')}
          </div>` : ''}
        </div>`).join('')}
      </div>
    </div>
  </main>`
}

function renderContact(data) {
  const contact = data.contact || {}
  const meta = data.meta || {}
  const acces = contact.acces || {}
  const metros = acces.metros || meta.metros || []
  const parkings = acces.parkings || meta.parkings || []

  return `
  <main class="expertise-page">
    <div class="container">
      <p class="expertise-page__eyebrow reveal">Contact</p>
      <h1 class="expertise-page__title reveal">${contact.titre || 'Nous contacter'}</h1>

      <div class="contact-grid">
        <div class="reveal">
          <div class="contact-info__item">
            <p class="contact-info__label">Adresse</p>
            <p class="contact-info__value">${contact.adresse || meta.adresse || ''}</p>
          </div>
          <div class="contact-info__item">
            <p class="contact-info__label">Téléphone</p>
            <p class="contact-info__value"><a href="tel:${(contact.telephone || meta.telephone || '').replace(/\./g, '')}">${contact.telephone || meta.telephone || ''}</a></p>
          </div>
          <div class="contact-info__item">
            <p class="contact-info__label">Accès métro</p>
            <p class="contact-info__value">${metros.map(m => `${m.lignes} : ${m.station}`).join('<br>')}</p>
          </div>
          <div class="contact-info__item">
            <p class="contact-info__label">Parkings à proximité</p>
            <p class="contact-info__value">${parkings.join(', ')}</p>
          </div>
          <div class="contact-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.5!2d2.336!3d48.865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2e2e9b4b8d%3A0x0!2s7+Rue+d'Argenteuil%2C+75001+Paris!5e0!3m2!1sfr!2sfr!4v1" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Cabinet Hélians - 7 rue d'Argenteuil, Paris"></iframe>
          </div>
        </div>

        <div class="reveal">
          <form class="form" id="contact-form">
            <div class="form__row">
              <div class="form__group">
                <label class="form__label" for="prenom">Prénom *</label>
                <input class="form__input" type="text" id="prenom" name="prenom" required />
              </div>
              <div class="form__group">
                <label class="form__label" for="nom">Nom *</label>
                <input class="form__input" type="text" id="nom" name="nom" required />
              </div>
            </div>
            <div class="form__row">
              <div class="form__group">
                <label class="form__label" for="email">Email *</label>
                <input class="form__input" type="email" id="email" name="email" required />
              </div>
              <div class="form__group">
                <label class="form__label" for="telephone">Téléphone *</label>
                <input class="form__input" type="tel" id="telephone" name="telephone" required />
              </div>
            </div>
            <div class="form__group">
              <label class="form__label" for="objet">Objet</label>
              <select class="form__select" id="objet" name="objet">
                <option value="">Sélectionnez un domaine</option>
                ${EXPERTISES.map(e => `<option value="${getExpertiseTitle(data, e.id)}">${getExpertiseTitle(data, e.id)}</option>`).join('')}
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div class="form__group">
              <label class="form__label" for="message">Message *</label>
              <textarea class="form__textarea" id="message" name="message" required placeholder="Décrivez brièvement votre situation..."></textarea>
            </div>
            <!-- Honeypot anti-spam -->
            <div class="hp-field" aria-hidden="true">
              <label for="website">Website</label>
              <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" />
            </div>
            <button type="submit" class="btn btn--primary form__submit">Envoyer le message</button>
            <div class="form__message form__message--success" id="form-success">Votre message a été envoyé avec succès. Nous vous répondrons dans les meilleurs délais.</div>
            <div class="form__message form__message--error" id="form-error">Une erreur est survenue. Veuillez réessayer ou nous contacter par téléphone.</div>
          </form>
        </div>
      </div>
    </div>
  </main>`
}

function renderMentions(data) {
  const mentions = data.mentions || {}
  const meta = data.meta || {}
  const contenu = mentions.contenu || []

  return `
  <main class="mentions-page">
    <div class="container container--narrow">
      <h1 class="reveal">${mentions.titre || 'Mentions légales'}</h1>

      ${contenu.map(s => `
      <h2 class="reveal">${s.titre || ''}</h2>
      <p class="reveal">${s.texte || ''}</p>
      `).join('')}

      ${contenu.length === 0 ? `
      <h2 class="reveal">Éditeur du site</h2>
      <p class="reveal">${meta.nom || 'Hélians'}, ${meta.forme_juridique || 'SELARL'}</p>
      <p class="reveal">RCS ${meta.rcs || 'PARIS 502 708 530'}</p>
      <p class="reveal">${meta.adresse || ''}</p>
      <p class="reveal">Tél. : ${meta.telephone || ''}</p>
      ` : ''}
    </div>
  </main>`
}

// --- Article modal ---
function renderArticleModal() {
  return `
  <div class="article-modal" id="article-modal">
    <div class="article-modal__overlay" id="article-modal-overlay"></div>
    <div class="article-modal__content">
      <button class="article-modal__close" id="article-modal-close">&times;</button>
      <p class="article-modal__date" id="article-modal-date"></p>
      <h2 class="article-modal__title" id="article-modal-title"></h2>
      <div class="article-modal__body" id="article-modal-body"></div>
    </div>
  </div>`
}

// --- Footer ---
function renderFooter(data) {
  const meta = data.meta || {}

  return `
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div>
          <div class="footer__brand">
            <img src="/images/logo.jpg" alt="Hélians" />
            <span class="footer__brand-name">Hélians</span>
          </div>
          <p class="footer__desc">Société d'avocats dédiée au droit immobilier et au droit public.</p>
        </div>
        <div>
          <h4 class="footer__col-title">Le cabinet</h4>
          <a href="/" class="footer__link">Accueil</a>
          <a href="/cabinet.html" class="footer__link">L'équipe</a>
          <a href="/blog.html" class="footer__link">Blog</a>
          <a href="/contact.html" class="footer__link">Contact</a>
        </div>
        <div>
          <h4 class="footer__col-title">Expertises</h4>
          ${EXPERTISES.slice(0, 5).map(e => `<a href="${e.page}" class="footer__link">${getExpertiseTitle(data, e.id)}</a>`).join('')}
        </div>
        <div>
          <h4 class="footer__col-title">Contact</h4>
          <p class="footer__link">${meta.adresse || ''}</p>
          <a href="tel:${(meta.telephone || '').replace(/\./g, '')}" class="footer__link">${meta.telephone || ''}</a>
        </div>
      </div>
      <div class="footer__bottom">
        <span>&copy; ${new Date().getFullYear()} Hélians — Tous droits réservés</span>
        <a href="/mentions.html">Mentions légales</a>
      </div>
    </div>
  </footer>`
}

// ============================================
// INTERACTIONS
// ============================================

function initNavScroll() {
  const nav = document.getElementById('navbar')
  if (!nav) return
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 20)
  }, { passive: true })
}

function initMobileMenu() {
  const hamburger = document.getElementById('hamburger')
  const drawer = document.getElementById('mobile-drawer')
  const overlay = document.getElementById('drawer-overlay')
  if (!hamburger || !drawer) return

  function toggle() {
    hamburger.classList.toggle('open')
    drawer.classList.toggle('open')
    overlay?.classList.toggle('open')
    document.body.style.overflow = drawer.classList.contains('open') ? 'hidden' : ''
  }

  hamburger.addEventListener('click', toggle)
  overlay?.addEventListener('click', toggle)

  drawer.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      if (drawer.classList.contains('open')) toggle()
    })
  })
}

function initScrollReveal() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' })

  document.querySelectorAll('.reveal').forEach(el => observer.observe(el))
}

function initContactForm() {
  const form = document.getElementById('contact-form')
  if (!form) return

  form.addEventListener('submit', async (e) => {
    e.preventDefault()
    const formData = new FormData(form)
    const payload = Object.fromEntries(formData.entries())

    const success = document.getElementById('form-success')
    const error = document.getElementById('form-error')
    success.style.display = 'none'
    error.style.display = 'none'

    try {
      const res = await fetch('/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })
      const result = await res.json()
      if (result.success) {
        success.style.display = 'block'
        form.reset()
      } else {
        error.style.display = 'block'
      }
    } catch {
      error.style.display = 'block'
    }
  })
}

function initBlogCards(data) {
  const articles = (data.blog?.articles || []).filter(a => a.status === 'published' || a.statut === 'publie')
  const modal = document.getElementById('article-modal')
  const overlay = document.getElementById('article-modal-overlay')
  const closeBtn = document.getElementById('article-modal-close')
  if (!modal) return

  function openArticle(article) {
    document.getElementById('article-modal-date').textContent = formatDate(article.date)
    document.getElementById('article-modal-title').textContent = article.titre || article.title || ''
    document.getElementById('article-modal-body').innerHTML = article.contenu || article.content || article.extrait || article.excerpt || ''
    modal.classList.add('open')
    document.body.style.overflow = 'hidden'
  }

  function closeModal() {
    modal.classList.remove('open')
    document.body.style.overflow = ''
  }

  document.querySelectorAll('.blog-card').forEach(card => {
    card.addEventListener('click', () => {
      const id = card.dataset.articleId
      const article = articles[parseInt(id)] || articles.find(a => a.id === id)
      if (article) openArticle(article)
    })
  })

  overlay?.addEventListener('click', closeModal)
  closeBtn?.addEventListener('click', closeModal)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('open')) closeModal()
  })
}

// --- Helpers ---
function formatDate(dateStr) {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
      day: 'numeric', month: 'long', year: 'numeric'
    })
  } catch {
    return dateStr
  }
}
