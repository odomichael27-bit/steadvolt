/* ============================================================
   STEADYVOLT ENERGY — Admin JavaScript
   File: assets/js/admin.js
============================================================ */
'use strict';

function adminToast(msg, type) {
  type = type || 'success';
  var container = document.getElementById('adminToastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'adminToastContainer';
    container.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
    document.body.appendChild(container);
  }
  var colors = { success:'#00A86B', error:'#EF4444', info:'#3B82F6', warning:'#F59E0B' };
  var icons  = { success:'fa-check-circle', error:'fa-exclamation-circle', info:'fa-info-circle', warning:'fa-exclamation-triangle' };
  var t = document.createElement('div');
  t.style.cssText = 'background:#fff;border:1px solid #e5e7eb;border-left:4px solid ' + colors[type] + ';border-radius:10px;padding:12px 16px;font-size:.85rem;box-shadow:0 8px 24px rgba(0,0,0,.12);display:flex;align-items:center;gap:10px;min-width:240px;';
  t.innerHTML = '<i class="fas ' + icons[type] + '" style="color:' + colors[type] + ';flex-shrink:0"></i><span>' + msg + '</span><button onclick="this.closest(\'div\').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1rem;">&times;</button>';
  container.appendChild(t);
  setTimeout(function(){ t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(function(){ t.remove(); }, 300); }, 3500);
}

// Password toggle
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var input = this.closest('.input-wrap') && this.closest('.input-wrap').querySelector('input');
    if (!input) return;
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    var icon = this.querySelector('i');
    if (icon) { icon.classList.toggle('fa-eye', !isHidden); icon.classList.toggle('fa-eye-slash', isHidden); }
  });
});

// Specs editor
(function() {
  var specsEditor = document.getElementById('specsEditor');
  var specsJson   = document.getElementById('specsJson');
  var addBtn      = document.getElementById('addSpecRow');
  if (!specsEditor || !addBtn) return;

  addBtn.addEventListener('click', function() {
    var row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = '<input type="text" class="spec-key" placeholder="e.g. Wattage" style="flex:1;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.88rem;outline:none;"/><input type="text" class="spec-val" placeholder="e.g. 400W" style="flex:1;padding:9px 12px;border:1.5px solid #E5E7EB;border-radius:8px;font-size:.88rem;outline:none;margin-left:8px;"/><button type="button" class="btn btn-xs btn-danger remove-spec" style="margin-left:8px;flex-shrink:0"><i class="fas fa-times"></i></button>';
    specsEditor.appendChild(row);
    row.querySelector('.spec-key').focus();
  });

  document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-spec')) e.target.closest('.spec-row').remove();
  });

  var form = document.querySelector('form[enctype]');
  form && form.addEventListener('submit', function() {
    var rows  = specsEditor.querySelectorAll('.spec-row');
    var specs = [];
    rows.forEach(function(r) {
      var key = r.querySelector('.spec-key') && r.querySelector('.spec-key').value.trim();
      var val = r.querySelector('.spec-val') && r.querySelector('.spec-val').value.trim();
      if (key) specs.push({ key: key, val: val || '' });
    });
    if (specsJson) specsJson.value = JSON.stringify(specs);
  });
})();

// Simple description toolbar
(function() {
  var descEditor = document.getElementById('descEditor');
  if (!descEditor) return;
  var toolbar = document.createElement('div');
  toolbar.style.cssText = 'display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap;';
  var btns = [
    { label:'<b>B</b>', tag:'<strong></strong>' },
    { label:'<i>I</i>', tag:'<em></em>' },
    { label:'H2',       tag:'<h2></h2>' },
    { label:'H3',       tag:'<h3></h3>' },
    { label:'UL',       tag:'<ul>\n  <li></li>\n</ul>' },
    { label:'P',        tag:'<p></p>' },
    { label:'Link',     tag:'<a href=""></a>' },
  ];
  btns.forEach(function(b) {
    var btn = document.createElement('button');
    btn.type = 'button'; btn.innerHTML = b.label;
    btn.style.cssText = 'padding:4px 10px;border:1px solid #e5e7eb;border-radius:5px;font-size:.78rem;background:#f9fafb;cursor:pointer;';
    btn.addEventListener('click', function() {
      var start = descEditor.selectionStart;
      var end   = descEditor.selectionEnd;
      var sel   = descEditor.value.slice(start, end);
      var inner = b.tag.replace('></', '>' + sel + '</');
      descEditor.setRangeText(inner, start, end, 'end');
      descEditor.focus();
    });
    toolbar.appendChild(btn);
  });
  descEditor.parentNode.insertBefore(toolbar, descEditor);
})();

