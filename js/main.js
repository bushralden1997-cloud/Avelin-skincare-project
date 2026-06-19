/* AVELIN Skincare — main.js */
document.addEventListener('DOMContentLoaded', function () {

  // Mobile nav
  const mb = document.getElementById('mobileMenuBtn');
  const mn = document.getElementById('mainNav');
  if (mb && mn) mb.addEventListener('click', () => mn.classList.toggle('open'));

  // Tab switching
  document.querySelectorAll('.tab-nav button').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.tab-nav button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      const pane = document.getElementById(this.dataset.tab);
      if (pane) pane.classList.add('active');
    });
  });

  // Qty +/-
  document.querySelectorAll('.qty-input').forEach(box => {
    const inp = box.querySelector('input[type=number]');
    box.querySelector('.qty-dec')?.addEventListener('click', () => {
      if (inp && parseInt(inp.value) > parseInt(inp.min || 1)) inp.value = parseInt(inp.value) - 1;
    });
    box.querySelector('.qty-inc')?.addEventListener('click', () => {
      const max = parseInt(inp.max || 9999);
      if (inp && parseInt(inp.value) < max) inp.value = parseInt(inp.value) + 1;
    });
  });

  // Sort auto-submit
  const ss = document.getElementById('sortSelect');
  if (ss) ss.addEventListener('change', function () {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', this.value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
  });

  // Image preview (admin)
  const imgInput = document.getElementById('imageInput');
  const imgPrev  = document.getElementById('imgPreview');
  if (imgInput && imgPrev) {
    imgInput.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { imgPrev.innerHTML = `<img src="${e.target.result}" alt="Önizleme">`; };
        reader.readAsDataURL(this.files[0]);
      }
    });
    imgPrev.addEventListener('click', () => imgInput.click());
  }

  // Auth tab switching
  document.querySelectorAll('.auth-tabs button').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.auth-tabs button').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.auth-pane').forEach(p => p.style.display = 'none');
      this.classList.add('active');
      const pane = document.getElementById(this.dataset.pane);
      if (pane) pane.style.display = 'block';
    });
  });

  // Toast
  window.showToast = function(msg, type='success') {
    const c = document.getElementById('toast-container');
    if (!c) return;
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span>${type==='success'?'✓':'✕'}</span> ${msg}`;
    c.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 4000);
  };

  // AJAX Favorites
  document.querySelectorAll('.fav-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const pid = this.dataset.productId;
      const active = this.classList.contains('active');
      fetch('../php/favorites_ajax.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=${active?'remove':'add'}&product_id=${pid}`
      }).then(r=>r.json()).then(data => {
        if (data.success) {
          this.classList.toggle('active');
          this.textContent = this.classList.contains('active') ? '♥' : '♡';
          showToast(data.message, 'success');
          document.querySelectorAll('.badge').forEach((b,i) => {
            if (b.closest('.action-link[href*="favorites"]')) { b.textContent=data.fav_count; b.style.display=data.fav_count>0?'flex':'none'; }
          });
        } else {
          showToast(data.message||'Bir hata oluştu','error');
          if (data.redirect) window.location.href = data.redirect;
        }
      }).catch(()=>showToast('Bir hata oluştu','error'));
    });
  });

  // AJAX Cart
  document.querySelectorAll('.btn-cart-ajax').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      fetch('../php/cart_ajax.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=add&product_id=${this.dataset.productId}&quantity=1`
      }).then(r=>r.json()).then(data => {
        if (data.success) showToast('Sepete eklendi!','success');
        else { showToast(data.message||'Hata','error'); if(data.redirect)window.location.href=data.redirect; }
      }).catch(()=>showToast('Hata','error'));
    });
  });

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => { if (!confirm(el.dataset.confirm||'Emin misiniz?')) e.preventDefault(); });
  });

  // Star rating
  const starLabels = document.querySelectorAll('.star-rating label');
  starLabels.forEach((label, idx) => {
    label.addEventListener('mouseenter', () => {
      starLabels.forEach((l,i) => l.style.color = i >= starLabels.length-idx-1 ? 'var(--gold)' : 'var(--border)');
    });
  });
  document.querySelector('.star-rating')?.addEventListener('mouseleave', () => {
    starLabels.forEach(l => l.style.color = '');
  });
});
