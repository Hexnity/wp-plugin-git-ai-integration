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
  });
}());
