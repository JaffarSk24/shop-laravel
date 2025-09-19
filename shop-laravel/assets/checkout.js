document.addEventListener('DOMContentLoaded', () => {
  const cart = JSON.parse(localStorage.getItem('cart')) || [];
  const itemsEl = document.getElementById('checkout-items');
  const totalEl = document.getElementById('checkout-total');
  const form = document.getElementById('checkout-form');
  let total = 0;

  if (cart.length === 0) {
    itemsEl.innerHTML = `<tr><td colspan="3" data-i18n="products.empty">Cart is empty</td></tr>`;
  } else {
    cart.forEach(item => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${item.name}</td>
        <td>${item.qty}</td>
        <td>€${(item.price * item.qty).toFixed(2)}</td>
      `;
      itemsEl.appendChild(row);
      total += item.price * item.qty;
    });
  }

  // Обновляем итог с переводом
  const totalLabel = translationsReady
    ? (translations[currentLang]['products.total'] || 'Total')
    : 'Total';
  totalEl.textContent = `${totalLabel}: €${total.toFixed(2)}`;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    const formData = Object.fromEntries(new FormData(form));
    const payload = {
      name: formData.name || '',
      email: formData.email || '',
      address: formData.address || '',
      cart,
      total: total,
      lang: currentLang // "sk" или "ru"
    };

    try {
      const res = await fetch('/api/submit_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (!res.ok || !json.ok) {
        throw new Error(json.error || ('HTTP ' + res.status));
      }

      // Успех
      localStorage.removeItem('cart');
      const successMsg = translationsReady
        ? (translations[currentLang]['order.success'] || 'Order received. ID: ')
        : 'Order received. ID: ';
      alert(successMsg + (json.order_id || '—'));
      window.location.href = 'index.html';

    } catch (err) {
      console.error('Order error:', err);
      const errorMsg = translationsReady
        ? (translations[currentLang]['order.error'] || 'Error sending order: ')
        : 'Error sending order: ';
      alert(errorMsg + (err.message || err));
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });
});