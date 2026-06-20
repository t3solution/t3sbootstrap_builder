/* Initializes Bootstrap tooltips & popovers inside the preview iframe. */
(function () {
  function init() {
    if (!window.bootstrap) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      try { new window.bootstrap.Tooltip(el); } catch (e) {}
    });
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      try { new window.bootstrap.Popover(el); } catch (e) {}
    });
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
