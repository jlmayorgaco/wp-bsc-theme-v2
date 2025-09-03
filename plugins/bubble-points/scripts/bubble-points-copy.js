jQuery(function ($) {
  function flashFeedback($el) {
    const prev = $el.attr("aria-label");
    $el.addClass("is-copied").attr("aria-label", "¡Copiado!");
    setTimeout(() => {
      $el.removeClass("is-copied");
      if (prev) $el.attr("aria-label", prev);
    }, 1200);
  }

  function copyToClipboard(text, $trigger) {
    if (!text) return;
    if (navigator.clipboard && window.isSecureContext !== false) {
      navigator.clipboard.writeText(text).then(() => flashFeedback($trigger));
    } else {
      // Fallback for older browsers / non-HTTPS
      const ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.opacity = "0";
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand("copy"); } catch (e) {}
      document.body.removeChild(ta);
      flashFeedback($trigger);
    }
  }

  // Click or Enter/Space on the pill or the button
  $(document).on("click keydown", ".js-copy-coupon", function (e) {
    if (e.type === "keydown" && !(e.key === "Enter" || e.key === " ")) return;
    e.preventDefault();
    copyToClipboard($(this).data("code"), $(this));
  });
});
