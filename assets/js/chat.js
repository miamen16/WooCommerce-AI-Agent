(function () {
  'use strict';
  function init(widget) {
    const form = widget.querySelector('.wcaia-form');
    const input = widget.querySelector('.wcaia-input');
    const messages = widget.querySelector('.wcaia-messages');
    if (!form || !input || !messages) return;

    function addMessage(text, kind) {
      const node = document.createElement('div');
      node.className = 'wcaia-message ' + kind;
      node.textContent = text;
      messages.appendChild(node);
      messages.scrollTop = messages.scrollHeight;
    }

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      const message = input.value.trim();
      if (!message) return;
      addMessage(message, 'wcaia-user');
      input.value = '';
      const button = form.querySelector('button');
      button.disabled = true;
      try {
        const response = await fetch(WCAIA.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': WCAIA.nonce },
          body: JSON.stringify({ message: message })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || WCAIA.strings.error);
        addMessage(data.reply || WCAIA.strings.error, 'wcaia-bot');
      } catch (error) {
        addMessage(error.message || WCAIA.strings.error, 'wcaia-bot');
      } finally {
        button.disabled = false;
        input.focus();
      }
    });
  }
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.wcaia-widget').forEach(init);
  });
})();