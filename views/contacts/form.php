<div id="page" data-page="contact-form" data-id="<?= $id ?? '' ?>">
  <div class="bar"><h2><?= $id ? 'Edit contact' : 'New contact' ?></h2><a class="btn secondary" href="/contacts">Back</a></div>

  <div class="tabs">
    <button type="button" class="tab active" data-tab="general">General</button>
    <button type="button" class="tab" data-tab="clients" <?= $id ? '' : 'disabled' ?>>Client(s)</button>
  </div>

  <section class="pane" data-pane="general">
    <form id="form" novalidate>
      <label for="name">Name</label>
      <input type="text" id="name" name="name" maxlength="100" autocomplete="off">
      <div class="msg" data-error-for="name"></div>

      <label for="surname">Surname</label>
      <input type="text" id="surname" name="surname" maxlength="100" autocomplete="off">
      <div class="msg" data-error-for="surname"></div>

      <label for="email">Email</label>
      <input type="text" id="email" name="email" maxlength="190" autocomplete="off">
      <div class="msg" data-error-for="email"></div>

      <div class="actions"><button type="submit">Save</button></div>
    </form>
  </section>

  <section class="pane" data-pane="clients" hidden>
    <div class="link-row">
      <select id="pick"></select>
      <button type="button" id="link-btn">Link client</button>
    </div>
    <div class="msg" data-error-for="client_id"></div>
    <div id="linked"></div>
  </section>
</div>
