/**
 * Bootstrap Builder – self-contained live preview.
 *
 * The preview iframe is built via srcdoc and loads t3sbootstrap's freshly COMPILED CSS
 * file (data-css-url) directly. This needs no frontend URL, so it works on hosts where
 * the frontend shares the backend domain (e.g. Mittwald).
 *
 * For instant feedback while editing colors, we patch Bootstrap's --bs-* custom
 * properties on the preview document. Variables that only take effect at SCSS compile
 * time require "Apply & compile" followed by a module reload (which reloads the CSS).
 */
(function () {
  "use strict";

  var CSSVAR = {
    "primary": "primary", "secondary": "secondary", "success": "success",
    "info": "info", "warning": "warning", "danger": "danger",
    "light": "light", "dark": "dark",
    "body-bg": "body-bg", "body-color": "body-color",
    "link-color": "link-color",
    "border-radius": "border-radius", "border-radius-sm": "border-radius-sm",
    "border-radius-lg": "border-radius-lg"
  };

  function sampleHtml(cssUrl, jsUrl) {
    return '<!doctype html><html lang="en"><head><meta charset="utf-8">' +
      '<meta name="viewport" content="width=device-width, initial-scale=1">' +
      // Absolute CSS URL so it resolves correctly under the about:blank srcdoc document.
      '<link rel="stylesheet" href="' + absUrl(cssUrl) + '">' +
      '<style>' +
      'body{padding:0}' +
      '.bb-stage{padding:1.5rem}' +
      '.bb-navbars{padding:1rem 1rem 0}' +
      '.bb-section{margin-bottom:1.5rem;border:1px solid rgba(128,128,128,.2);border-radius:.75rem;background:rgba(127,127,127,.06);box-shadow:0 1px 2px rgba(0,0,0,.04);overflow:hidden}' +
      '.bb-section>.bb-section-head{padding:.6rem 1rem;font-size:.75rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:inherit;opacity:.75;background:rgba(127,127,127,.08);border-bottom:1px solid rgba(128,128,128,.15)}' +
      '.bb-section>.bb-section-body{padding:1.25rem}' +
      '.bb-hero{border-radius:.75rem;padding:2.5rem 2rem;margin-bottom:1.5rem;background:var(--bs-primary,#0d6efd);color:#fff}' +
      '.bb-hero .lead{opacity:.9}' +
      '</style>' +
      '</head><body>' +

      // Navbars at the top (multiple variants, like bootstrap.build)
      '<div class="bb-navbars">' +
      navbarExample('navbar-dark bg-primary', 'dark', 'Primary') +
      navbarExample('navbar-dark bg-dark', 'dark', 'Dark') +
      navbarExample('bg-body-tertiary', 'light', 'Light') +
      navbarExample('navbar-dark bg-success', 'dark', 'Success') +
      '</div>' +

      '<div class="bb-stage"><div class="container">' +

      // Hero
      '<div class="bb-hero">' +
      '<h1 class="display-6 mb-2">Heading</h1>' +
      '<p class="lead mb-3">Live preview of your compiled theme.</p>' +
      '<button class="btn btn-light me-2">Get started</button>' +
      '<button class="btn btn-outline-light">Learn more</button>' +
      '</div>' +

      '<div class="row g-3">' +

      // Typography
      '<div class="col-12">' + section('Typography',
        '<div class="row"><div class="col-lg-6">' +
        '<h1>h1. Bootstrap heading</h1>' +
        '<h2>h2. Bootstrap heading</h2>' +
        '<h3>h3. Bootstrap heading</h3>' +
        '<h4>h4. Bootstrap heading</h4>' +
        '<h5>h5. Bootstrap heading</h5>' +
        '<h6>h6. Bootstrap heading</h6>' +
        '</div><div class="col-lg-6">' +
        '<p class="display-1">Display 1</p>' +
        '<p class="display-4">Display 4</p>' +
        '<p class="lead">This is a lead paragraph. It stands out from regular body copy.</p>' +
        '<p>Regular paragraph with <code>$paragraph-margin-bottom</code> spacing below. ' +
        'Body text with a <a data-x="#">sample link</a>, <strong>bold</strong>, <em>italic</em>, ' +
        '<mark>highlight</mark>, <del>deleted</del>, <code>inline code</code> and <kbd>Ctrl</kbd>+<kbd>C</kbd>.</p>' +
        '<p>A second paragraph to show the margin between paragraphs.</p>' +
        '<p><small class="text-muted">Small muted text.</small></p>' +
        '</div></div>' +
        '<blockquote class="blockquote"><p>A well-known quote in a blockquote element.</p>' +
        '<footer class="blockquote-footer">Someone famous in <cite>Source Title</cite></footer></blockquote>' +
        '<div class="row"><div class="col-md-6"><ul><li>Unordered one</li><li>Unordered two</li></ul></div>' +
        '<div class="col-md-6"><ol><li>Ordered one</li><li>Ordered two</li></ol></div></div>'
      ) + '</div>' +

      // Buttons
      '<div class="col-12">' + section('Buttons',
        '<div class="mb-2">' +
        btn('primary') + btn('secondary') + btn('success') + btn('danger') + btn('warning') + btn('info') + btn('light') + btn('dark') + btn('link') +
        '</div>' +
        '<div class="mb-2">' +
        btn('outline-primary') + btn('outline-secondary') + btn('outline-success') + btn('outline-danger') + btn('outline-warning') + btn('outline-info') + btn('outline-light') + btn('outline-dark') +
        '</div>' +
        '<div>' +
        '<button class="btn btn-primary btn-lg me-1">Large</button>' +
        '<button class="btn btn-primary me-1">Default</button>' +
        '<button class="btn btn-primary btn-sm me-1">Small</button>' +
        '<button class="btn btn-primary" disabled>Disabled</button></div>'
      ) + '</div>' +

      // Button groups
      '<div class="col-lg-6">' + section('Button groups',
        '<div class="btn-group mb-3" role="group">' +
        '<button class="btn btn-primary">Left</button>' +
        '<button class="btn btn-primary">Middle</button>' +
        '<button class="btn btn-primary">Right</button></div><br>' +
        '<div class="btn-group mb-3" role="group">' +
        '<button class="btn btn-outline-secondary">1</button>' +
        '<button class="btn btn-outline-secondary active">2</button>' +
        '<button class="btn btn-outline-secondary">3</button></div><br>' +
        '<div class="btn-group-vertical" role="group">' +
        '<button class="btn btn-secondary">Top</button>' +
        '<button class="btn btn-secondary">Bottom</button></div>'
      ) + '</div>' +

      // Breadcrumbs + pagination
      '<div class="col-lg-6">' + section('Breadcrumb &amp; pagination',
        '<nav><ol class="breadcrumb"><li class="breadcrumb-item"><a data-x="#">Home</a></li>' +
        '<li class="breadcrumb-item"><a data-x="#">Library</a></li>' +
        '<li class="breadcrumb-item active">Data</li></ol></nav>' +
        '<nav><ul class="pagination mb-0">' +
        '<li class="page-item disabled"><a class="page-link">&laquo;</a></li>' +
        '<li class="page-item active"><a class="page-link" data-x="#">1</a></li>' +
        '<li class="page-item"><a class="page-link" data-x="#">2</a></li>' +
        '<li class="page-item"><a class="page-link" data-x="#">3</a></li>' +
        '<li class="page-item"><a class="page-link" data-x="#">&raquo;</a></li></ul></nav>'
      ) + '</div>' +

      // Alerts (all variants)
      '<div class="col-lg-6">' + section('Alerts',
        bsAlert('primary') + bsAlert('secondary') + bsAlert('success') + bsAlert('danger') +
        bsAlert('warning') + bsAlert('info') + bsAlert('light') + bsAlert('dark') +
        '<div class="alert alert-success" role="alert">With <a data-x="#" class="alert-link">an example link</a>.</div>' +
        '<div class="alert alert-warning alert-dismissible" role="alert">Dismissible alert with a close button.' +
        '<button type="button" class="btn-close"></button></div>' +
        '<div class="alert alert-info" role="alert"><h4 class="alert-heading">Well done!</h4>' +
        '<p class="mb-0">An alert with a heading and longer descriptive content below it.</p></div>'
      ) + '</div>' +

      // Badges + progress (all variants)
      '<div class="col-lg-6">' + section('Badges &amp; progress',
        '<p class="mb-2">' + badge('primary') + badge('secondary') + badge('success') + badge('danger') + badge('warning') + badge('info') + badge('light') + badge('dark') + '</p>' +
        '<p class="mb-3">' +
        '<span class="badge rounded-pill text-bg-primary me-1">pill</span>' +
        '<span class="badge rounded-pill text-bg-secondary me-1">pill</span>' +
        '<span class="badge rounded-pill text-bg-success me-1">pill</span>' +
        '<span class="badge rounded-pill text-bg-danger me-1">pill</span></p>' +
        progress('primary', 25) + progress('secondary', 40) + progress('success', 50) +
        progress('danger', 60) + progress('warning', 75) + progress('info', 90)
      ) + '</div>' +

      // Forms
      '<div class="col-lg-6">' + section('Forms',
        '<div class="row g-3">' +
        '<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" placeholder="name@example.com"></div>' +
        '<div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" value="secret"></div>' +
        '<div class="col-md-6"><label class="form-label">Select</label><select class="form-select"><option>One</option><option>Two</option></select></div>' +
        '<div class="col-md-6"><label class="form-label">Range</label><input type="range" class="form-range"></div>' +
        '<div class="col-12"><label class="form-label">Textarea</label><textarea class="form-control" rows="2">Some text</textarea></div>' +
        '<div class="col-12">' +
        '<div class="form-check"><input class="form-check-input" type="checkbox" checked id="c1"><label class="form-check-label" for="c1">Checkbox</label></div>' +
        '<div class="form-check"><input class="form-check-input" type="radio" name="r" checked id="r1"><label class="form-check-label" for="r1">Radio one</label></div>' +
        '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked id="s1"><label class="form-check-label" for="s1">Switch</label></div>' +
        '</div>' +
        '<div class="col-12"><button class="btn btn-primary">Submit</button> <button class="btn btn-secondary">Cancel</button></div>' +
        '</div>'
      ) + '</div>' +

      // Validation states
      '<div class="col-lg-6">' + section('Form validation',
        '<div class="mb-3"><label class="form-label">Valid field</label>' +
        '<input type="text" class="form-control is-valid" value="Looks good">' +
        '<div class="valid-feedback">Looks good!</div></div>' +
        '<div class="mb-3"><label class="form-label">Invalid field</label>' +
        '<input type="text" class="form-control is-invalid" value="Wrong">' +
        '<div class="invalid-feedback">Please provide a valid value.</div></div>' +
        '<div class="mb-0"><label class="form-label">Invalid select</label>' +
        '<select class="form-select is-invalid"><option>Choose…</option></select>' +
        '<div class="invalid-feedback">Please select an option.</div></div>'
      ) + '</div>' +

      // Input groups
      '<div class="col-lg-6">' + section('Input groups',
        '<div class="input-group mb-2"><span class="input-group-text">@</span><input type="text" class="form-control" placeholder="Username"></div>' +
        '<div class="input-group mb-2"><input type="text" class="form-control" placeholder="Amount"><span class="input-group-text">.00</span><button class="btn btn-outline-secondary">Go</button></div>' +
        '<div class="input-group"><span class="input-group-text">https://</span><input type="text" class="form-control" placeholder="example.com"></div>'
      ) + '</div>' +

      // Toasts
      '<div class="col-lg-6">' + section('Toasts',
        '<div class="toast show mb-2"><div class="toast-header">' +
        '<strong class="me-auto">Notification</strong><small>just now</small></div>' +
        '<div class="toast-body">Hello! This is a toast message.</div></div>' +
        '<div class="toast show align-items-center text-bg-primary border-0"><div class="d-flex">' +
        '<div class="toast-body">A colored toast using the theme primary.</div></div></div>'
      ) + '</div>' +

      // Table (with variants)
      '<div class="col-12">' + section('Tables',
        '<div class="row g-3">' +
        '<div class="col-lg-6"><p class="text-muted mb-1" style="font-size:.8rem">Striped + hover + bordered</p>' +
        '<table class="table table-striped table-hover table-bordered mb-0"><thead class="table-dark"><tr><th>#</th><th>Name</th><th>Role</th><th>Status</th></tr></thead><tbody>' +
        '<tr class="table-primary"><td>1</td><td>Alice</td><td>Admin</td><td><span class="badge text-bg-success">active</span></td></tr>' +
        '<tr class="table-success"><td>2</td><td>Bob</td><td>Editor</td><td><span class="badge text-bg-warning">pending</span></td></tr>' +
        '<tr class="table-warning"><td>3</td><td>Carol</td><td>Viewer</td><td><span class="badge text-bg-danger">blocked</span></td></tr>' +
        '<tr class="table-danger"><td>4</td><td>Dave</td><td>Guest</td><td><span class="badge text-bg-secondary">inactive</span></td></tr>' +
        '</tbody></table></div>' +
        '<div class="col-lg-6"><p class="text-muted mb-1" style="font-size:.8rem">Small + active row</p>' +
        '<table class="table table-sm mb-3"><thead><tr><th>#</th><th>First</th><th>Last</th></tr></thead><tbody>' +
        '<tr class="table-active"><td>1</td><td>Mark</td><td>Otto</td></tr>' +
        '<tr><td>2</td><td>Jacob</td><td>Thornton</td></tr>' +
        '<tr><td>3</td><td>Larry</td><td>Bird</td></tr></tbody></table>' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Dark</p>' +
        '<table class="table table-dark table-striped mb-0"><thead><tr><th>#</th><th>First</th><th>Last</th></tr></thead><tbody>' +
        '<tr><td>1</td><td>Mark</td><td>Otto</td></tr>' +
        '<tr><td>2</td><td>Jacob</td><td>Thornton</td></tr></tbody></table></div>' +
        '</div>'
      ) + '</div>' +

      // Nav (base, tabs, pills, fill/justified, vertical)
      '<div class="col-12">' + section('Nav',
        '<div class="row g-3">' +
        '<div class="col-lg-6">' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Base nav</p>' +
        '<ul class="nav mb-3"><li class="nav-item"><a class="nav-link active" data-x="#">Active</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Link</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Link</a></li>' +
        '<li class="nav-item"><a class="nav-link disabled">Disabled</a></li></ul>' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Tabs</p>' +
        '<ul class="nav nav-tabs mb-0" role="tablist">' +
        '<li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bbtab1" type="button" role="tab">Active</button></li>' +
        '<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bbtab2" type="button" role="tab">Profile</button></li>' +
        '<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bbtab3" type="button" role="tab">Contact</button></li>' +
        '<li class="nav-item"><button class="nav-link disabled" type="button" disabled>Disabled</button></li></ul>' +
        '<div class="tab-content border border-top-0 p-3 mb-3">' +
        '<div class="tab-pane fade show active" id="bbtab1" role="tabpanel">Active tab content with your theme colors.</div>' +
        '<div class="tab-pane fade" id="bbtab2" role="tabpanel">Profile tab content.</div>' +
        '<div class="tab-pane fade" id="bbtab3" role="tabpanel">Contact tab content.</div></div>' +
        '</div>' +
        '<div class="col-lg-6">' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Pills</p>' +
        '<ul class="nav nav-pills mb-3"><li class="nav-item"><a class="nav-link active" data-x="#">Active pill</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Pill</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Pill</a></li>' +
        '<li class="nav-item"><a class="nav-link disabled">Disabled</a></li></ul>' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Pills, fill</p>' +
        '<ul class="nav nav-pills nav-fill mb-3"><li class="nav-item"><a class="nav-link active" data-x="#">Active</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Longer link</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Link</a></li></ul>' +
        '<p class="text-muted mb-1" style="font-size:.8rem">Vertical pills</p>' +
        '<ul class="nav nav-pills flex-column" style="max-width:200px"><li class="nav-item"><a class="nav-link active" data-x="#">Active</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Link</a></li>' +
        '<li class="nav-item"><a class="nav-link" data-x="#">Link</a></li></ul>' +
        '</div></div>'
      ) + '</div>' +

      // Navbar variants (light + dark)

      // Dropdown (interactive)
      '<div class="col-lg-6">' + section('Dropdown',
        '<div class="dropdown d-inline-block"><button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</button>' +
        '<ul class="dropdown-menu">' +
        '<li><a class="dropdown-item active" href="#">Action</a></li>' +
        '<li><a class="dropdown-item" href="#">Another action</a></li>' +
        '<li><hr class="dropdown-divider"></li>' +
        '<li><a class="dropdown-item" href="#">Separated link</a></li></ul></div>' +
        '<div class="dropdown d-inline-block ms-2"><button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Menu</button>' +
        '<ul class="dropdown-menu">' +
        '<li><a class="dropdown-item" href="#">One</a></li>' +
        '<li><a class="dropdown-item" href="#">Two</a></li></ul></div>'
      ) + '</div>' +

      // Tooltips & popovers (interactive) — title-cased to match group
      '<div class="col-lg-6">' + section('Tooltips &amp; Popovers',
        '<button type="button" class="btn btn-secondary me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Tooltip on top">Tooltip</button>' +
        '<button type="button" class="btn btn-secondary me-2" data-bs-toggle="popover" data-bs-placement="top" title="Popover title" data-bs-content="Popover body content styled with your theme.">Popover</button>' +
        '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bbModal">Launch modal</button>' +
        '<div class="modal fade" id="bbModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title">Modal title</h5>' +
        '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
        '<div class="modal-body"><p>Modal body text styled with your theme.</p></div>' +
        '<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>' +
        '<button class="btn btn-primary">Save changes</button></div></div></div></div>'
      ) + '</div>' +

      // Modal (static, inline) — always-visible reference
      '<div class="col-lg-6">' + section('Modal',
        '<div class="modal position-static d-block" tabindex="-1"><div class="modal-dialog">' +
        '<div class="modal-content"><div class="modal-header">' +
        '<h5 class="modal-title">Modal title</h5><button type="button" class="btn-close"></button></div>' +
        '<div class="modal-body"><p>Modal body text goes here, styled with your theme.</p></div>' +
        '<div class="modal-footer"><button class="btn btn-secondary">Close</button>' +
        '<button class="btn btn-primary">Save changes</button></div></div></div></div>'
      ) + '</div>' +

      // Figure
      '<div class="col-lg-6">' + section('Figure',
        '<figure class="figure mb-0">' +
        '<svg class="figure-img img-fluid rounded" width="100%" height="120" xmlns="http://www.w3.org/2000/svg">' +
        '<rect width="100%" height="100%" fill="var(--bs-secondary, #6c757d)"></rect>' +
        '<text x="50%" y="50%" fill="#fff" text-anchor="middle" dominant-baseline="middle" font-family="sans-serif">Image</text></svg>' +
        '<figcaption class="figure-caption mt-2">A caption for the above image.</figcaption></figure>'
      ) + '</div>' +

      // List groups
      '<div class="col-lg-6">' + section('List groups',
        '<div class="row g-3"><div class="col-md-6">' +
        '<ul class="list-group">' +
        '<li class="list-group-item active">Active item</li>' +
        '<li class="list-group-item">Second item</li>' +
        '<li class="list-group-item d-flex justify-content-between align-items-center">With badge<span class="badge text-bg-primary rounded-pill">14</span></li>' +
        '<li class="list-group-item disabled">Disabled item</li></ul></div>' +
        '<div class="col-md-6">' +
        '<div class="list-group">' +
        '<a data-x="#" class="list-group-item list-group-item-action active">Link active</a>' +
        '<a data-x="#" class="list-group-item list-group-item-action">Link item</a>' +
        '<a data-x="#" class="list-group-item list-group-item-action list-group-item-success">Success</a>' +
        '<a data-x="#" class="list-group-item list-group-item-action list-group-item-danger">Danger</a></div></div></div>'
      ) + '</div>' +

      // Accordion (interactive)
      '<div class="col-lg-6">' + section('Accordion',
        '<div class="accordion" id="bbAcc">' +
        '<div class="accordion-item"><h2 class="accordion-header">' +
        '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#bbAccC1" aria-expanded="true">Accordion Item #1</button></h2>' +
        '<div id="bbAccC1" class="accordion-collapse collapse show" data-bs-parent="#bbAcc"><div class="accordion-body">First item content, styled with your theme.</div></div></div>' +
        '<div class="accordion-item"><h2 class="accordion-header">' +
        '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bbAccC2" aria-expanded="false">Accordion Item #2</button></h2>' +
        '<div id="bbAccC2" class="accordion-collapse collapse" data-bs-parent="#bbAcc"><div class="accordion-body">Second item content.</div></div></div>' +
        '<div class="accordion-item"><h2 class="accordion-header">' +
        '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bbAccC3" aria-expanded="false">Accordion Item #3</button></h2>' +
        '<div id="bbAccC3" class="accordion-collapse collapse" data-bs-parent="#bbAcc"><div class="accordion-body">Third item content.</div></div></div></div>'
      ) + '</div>' +

      // Cards
      '<div class="col-12">' + section('Cards',
        '<div class="row g-3">' +
        '<div class="col-md-4"><div class="card h-100"><div class="card-header">Featured</div><div class="card-body">' +
        '<h5 class="card-title">Card title</h5><h6 class="card-subtitle mb-2 text-muted">Subtitle</h6>' +
        '<p class="card-text">Some quick example text to build on the card title.</p>' +
        '<a data-x="#" class="card-link">Card link</a><a data-x="#" class="card-link">Another link</a></div>' +
        '<div class="card-footer text-muted">2 days ago</div></div></div>' +
        '<div class="col-md-4"><div class="card text-bg-primary h-100"><div class="card-header">Header</div><div class="card-body">' +
        '<h5 class="card-title">Primary card</h5><p class="card-text">A colored card using the theme primary.</p></div></div></div>' +
        '<div class="col-md-4"><div class="card border-success h-100">' +
        '<div class="card-body"><h5 class="card-title text-success">Bordered</h5>' +
        '<p class="card-text">Success border accent with a list group below.</p></div>' +
        '<ul class="list-group list-group-flush">' +
        '<li class="list-group-item">Cras justo odio</li>' +
        '<li class="list-group-item">Dapibus ac facilisis in</li></ul>' +
        '<div class="card-footer">Footer</div></div></div>' +
        '</div>' +
        '<div class="row g-3 mt-1">' +
        '<div class="col-md-6"><div class="card"><div class="row g-0">' +
        '<div class="col-4"><svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="var(--bs-secondary,#6c757d)"/></svg></div>' +
        '<div class="col-8"><div class="card-body"><h5 class="card-title">Horizontal card</h5>' +
        '<p class="card-text">Image on the left, content on the right.</p></div></div></div></div></div>' +
        '<div class="col-md-6"><div class="card text-center"><div class="card-header">Centered</div>' +
        '<div class="card-body"><h5 class="card-title">Special title</h5>' +
        '<p class="card-text">With centered text and a button.</p>' +
        '<a data-x="#" class="btn btn-primary">Go somewhere</a></div>' +
        '<div class="card-footer text-muted">Footer</div></div></div>' +
        '</div>' +
        '<div class="row g-3 mt-1">' +
        '<div class="col-md-4"><div class="card">' +
        '<svg width="100%" height="140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="var(--bs-info,#0dcaf0)"/></svg>' +
        '<div class="card-body"><h5 class="card-title">Image card</h5>' +
        '<p class="card-text">An image cap on top of the card body.</p></div></div></div>' +
        '<div class="col-md-4"><div class="card text-bg-dark">' +
        '<svg width="100%" height="180" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="var(--bs-secondary,#6c757d)"/></svg>' +
        '<div class="card-img-overlay"><h5 class="card-title">Overlay</h5>' +
        '<p class="card-text">Text laid over the image.</p></div></div></div>' +
        '<div class="col-md-4"><div class="card-group">' +
        '<div class="card"><div class="card-body"><h6 class="card-title">Group A</h6>' +
        '<p class="card-text small">Seamlessly joined cards.</p></div></div>' +
        '<div class="card"><div class="card-body"><h6 class="card-title">Group B</h6>' +
        '<p class="card-text small">Equal width and height.</p></div></div></div></div>' +
        '</div>'
      ) + '</div>' +

      // Carousel (interactive)
      '<div class="col-12">' + section('Carousel',
        '<div id="bbCarousel" class="carousel slide" data-bs-ride="carousel">' +
        '<div class="carousel-indicators">' +
        '<button type="button" data-bs-target="#bbCarousel" data-bs-slide-to="0" class="active"></button>' +
        '<button type="button" data-bs-target="#bbCarousel" data-bs-slide-to="1"></button>' +
        '<button type="button" data-bs-target="#bbCarousel" data-bs-slide-to="2"></button></div>' +
        '<div class="carousel-inner rounded">' +
        '<div class="carousel-item active"><svg width="100%" height="240" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="var(--bs-primary,#0d6efd)"/></svg>' +
        '<div class="carousel-caption d-none d-md-block"><h5>First slide</h5><p>Primary colored slide.</p></div></div>' +
        '<div class="carousel-item"><svg width="100%" height="240" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="var(--bs-success,#198754)"/></svg>' +
        '<div class="carousel-caption d-none d-md-block"><h5>Second slide</h5><p>Success colored slide.</p></div></div>' +
        '<div class="carousel-item"><svg width="100%" height="240" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="var(--bs-dark,#212529)"/></svg>' +
        '<div class="carousel-caption d-none d-md-block"><h5>Third slide</h5><p>Dark colored slide.</p></div></div></div>' +
        '<button class="carousel-control-prev" type="button" data-bs-target="#bbCarousel" data-bs-slide="prev">' +
        '<span class="carousel-control-prev-icon"></span></button>' +
        '<button class="carousel-control-next" type="button" data-bs-target="#bbCarousel" data-bs-slide="next">' +
        '<span class="carousel-control-next-icon"></span></button></div>'
      ) + '</div>' +

      // Spinners
      '<div class="col-12">' + section('Spinners',
        '<div class="spinner-border text-primary me-2"></div>' +
        '<div class="spinner-border text-success me-2"></div>' +
        '<div class="spinner-border text-danger me-2"></div>' +
        '<div class="spinner-grow text-primary me-2"></div>' +
        '<div class="spinner-grow text-warning me-2"></div>' +
        '<div class="spinner-grow text-info me-2"></div>'
      ) + '</div>' +

      '</div>' + // row
      '</div></div>' + // container + stage
      (jsUrl ? '<script src="' + absUrl(jsUrl) + '"></script>' +
               '<script src="' + absUrl(jsUrl.replace('bootstrap.bundle.min.js', 'preview-init.js')) + '"></script>' : '') +
      '</body></html>';
  }

  function section(title, body) {
    var id = 'sec-' + title.replace(/&amp;/g, '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    return '<div class="bb-section" id="' + id + '"><div class="bb-section-head">' + title + '</div>' +
      '<div class="bb-section-body">' + body + '</div></div>';
  }

  function navbarExample(bgClass, theme, brand) {
    return '<nav class="navbar navbar-expand-lg ' + bgClass + ' mb-2" data-bs-theme="' + theme + '">' +
      '<div class="container-fluid">' +
      '<a class="navbar-brand" data-x="#">' + brand + '</a>' +
      '<ul class="navbar-nav me-auto mb-0">' +
      '<li class="nav-item"><a class="nav-link active" data-x="#">Home</a></li>' +
      '<li class="nav-item"><a class="nav-link" data-x="#">Features</a></li>' +
      '<li class="nav-item"><a class="nav-link" data-x="#">Pricing</a></li>' +
      '<li class="nav-item"><a class="nav-link disabled">Disabled</a></li></ul>' +
      '<form class="d-flex" role="search"><input class="form-control form-control-sm me-2" type="search" placeholder="Search">' +
      '<button class="btn btn-sm ' + (theme === 'light' ? 'btn-outline-dark' : 'btn-outline-light') + '" type="button">Search</button></form>' +
      '</div></nav>';
  }

  function btn(v) { return '<button class="btn btn-' + v + ' me-1 mb-1">' + cap(v) + '</button>'; }
  function bsAlert(v) { return '<div class="alert alert-' + v + '" role="alert">A ' + v + ' alert with an <a data-x="#" class="alert-link">example link</a>.</div>'; }
  function badge(v) { return '<span class="badge text-bg-' + v + ' me-1">' + cap(v) + '</span>'; }
  function progress(v, p) { return '<div class="progress mb-2" role="progressbar"><div class="progress-bar bg-' + v + '" style="width:' + p + '%">' + p + '%</div></div>'; }
  function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

  // Resolve a possibly-relative URL ("/typo3temp/...") to an absolute one using the
  // parent document's origin, so it loads inside the about:blank srcdoc iframe.
  function absUrl(u) {
    if (!u) return u;
    if (/^https?:\/\//i.test(u)) return u;
    try { return new URL(u, window.location.origin).href; } catch (e) { return u; }
  }

  function patchVars(doc) {
    if (!doc || !doc.documentElement) return;
    document.querySelectorAll("[data-bb-key]").forEach(function (el) {
      var prop = CSSVAR[el.getAttribute("data-bb-key")];
      if (!prop || !el.value) return;
      try { doc.documentElement.style.setProperty("--bs-" + prop, el.value); } catch (e) {}
    });
  }

  // Overlay the freshly compiled PREVIEW css onto the real frontend page shown in the
  // frontend iframe, WITHOUT changing the published frontend. Only works same-origin
  // (backend and frontend on the same domain). The live frontend files are untouched;
  // this only swaps the stylesheet inside this iframe view.
  function overlayPreviewCss(ff) {
    try {
      var previewCss = ff.getAttribute("data-preview-css");
      if (!previewCss) return;
      var doc = ff.contentDocument;
      if (!doc) return; // cross-origin: cannot touch, frontend shows published CSS as-is
      var bust = previewCss + (previewCss.indexOf("?") === -1 ? "?" : "&") + "bbts=" + Date.now();
      var links = doc.querySelectorAll('link[rel="stylesheet"]');
      var replaced = false;
      links.forEach(function (l) {
        var href = l.getAttribute("href") || "";
        // t3sbootstrap's compiled theme css lives in the T3SB-SCSS / typo3temp css output.
        if (/T3SB-SCSS|t3sbootstrap|bootstrap|theme/i.test(href)) {
          l.setAttribute("href", bust);
          replaced = true;
        }
      });
      // If we couldn't identify it, append the preview css last so it overrides.
      if (!replaced) {
        var link = doc.createElement("link");
        link.rel = "stylesheet";
        link.href = bust;
        doc.head.appendChild(link);
      }
    } catch (e) {
      // Cross-origin or other access error: leave the frontend as published.
    }
  }

  function init() {
    // While dragging the resize handle, disable iframe pointer events so the drag
    // isn't swallowed by the iframe content.
    var resizeWrap = document.getElementById("bb-resize");
    if (resizeWrap) {
      resizeWrap.addEventListener("mousedown", function (e) {
        // The resize handle sits in the bottom-right corner.
        var r = resizeWrap.getBoundingClientRect();
        if (e.clientX > r.right - 24 && e.clientY > r.bottom - 24) {
          resizeWrap.classList.add("bb-resizing");
        }
      });
      window.addEventListener("mouseup", function () {
        resizeWrap.classList.remove("bb-resizing");
      });
    }

    // When an editor group is opened, scroll the component preview to the matching section.
    function slugify(s) {
      return 'sec-' + (s || '').replace(/&/g, '').replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    }
    var allGroups = document.querySelectorAll(".bb-group");
    document.querySelectorAll(".bb-group > summary").forEach(function (sum) {
      sum.addEventListener("click", function () {
        var details = sum.parentElement;
        // Only act when opening (click fires before the [open] toggles, so check current state).
        var willOpen = !details.hasAttribute("open");
        if (!willOpen) return;
        // Exclusive accordion: close every other group, keep only this one open.
        allGroups.forEach(function (g) { if (g !== details) g.removeAttribute("open"); });
        var title = sum.textContent.trim();
        var frame = document.getElementById("bb-preview-frame");
        if (!frame) return;
        // Make sure the Components tab is visible.
        var compTab = document.querySelector('.bb-tab[data-tab="components"]');
        if (compTab && !compTab.classList.contains("active")) compTab.click();
        setTimeout(function () {
          try {
            var doc = frame.contentDocument;
            var el = doc && doc.getElementById(slugify(title));
            if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
          } catch (e) {}
        }, 120);
      });
    });

    // Preview tabs: Components vs Frontend.
    var tabs = document.querySelectorAll(".bb-tab");
    var frontendLoaded = false;
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = tab.getAttribute("data-tab");
        tabs.forEach(function (t) { t.classList.toggle("active", t === tab); });
        document.querySelectorAll(".bb-tabpane").forEach(function (p) {
          p.classList.toggle("bb-hidden", p.getAttribute("data-pane") !== target);
        });
        if (target === "frontend" && !frontendLoaded) {
          var ff = document.getElementById("bb-frontend-frame");
          var fspin = document.getElementById("bb-frontend-spinner");
          if (ff) {
            var url = ff.getAttribute("data-frontend-url");
            if (url) {
              if (fspin) fspin.classList.remove("bb-hidden");
              ff.addEventListener("load", function () {
                if (fspin) fspin.classList.add("bb-hidden");
                overlayPreviewCss(ff);
              });
              ff.src = url;
              frontendLoaded = true;
            }
          }
        }
      });
    });

    // Color picker <-> text field sync.
    var isHex = function (v) { return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test((v || "").trim()); };
    document.querySelectorAll(".bb-color-picker").forEach(function (picker) {
      var targetId = picker.getAttribute("data-picker-for");
      var text = document.getElementById(targetId);
      if (!text) return;
      // Initialize picker from the text field's current value if it's a valid hex.
      if (isHex(text.value)) picker.value = text.value.trim();
      // Picker -> text field (and trigger live preview update).
      picker.addEventListener("input", function () {
        text.value = picker.value;
        text.dispatchEvent(new Event("input", { bubbles: true }));
        text.dispatchEvent(new Event("change", { bubbles: true }));
      });
      // Text field -> picker (only when it's a clean hex; rgba/refs leave picker as-is).
      text.addEventListener("input", function () {
        if (isHex(text.value)) picker.value = text.value.trim();
      });
    });

    // Theme-color reference select -> writes an SCSS reference ($primary, …) into the text field.
    document.querySelectorAll(".bb-color-ref").forEach(function (sel) {
      var targetId = sel.getAttribute("data-ref-for");
      var text = document.getElementById(targetId);
      if (!text) return;
      sel.addEventListener("change", function () {
        if (!sel.value) return;
        text.value = sel.value;
        text.dispatchEvent(new Event("input", { bubbles: true }));
        text.dispatchEvent(new Event("change", { bubbles: true }));
        sel.value = ""; // reset to placeholder so it can be picked again
      });
    });

    var presetBtn = document.getElementById("bb-preset-load");
    if (presetBtn) {
      presetBtn.addEventListener("click", function () {
        var sel = document.getElementById("bb-preset-select");
        var row = document.querySelector(".bb-preset-row");
        if (!sel || !row) return;
        var preset = sel.value || "";

        var all = {};
        try { all = JSON.parse(row.getAttribute("data-preset-values") || "{}"); } catch (e) { all = {}; }
        var vals = all[preset] || {};

        // Fill each editor field whose key matches a resolved preset value.
        document.querySelectorAll("[data-bb-key]").forEach(function (el) {
          var key = el.getAttribute("data-bb-key");
          if (Object.prototype.hasOwnProperty.call(vals, key)) {
            el.value = vals[key];
            el.dispatchEvent(new Event("input", { bubbles: true }));
            el.dispatchEvent(new Event("change", { bubbles: true }));
          }
        });

        // Remember the chosen preset for the Apply form (hidden basePreset field).
        var hidden = document.querySelector("input[name='tx_t3sbootstrapbuilder_web_t3sbootstrapbuilder[basePreset]'], input[name='basePreset']");
        if (hidden) hidden.value = preset;
      });
    }

    var spinner = document.getElementById("bb-preview-spinner");

    var applyForm = document.getElementById("bb-form");
    var intentField = document.getElementById("bb-intent");
    var btnPreview = document.getElementById("bb-btn-preview");
    var btnPublish = document.getElementById("bb-btn-publish");
    var btnExport = document.getElementById("bb-btn-export");
    var exporting = false;
    var ajaxPreviewUrl = applyForm ? applyForm.getAttribute("data-ajax-preview-url") : "";
    var ajaxSite = applyForm ? applyForm.getAttribute("data-site") : "";

    // PREVIEW via AJAX: compile server-side and refresh ONLY the preview iframe — no page
    // reload, so open groups, scroll position and field state are preserved. Falls back to
    // a normal form submit if fetch or the endpoint URL isn't available.
    if (btnPreview && intentField) {
      btnPreview.addEventListener("click", function (e) {
        if (!window.fetch || !ajaxPreviewUrl || !applyForm) {
          intentField.value = "preview";
          return; // normal submit fallback
        }
        e.preventDefault();
        if (spinner) spinner.classList.remove("bb-hidden");

        var fd = new FormData(applyForm);
        fd.set("siteIdentifier", ajaxSite || "");
        fd.set("intent", "preview");

        fetch(ajaxPreviewUrl, {
          method: "POST",
          body: fd,
          headers: { "X-Requested-With": "XMLHttpRequest" },
          credentials: "same-origin"
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.success && data.cssUrl) {
              var pf = document.getElementById("bb-preview-frame");
              if (pf) {
                pf.setAttribute("data-css-url", data.cssUrl);
                pf.srcdoc = sampleHtml(absUrl(data.cssUrl), pf.getAttribute("data-js-url"));
              }
              // Also refresh the frontend overlay if that tab was already loaded.
              var ff = document.getElementById("bb-frontend-frame");
              if (ff && ff.getAttribute("src") && ff.getAttribute("src") !== "about:blank") {
                ff.setAttribute("data-preview-css", data.cssUrl);
                try { overlayPreviewCss(ff); } catch (e2) {}
              }
              // Spinner hides on the new stylesheet's load (handled below); safety timeout:
              setTimeout(function () { if (spinner) spinner.classList.add("bb-hidden"); }, 4000);
            } else {
              if (spinner) spinner.classList.add("bb-hidden");
              window.alert((data && data.message) || "Preview failed.");
            }
          })
          .catch(function () {
            if (spinner) spinner.classList.add("bb-hidden");
            window.alert("Preview request failed.");
          });
      });
    }
    if (btnPublish && intentField) {
      btnPublish.addEventListener("click", function (e) {
        var msg = btnPublish.getAttribute("data-confirm") || "Really publish?";
        if (!window.confirm(msg)) {
          e.preventDefault();
          return;
        }
        intentField.value = "publish";
      });
    }
    if (btnExport && intentField) {
      btnExport.addEventListener("click", function () { intentField.value = "export"; exporting = true; });
    }

    // Show the spinner the moment Preview/Publish is submitted (not for export downloads),
    // and carry that state across the page reload via a sessionStorage flag.
    if (applyForm && spinner) {
      applyForm.addEventListener("submit", function () {
        if (exporting) return; // export triggers a file download, no reload/preview
        try { sessionStorage.setItem("bbApplyPending", "1"); } catch (e) {}
        spinner.classList.remove("bb-hidden");
      });
    }

    var frame = document.getElementById("bb-preview-frame");
    if (!frame) return;
    var cssUrl = frame.getAttribute("data-css-url");
    if (!cssUrl) return;

    // Only keep the spinner visible on load if we just came from an Apply submit.
    var cameFromApply = false;
    try {
      cameFromApply = sessionStorage.getItem("bbApplyPending") === "1";
      sessionStorage.removeItem("bbApplyPending");
    } catch (e) {}

    var spinnerShownAt = Date.now();
    var MIN_SPINNER_MS = 400;
    var hideSpinner = function () {
      if (!spinner) return;
      var elapsed = Date.now() - spinnerShownAt;
      var wait = Math.max(0, MIN_SPINNER_MS - elapsed);
      setTimeout(function () { spinner.classList.add("bb-hidden"); }, wait);
    };

    if (cameFromApply) {
      spinner.classList.remove("bb-hidden");
    } else if (spinner) {
      // Normal module open: no spinner.
      spinner.classList.add("bb-hidden");
    }
    // Safety net: hide the spinner even if the stylesheet load event never fires.
    var spinnerTimeout = setTimeout(hideSpinner, 8000);

    frame.srcdoc = sampleHtml(cssUrl, frame.getAttribute("data-js-url"));

    frame.addEventListener("load", function () {
      var doc;
      try { doc = frame.contentDocument; } catch (e) { doc = null; }
      if (!doc) { clearTimeout(spinnerTimeout); hideSpinner(); return; }

      // The iframe "load" fires when the srcdoc HTML is in place, but the big compiled
      // stylesheet loads AFTER that. Hide the spinner once that <link> has loaded (or
      // errored). A minimum display time (above) prevents it from merely flashing.
      var link = doc.querySelector('link[rel="stylesheet"]');
      if (link) {
        var done = function () { clearTimeout(spinnerTimeout); hideSpinner(); };
        if (link.sheet) {
          done();
        } else {
          link.addEventListener("load", done);
          link.addEventListener("error", done);
        }
      } else {
        clearTimeout(spinnerTimeout);
        hideSpinner();
      }

      patchVars(doc);
      document.querySelectorAll("[data-bb-key]").forEach(function (el) {
        el.addEventListener("input", function () { patchVars(doc); });
        el.addEventListener("change", function () { patchVars(doc); });
      });
    });
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
