'use strict';

/* ================= Helpers ================= */
const $ = (s, root = document) => root.querySelector(s);
const esc = s => String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));

/* Exact empty-state strings from the spec (they differ per screen) */
const MSG = {
  noClients: 'No client(s) found.',            // Clients list
  noContacts: 'No contact(s) found.',          // Contacts list
  noContactsOnClient: 'No contacts found.',    // Client form > Contact(s) tab
  noClientsOnContact: 'No contact(s) found.',  // Contact form > Client(s) tab (spec wording)
};

let toastTimer;
function toast(text, kind = 'ok') {
  const t = $('#toast');
  t.textContent = text;
  t.className = 'toast show ' + kind;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (t.className = 'toast'), 2500);
}

/** AJAX wrapper: always resolves to { ok, status, data } so callers handle 422s without try/catch. */
async function api(method, url, body) {
  try {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  } catch {
    return { ok: false, status: 0, data: { error: 'Network error. Please check your connection.' } };
  }
}

/* ================= Validation (mirrors PHP Validator) ================= */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const rules = {
  required: (v, label) => (v.trim() === '' ? `${label} is required.` : ''),
  max: (v, n, label) => (v.trim().length > n ? `${label} must be ${n} characters or fewer.` : ''),
  email: v => (EMAIL_RE.test(v.trim()) ? '' : 'Enter a valid email address.'),
};

function showErrors(root, errors) {
  root.querySelectorAll('[data-error-for]').forEach(el => {
    const field = el.dataset.errorFor;
    el.textContent = errors[field] || '';
    const input = root.querySelector(`[name="${field}"]`);
    if (input) input.classList.toggle('invalid', !!errors[field]);
  });
}

/* ================= Tabs ================= */
function initTabs(root) {
  root.querySelectorAll('.tab').forEach(btn =>
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
      root.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t === btn));
      root.querySelectorAll('.pane').forEach(p => (p.hidden = p.dataset.pane !== btn.dataset.tab));
    })
  );
}

/** Fill the "link" select with a placeholder plus available options. */
function fillPicker(select, placeholder, items, label) {
  select.innerHTML = `<option value="">${esc(placeholder)}</option>` +
    items.map(i => `<option value="${i.id}">${esc(label(i))}</option>`).join('');
}

