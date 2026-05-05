(function () {
  'use strict';

  var config = window.GithubChatWidgetAdmin || {};
  var usageData = config.usageData && typeof config.usageData === 'object' ? config.usageData : {};

  var descriptions = window.GithubChatWidgetModelDescriptions || {};

  function padTwo(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function formatResetTime(ts) {
    if (!ts || ts <= 0) {
      return null;
    }
    var d = new Date(ts * 1000);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
      + ' '
      + padTwo(d.getHours()) + ':' + padTwo(d.getMinutes());
  }

  function buildUsageHTML(modelId) {
    var entry = usageData[modelId];

    if (!entry || entry.remaining === null) {
      return '<span class="gcw-usage-unknown">Status: Usage data unknown &mdash; make a request to refresh.</span>';
    }

    var remaining = entry.remaining;
    var limit = entry.limit;
    var fractionClass = 'gcw-usage-ok';

    if (limit !== null && limit > 0) {
      var pct = remaining / limit;
      if (pct <= 0.1) {
        fractionClass = 'gcw-usage-critical';
      } else if (pct <= 0.3) {
        fractionClass = 'gcw-usage-low';
      }
    }

    var quotaText = limit !== null
      ? remaining + ' / ' + limit + ' requests left'
      : remaining + ' requests left';

    var resetText = '';
    var resetFormatted = formatResetTime(entry.reset);
    if (resetFormatted) {
      resetText = ' &mdash; resets ' + resetFormatted;
    }

    var tokenText = '';
    if (entry.remaining_tokens !== null) {
      tokenText = '<span class="gcw-usage-tokens"> &middot; ' + entry.remaining_tokens.toLocaleString() + ' tokens remaining</span>';
    }

    return '<span class="gcw-usage-quota ' + fractionClass + '">'
      + '<strong>Estimated Quota:</strong> ' + quotaText + resetText
      + '</span>'
      + tokenText;
  }

  function updateModel(modelId) {
    var descEl = document.getElementById('github_chat_widget_model_desc');
    var usageEl = document.getElementById('github_chat_widget_usage_indicator');

    if (descEl) {
      descEl.textContent = descriptions[modelId] || '';
    }

    if (usageEl) {
      usageEl.innerHTML = buildUsageHTML(modelId);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('github_chat_widget_model');
    if (!sel) {
      return;
    }

    updateModel(sel.value);

    sel.addEventListener('change', function () {
      updateModel(sel.value);
    });

    // Chat History Modal
    var modal = document.getElementById('gcw-history-modal');
    var modalMessages = document.getElementById('gcw-history-modal-messages');
    var modalTitle = modal ? modal.querySelector('#gcw-history-modal-title strong') : null;
    var modalClose = modal ? modal.querySelector('.gcw-history-modal-close') : null;
    var modalBackdrop = modal ? modal.querySelector('.gcw-history-modal-backdrop') : null;

    function closeHistoryModal() {
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    }

    function openHistoryModal(email, messagesJson) {
      if (!modal || !modalMessages) return;

      var messages = [];
      try {
        messages = JSON.parse(messagesJson);
      } catch (e) {
        messages = [];
      }
      if (!Array.isArray(messages)) messages = [];

      if (modalTitle) modalTitle.textContent = email;

      var html = '';
      messages.forEach(function (msg) {
        if (!msg || !msg.role) return;
        var role = msg.role;
        var text = '';

        if (typeof msg.content === 'string') {
          text = msg.content;
        } else if (msg.content && typeof msg.content === 'object') {
          // assistant payload may have main_answer
          text = msg.content.main_answer || JSON.stringify(msg.content);
        }

        if (role === 'system') return; // skip system messages

        var roleClass = role === 'user' ? 'gcw-hm-user' : 'gcw-hm-assistant';
        var roleLabel = role === 'user' ? 'User' : 'Assistant';
        html += '<div class="gcw-hm-bubble ' + roleClass + '">'
          + '<span class="gcw-hm-role">' + roleLabel + '</span>'
          + '<p class="gcw-hm-text">' + escHtml(String(text)) + '</p>'
          + '</div>';
      });

      if (html === '') {
        html = '<p class="gcw-hm-empty">No messages to display.</p>';
      }

      modalMessages.innerHTML = html;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      modalMessages.scrollTop = 0;
    }

    function escHtml(str) {
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/\n/g, '<br>');
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.gcw-view-history-btn');
      if (btn) {
        openHistoryModal(btn.dataset.email, btn.dataset.messages);
        return;
      }
    });

    if (modalClose) modalClose.addEventListener('click', closeHistoryModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeHistoryModal);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeHistoryModal();
    });
  });
}());