// Google reviews refresh
(function() {
  var btn = document.getElementById('refreshReviews');
  if (!btn) return;
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    var orig = this.innerHTML;
    var self = this;
    self.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    self.disabled = true;
    fetch(self.href).then(function(r){ return r.json(); }).then(function(res) {
      adminToast(res.msg || 'Done!', res.ok ? 'success' : 'error');
      self.innerHTML = res.ok ? '<i class="fas fa-check"></i> Done' : '<i class="fas fa-times"></i> Failed';
      setTimeout(function(){ self.innerHTML = orig; self.disabled = false; }, 4000);
    }).catch(function() {
      adminToast('Request failed.', 'error');
      self.innerHTML = orig; self.disabled = false;
    });
  });
})();

// Multi-image drop zone
(function() {
  var fileInput = document.querySelector('.file-input[name="images[]"]');
  if (!fileInput) return;
  var wrapper  = fileInput.closest('.form-group');
  if (!wrapper) return;
  var dropZone = document.createElement('div');
  dropZone.style.cssText = 'border:2px dashed #D1D5DB;border-radius:8px;padding:32px;text-align:center;font-size:.88rem;color:#9CA3AF;cursor:pointer;transition:all .2s;margin-bottom:12px;';
  dropZone.innerHTML = '<i class="fas fa-cloud-upload-alt" style="font-size:2rem;display:block;margin-bottom:8px;color:#D1D5DB"></i>Drop images here or click to browse<br><small>JPG, PNG, WEBP &mdash; Max 5MB each</small>';
  wrapper.insertBefore(dropZone, fileInput);
  dropZone.addEventListener('click', function(){ fileInput.click(); });
  dropZone.addEventListener('dragover', function(e){ e.preventDefault(); dropZone.style.borderColor='#00A86B'; dropZone.style.background='#F0FDF4'; });
  dropZone.addEventListener('dragleave', function(){ dropZone.style.borderColor='#D1D5DB'; dropZone.style.background=''; });
  dropZone.addEventListener('drop', function(e) {
    e.preventDefault(); dropZone.style.borderColor='#D1D5DB'; dropZone.style.background='';
    var dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach(function(f){ dt.items.add(f); });
    fileInput.files = dt.files;
    showPreviews(fileInput.files);
  });
  fileInput.addEventListener('change', function(){ showPreviews(this.files); });

  function showPreviews(files) {
    var row = document.getElementById('newImgPreviews');
    if (!row) { row = document.createElement('div'); row.id = 'newImgPreviews'; row.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;'; wrapper.appendChild(row); }
    row.innerHTML = '';
    Array.from(files).forEach(function(file) {
      var reader = new FileReader();
      reader.onload = function(e) {
        var img = document.createElement('img');
        img.src = e.target.result;
        img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;';
        row.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
    dropZone.innerHTML = '<i class="fas fa-check-circle" style="font-size:1.5rem;display:block;margin-bottom:6px;color:#00A86B;"></i><strong>' + files.length + ' file(s) selected</strong><br><small>Click to change</small>';
  }
})();

// Order status pills live update
(function() {
  var statusSelect = document.querySelector('select[name="status"]');
  var paySelect    = document.querySelector('select[name="payment_status"]');
  if (!statusSelect) return;
  function updatePills() {
    var pills = document.querySelectorAll('.order-status-row .status-pill');
    if (pills[0] && statusSelect) {
      pills[0].className = 'status-pill status-' + statusSelect.value;
      pills[0].textContent = statusSelect.value.charAt(0).toUpperCase() + statusSelect.value.slice(1);
    }
    if (pills[1] && paySelect) {
      pills[1].className = 'status-pill status-' + paySelect.value;
      pills[1].textContent = paySelect.value.charAt(0).toUpperCase() + paySelect.value.slice(1);
    }
  }
  statusSelect.addEventListener('change', updatePills);
  paySelect && paySelect.addEventListener('change', updatePills);
})();

// Chart resize
window.addEventListener('resize', function() {
  if (window.Chart) {
    Object.values(Chart.instances || {}).forEach(function(c){ c.resize(); });
  }
});

// Copy to clipboard
document.addEventListener('click', function(e) {
  var btn = e.target.closest('[data-copy]');
  if (!btn) return;
  navigator.clipboard.writeText(btn.dataset.copy).then(function() { adminToast('Copied!', 'success'); });
});
