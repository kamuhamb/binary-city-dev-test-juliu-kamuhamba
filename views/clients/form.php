<div id="page" data-page="client-form" data-id="<?= $id ?? '' ?>">
  <div class="bar"><h2><?= $id ? 'Edit client' : 'New client' ?></h2><a class="btn secondary" href="/clients">Back</a></div>

  <div class="tabs">
    <button type="button" class="tab active" data-tab="general">General</button>
    <button type="button" class="tab" data-tab="contacts" <?= $id ? '' : 'disabled' ?>>Contact(s)</button>
  </div>

  <section class="pane" data-pane="general">
    <form id="form" novalidate>
      <label for="name">Name</label>
      <input type="text" id="name" name="name" maxlength="150" autocomplete="off">
      <div class="msg" data-error-for="name"></div>

      <div id="code-row" hidden>
        <label for="client_code">Client code</label>
        <input type="text" id="client_code" readonly>
      </div>

      <div class="actions"><button type="submit">Save</button></div>
    </form>
  </section>

  <section class="pane" data-pane="contacts" hidden>
    <div class="link-row">
      <select id="pick"></select>
      <button type="button" id="link-btn">Link contact</button>
    </div>
    <div class="msg" data-error-for="contact_id"></div>
    <div id="linked"></div>
  </section>
</div>
