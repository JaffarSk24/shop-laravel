// ===== Mobile menu toggle =====
const navToggle = document.querySelector('.nav-toggle');
const navList = document.getElementById('primary-menu');
if (navToggle && navList) {
  navToggle.addEventListener('click', () => {
    const opened = navList.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', opened ? 'true' : 'false');
  });
}

// ===== Language switcher =====
let translations = {};
let translationsReady = false;
let currentLang = document.documentElement.lang || 'sk'; // глобальная переменная

fetch('assets/translations.json')
  .then(res => res.json())
  .then(json => {
    translations = json;
    translationsReady = true;
    const userLang = navigator.language || navigator.userLanguage || 'sk';
    const short = userLang.substring(0, 2).toLowerCase();
    const lang = (short === 'ru') ? 'ru' : 'sk';
    setLanguage(lang); // выставляем язык при старте
  });

function setLanguage(lang) {
  if (!translationsReady || !translations[lang]) return;
  currentLang = lang; // обновляем глобальную переменную
  document.documentElement.lang = lang;

  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (translations[lang][key]) {
      el.textContent = translations[lang][key];
    }
    if (el.hasAttribute('data-i18n-aria')) {
      const ariaKey = el.getAttribute('data-i18n-aria');
      if (translations[lang][ariaKey]) {
        el.setAttribute('aria-label', translations[lang][ariaKey]);
      }
    }
  });

  // подсветка активного языка в меню
  document.querySelectorAll('.lang-switch a').forEach(a => {
    const active = a.dataset.lang === lang;
    a.classList.toggle('active', active);
    if (active) a.setAttribute('aria-current', 'true');
    else a.removeAttribute('aria-current');
  });
}

// переключатель языков
document.addEventListener('click', (e) => {
  const langLink = e.target.closest('.lang-switch a');
  if (!langLink) return;
  e.preventDefault();
  setLanguage(langLink.dataset.lang);
});

// ===== CART LOGIC =====
let cart = [];
function loadCart() {
  const saved = localStorage.getItem('cart');
  if (saved) { try { cart = JSON.parse(saved); } catch { cart = []; } }
  updateCartCount();
}
function saveCart() {
  localStorage.setItem('cart', JSON.stringify(cart));
}
function updateCartCount() {
  const countEl = document.getElementById('cart-count');
  if (!countEl) return;
  const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
  countEl.textContent = `(${totalQty})`;
}
function addToCart(id, name, price) {
  const item = cart.find(p => p.id === id);
  if (item) item.qty += 1;
  else cart.push({ id, name, price, qty: 1 });
  saveCart(); updateCartCount();
}

// ===== CART MODAL =====
function renderCart() {
  const itemsEl = document.getElementById('cart-items');
  const totalEl = document.getElementById('cart-total');
  const emptyEl = document.getElementById('cart-empty');
  itemsEl.innerHTML = '';
  let total = 0;

  if (cart.length === 0) {
    emptyEl.style.display = 'block';
    totalEl.textContent = '';
    return;
  } else {
    emptyEl.style.display = 'none';
  }

  cart.forEach((item, idx) => {
    const li = document.createElement('li');
    li.innerHTML = `
      <span>${item.name} — €${item.price.toFixed(2)}</span>
      <div class="qty-control">
        <button class="decrease" data-index="${idx}">−</button>
        <span class="qty">${item.qty}</span>
        <button class="increase" data-index="${idx}">+</button>
      </div>
      <button class="remove-item" data-index="${idx}">×</button>
    `;
    itemsEl.appendChild(li);
    total += item.price * item.qty;
  });

  const totalLabel = translationsReady
    ? (translations[currentLang]['products.total'] || 'Total')
    : 'Total';
  totalEl.textContent = `${totalLabel}: €${total.toFixed(2)}`;
}

function openCartModal() { renderCart(); document.getElementById('cart-modal').style.display = 'flex'; }
function closeCartModal() { document.getElementById('cart-modal').style.display = 'none'; }

// === Events ===
document.addEventListener('click', (e) => {
  // Add to cart button
  const btn = e.target.closest('.btn[data-id]');
  if (btn) {
    e.preventDefault();
    const id = parseInt(btn.dataset.id, 10);
    const price = parseFloat(btn.dataset.price);
    const card = btn.closest('.card');
    const name = card ? (card.querySelector('h3')?.textContent.trim() || '') : `product-${id}`;
    addToCart(id, name, price);
    return;
  }

  // Open cart modal
  const cartLink = e.target.closest('#cart-link');
  if (cartLink) { e.preventDefault(); openCartModal(); }

  // Increase qty
  if (e.target.classList.contains('increase')) {
    const idx = parseInt(e.target.dataset.index, 10);
    cart[idx].qty += 1;
    saveCart(); updateCartCount(); renderCart();
  }

  // Decrease qty
  if (e.target.classList.contains('decrease')) {
    const idx = parseInt(e.target.dataset.index, 10);
    if (cart[idx].qty > 1) cart[idx].qty -= 1;
    else cart.splice(idx, 1);
    saveCart(); updateCartCount(); renderCart();
  }

  // Remove item
  if (e.target.classList.contains('remove-item')) {
    const idx = parseInt(e.target.dataset.index, 10);
    cart.splice(idx, 1);
    saveCart(); updateCartCount(); renderCart();
  }

  // Clear cart
  if (e.target.id === 'clear-cart') {
    cart = [];
    saveCart(); updateCartCount(); renderCart();
  }

  // Checkout
  if (e.target.id === 'checkout') {
    e.preventDefault();
    if (cart.length === 0) {
      alert(translationsReady
        ? translations[currentLang]['products.empty']
        : 'Cart is empty');
      return;
    }
    window.location.href = "checkout.html";
  }
});

document.getElementById('close-cart').addEventListener('click', closeCartModal);
loadCart();