/* ================= Pages ================= */
const pages = {
  /* ---- Clients list ---- */
  async 'clients-list'(root) {
    const { ok, data } = await api('GET', '/api/clients');
    const box = $('#list', root);
    if (!ok) return (box.textContent = data.error || 'Could not load clients.');
    if (data.clients.length === 0) return (box.innerHTML = `<div class="empty">${MSG.noClients}</div>`);
    box.innerHTML = `<table><thead><tr>
        <th>Name</th><th>Client code</th><th class="c">No. of linked contacts</th></tr></thead><tbody>
      ${data.clients.map(c => `<tr class="row" data-href="/clients/${c.id}">
        <td>${esc(c.name)}</td><td>${esc(c.client_code)}</td><td class="c">${c.contact_count}</td></tr>`).join('')}
      </tbody></table>`;
    box.querySelectorAll('tr.row').forEach(r => r.addEventListener('click', () => (location.href = r.dataset.href)));
  },

  /* ---- Contacts list ---- */
  async 'contacts-list'(root) {
    const { ok, data } = await api('GET', '/api/contacts');
    const box = $('#list', root);
    if (!ok) return (box.textContent = data.error || 'Could not load contacts.');
    if (data.contacts.length === 0) return (box.innerHTML = `<div class="empty">${MSG.noContacts}</div>`);
    box.innerHTML = `<table><thead><tr>
        <th>Name</th><th>Surname</th><th>Email address</th><th class="c">No. of linked clients</th></tr></thead><tbody>
      ${data.contacts.map(c => `<tr class="row" data-href="/contacts/${c.id}">
        <td>${esc(c.name)}</td><td>${esc(c.surname)}</td><td>${esc(c.email)}</td><td class="c">${c.client_count}</td></tr>`).join('')}
      </tbody></table>`;
    box.querySelectorAll('tr.row').forEach(r => r.addEventListener('click', () => (location.href = r.dataset.href)));
  },

  /* ---- Client form ---- */
  async 'client-form'(root) {
    const id = root.dataset.id;
    const form = $('#form', root);
    initTabs(root);

    const renderLinks = d => {
      fillPicker($('#pick', root), 'Select a contact…', d.available, c => `${c.full_name} (${c.email})`);
      $('#linked', root).innerHTML = d.contacts.length === 0
        ? `<div class="empty">${MSG.noContactsOnClient}</div>`
        : `<table><thead><tr><th>Contact Full Name</th><th>Contact email address</th><th></th></tr></thead><tbody>
           ${d.contacts.map(c => `<tr><td>${esc(c.full_name)}</td><td>${esc(c.email)}</td>
             <td><a class="unlink" data-id="${c.id}">Unlink</a></td></tr>`).join('')}</tbody></table>`;
    };

    if (id) {
      const { ok, data } = await api('GET', `/api/clients/${id}`);
      if (!ok) return toast(data.error || 'Could not load client.', 'err');
      form.name.value = data.client.name;
      $('#client_code', root).value = data.client.client_code;
      $('#code-row', root).hidden = false;           // code only shown once saved
      renderLinks(data);
    }

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const name = form.name.value;
      const errors = {};
      const err = rules.required(name, 'Name') || rules.max(name, 150, 'Name');
      if (err) errors.name = err;
      showErrors(form, errors);
      if (err) return;

      const res = id ? await api('PUT', `/api/clients/${id}`, { name })
                     : await api('POST', '/api/clients', { name });
      if (res.status === 422) return showErrors(form, res.data.errors);
      if (!res.ok) return toast(res.data.error || 'Could not save client.', 'err');
      if (!id) return (location.href = `/clients/${res.data.client.id}?saved=1`);
      toast('Client saved');
    });

    $('#link-btn', root).addEventListener('click', async () => {
      const contact_id = Number($('#pick', root).value);
      const res = await api('POST', `/api/clients/${id}/contacts`, { contact_id });
      if (res.status === 422) return showErrors(root, res.data.errors);
      if (!res.ok) return toast(res.data.error || 'Could not link contact.', 'err');
      showErrors(root, {});
      renderLinks(res.data);
      toast('Contact linked');
    });

    $('#linked', root).addEventListener('click', async e => {
      const a = e.target.closest('a.unlink');
      if (!a) return;
      const res = await api('DELETE', `/api/clients/${id}/contacts/${a.dataset.id}`);
      if (!res.ok) return toast(res.data.error || 'Could not unlink contact.', 'err');
      renderLinks(res.data);
      toast('Contact unlinked');
    });
  },

  /* ---- Contact form ---- */
  async 'contact-form'(root) {
    const id = root.dataset.id;
    const form = $('#form', root);
    initTabs(root);

    const renderLinks = d => {
      fillPicker($('#pick', root), 'Select a client…', d.available, c => `${c.name} (${c.client_code})`);
      $('#linked', root).innerHTML = d.clients.length === 0
        ? `<div class="empty">${MSG.noClientsOnContact}</div>`
        : `<table><thead><tr><th>Client name</th><th>Client code</th><th></th></tr></thead><tbody>
           ${d.clients.map(c => `<tr><td>${esc(c.name)}</td><td>${esc(c.client_code)}</td>
             <td><a class="unlink" data-id="${c.id}">Unlink</a></td></tr>`).join('')}</tbody></table>`;
    };

    if (id) {
      const { ok, data } = await api('GET', `/api/contacts/${id}`);
      if (!ok) return toast(data.error || 'Could not load contact.', 'err');
      form.name.value = data.contact.name;
      form.surname.value = data.contact.surname;
      form.email.value = data.contact.email;
      renderLinks(data);
    }

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const { name, surname, email } = Object.fromEntries(new FormData(form));
      const errors = {};
      const n = rules.required(name, 'Name') || rules.max(name, 100, 'Name');
      const s = rules.required(surname, 'Surname') || rules.max(surname, 100, 'Surname');
      const m = rules.required(email, 'Email') || rules.max(email, 190, 'Email') || rules.email(email);
      if (n) errors.name = n;
      if (s) errors.surname = s;
      if (m) errors.email = m;
      showErrors(form, errors);
      if (Object.keys(errors).length) return;

      const body = { name, surname, email };
      const res = id ? await api('PUT', `/api/contacts/${id}`, body) : await api('POST', '/api/contacts', body);
      if (res.status === 422) return showErrors(form, res.data.errors);   // e.g. duplicate email
      if (!res.ok) return toast(res.data.error || 'Could not save contact.', 'err');
      if (!id) return (location.href = `/contacts/${res.data.contact.id}?saved=1`);
      toast('Contact saved');
    });

    $('#link-btn', root).addEventListener('click', async () => {
      const client_id = Number($('#pick', root).value);
      const res = await api('POST', `/api/contacts/${id}/clients`, { client_id });
      if (res.status === 422) return showErrors(root, res.data.errors);
      if (!res.ok) return toast(res.data.error || 'Could not link client.', 'err');
      showErrors(root, {});
      renderLinks(res.data);
      toast('Client linked');
    });

    $('#linked', root).addEventListener('click', async e => {
      const a = e.target.closest('a.unlink');
      if (!a) return;
      const res = await api('DELETE', `/api/contacts/${id}/clients/${a.dataset.id}`);
      if (!res.ok) return toast(res.data.error || 'Could not unlink client.', 'err');
      renderLinks(res.data);
      toast('Client unlinked');
    });
  },
};

/* ================= Boot ================= */
document.addEventListener('DOMContentLoaded', () => {
  const root = $('#page');
  if (!root) return;
  if (new URLSearchParams(location.search).get('saved')) {
    toast('Saved');
    history.replaceState(null, '', location.pathname);
  }
  // Enable the second tab once the record exists (page was loaded with an id)
  if (root.dataset.id) root.querySelectorAll('.tab[disabled]').forEach(t => (t.disabled = false));
  pages[root.dataset.page]?.(root);
});
