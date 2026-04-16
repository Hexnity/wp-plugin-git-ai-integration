(function () {
  'use strict';

  var STORAGE_KEY = 'github_chat_widget_state_v1';
  var MAX_STORED_MESSAGES = 20;

  function safeJSONParse(value) {
    try {
      return JSON.parse(value);
    } catch (err) {
      return null;
    }
  }

  function splitPayload(payload) {
    var content = String(payload || '');
    var uiAction = null;

    if (content.indexOf('|UI_DATA|') !== -1) {
      var parts = content.split('|UI_DATA|');
      content = parts[0] || '';
      uiAction = safeJSONParse(parts[1] || '');
    }

    return { content: content, uiAction: uiAction };
  }

  function createElement(tag, className, text) {
    var el = document.createElement(tag);
    if (className) {
      el.className = className;
    }
    if (typeof text === 'string') {
      el.textContent = text;
    }
    return el;
  }

  function scrollToBottom(container) {
    container.scrollTop = container.scrollHeight;
  }

  function normalizeSectionTarget(value) {
    return String(value || '').trim();
  }

  function findTargetElement(sectionKey) {
    var target = document.getElementById(sectionKey);
    if (target) {
      return target;
    }

    return document.querySelector('[data-chat-section="' + sectionKey + '"]');
  }

  function isUrlTarget(value) {
    var target = String(value || '').trim().toLowerCase();
    return target.indexOf('/') === 0 || target.indexOf('http://') === 0 || target.indexOf('https://') === 0;
  }

  function loadStoredState() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return null;
      }

      var parsed = safeJSONParse(raw);
      if (!parsed || typeof parsed !== 'object') {
        return null;
      }

      return parsed;
    } catch (err) {
      return null;
    }
  }

  function saveStoredState(state) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
        isOpen: !!state.isOpen
      }));
    } catch (err) {
      return;
    }
  }

  function normalizeStoredMessages(messages) {
    if (!Array.isArray(messages)) {
      return [];
    }

    return messages.reduce(function (result, message) {
      if (!message || typeof message !== 'object') {
        return result;
      }

      var role = '';
      if (message.role === 'user') {
        role = 'user';
      } else if (message.role === 'ai' || message.role === 'assistant') {
        role = 'ai';
      }
      var content = typeof message.content === 'string' ? message.content : '';

      if (!role || !content) {
        return result;
      }

      result.push({
        role: role,
        content: content,
        uiAction: message.uiAction && typeof message.uiAction === 'object' ? message.uiAction : null
      });

      return result;
    }, []).slice(-MAX_STORED_MESSAGES);
  }

  function applyConfigToRoot(root, config) {
    var position = String(config.chatPosition || 'bottom-right');
    var className = 'is-pos-' + position;
    root.classList.add(className);

    var offsetX = Number(config.offsetX || 24);
    var offsetY = Number(config.offsetY || 24);
    var panelWidth = Number(config.panelWidth || 420);
    var panelHeight = Number(config.panelHeight || 560);

    root.style.setProperty('--gcw-offset-x', offsetX + 'px');
    root.style.setProperty('--gcw-offset-y', offsetY + 'px');
    root.style.setProperty('--gcw-panel-width', panelWidth + 'px');
    root.style.setProperty('--gcw-panel-height', panelHeight + 'px');
    root.style.setProperty('--gcw-panel-bg', String(config.panelBgColor || '#111827'));
    root.style.setProperty('--gcw-accent', String(config.accentColor || '#10b981'));
    root.style.setProperty('--gcw-request-text', String(config.requestTextColor || '#ffffff'));
    root.style.setProperty('--gcw-response-text', String(config.responseTextColor || '#d1d5db'));
    root.style.setProperty('--gcw-launcher-border-color', String(config.launcherBorderColor || '#0f172a'));
    root.style.setProperty('--gcw-launcher-border-width', Number(config.launcherBorderWidth || 2) + 'px');
    root.style.setProperty('--gcw-title-font-size', String(config.titleFontSize || 'clamp(0.95rem, 0.9rem + 0.2vw, 1.05rem)'));
    root.style.setProperty('--gcw-body-font-size', String(config.bodyFontSize || 'clamp(0.875rem, 0.84rem + 0.15vw, 0.95rem)'));
    root.style.setProperty('--gcw-input-font-size', String(config.inputFontSize || 'clamp(0.875rem, 0.84rem + 0.15vw, 1rem)'));
    root.style.setProperty('--gcw-button-font-size', String(config.buttonFontSize || 'clamp(0.75rem, 0.72rem + 0.15vw, 0.875rem)'));
  }

  function appendMessage(messagesWrap, message, config) {
    var row = createElement('div', 'github-chat-widget-row ' + (message.role === 'user' ? 'is-user' : 'is-ai'));
    var avatar = createElement('div', 'github-chat-widget-avatar', message.role === 'user' ? 'You' : 'AI');
    var bubble = createElement('div', 'github-chat-widget-bubble', message.content || '');

    row.appendChild(avatar);
    row.appendChild(bubble);

    if (message.role === 'ai' && config.enableUiButtons && message.uiAction && message.uiAction.show_button) {
      var actions = createElement('div', 'github-chat-widget-actions');
      var buttonLabel = message.uiAction.button_label || config.defaultButtonLabel || 'Open';
      var cta = createElement('button', 'github-chat-widget-nav-button', buttonLabel);

      cta.type = 'button';
      cta.addEventListener('click', function () {
        var directUrl = message.uiAction.target_url || message.uiAction.url || '';
        if (isUrlTarget(directUrl)) {
          window.location.href = directUrl;
          return;
        }

        var routeMap = config.buttonRouteMap || {};
        var routeKey = normalizeSectionTarget(message.uiAction.route_key || '').toLowerCase();
        if (routeKey && routeMap[routeKey] && isUrlTarget(routeMap[routeKey].url)) {
          window.location.href = routeMap[routeKey].url;
          return;
        }

        var sections = Array.isArray(message.uiAction.sections) ? message.uiAction.sections : [];
        var allowedSections = Array.isArray(config.allowedSections)
          ? config.allowedSections.map(function (value) {
            return normalizeSectionTarget(value).toLowerCase();
          })
          : [];
        sections = sections.filter(function (value) {
          var normalized = normalizeSectionTarget(value);
          if (!normalized) {
            return false;
          }
          if (!allowedSections.length) {
            return true;
          }
          return allowedSections.indexOf(normalized.toLowerCase()) !== -1;
        });

        if (sections.length > 0) {
          var sectionKey = normalizeSectionTarget(sections[0]);
          var normalizedSectionKey = sectionKey.toLowerCase();
          var mappedRoute = routeMap[normalizedSectionKey] || routeMap[sectionKey] || null;

          if (mappedRoute && isUrlTarget(mappedRoute.url)) {
            window.location.href = mappedRoute.url;
            return;
          }

          var target = findTargetElement(normalizedSectionKey) || findTargetElement(sectionKey);
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          } else if (isUrlTarget(sectionKey)) {
            window.location.href = sections[0];
          }
        }

        var event = new CustomEvent('github_chat_widget_navigate', {
          detail: {
            sections: sections,
            generated_prompt: message.uiAction.generated_prompt || ''
          }
        });
        window.dispatchEvent(event);
      });

      actions.appendChild(cta);
      bubble.appendChild(actions);
    }

    messagesWrap.appendChild(row);
    scrollToBottom(messagesWrap);

    return bubble;
  }

  function typeText(element, content, onDone) {
    var text = String(content || '');
    var i = 0;
    var speed = 12;

    function tick() {
      if (i >= text.length) {
        if (typeof onDone === 'function') {
          onDone();
        }
        return;
      }
      element.textContent = text.slice(0, i + 1);
      i += 1;
      window.setTimeout(tick, speed);
    }

    tick();
  }

  async function startSession(email) {
    var config = window.GithubChatWidgetConfig || {};
    var response = await fetch(config.sessionUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ email: email })
    });

    if (!response.ok) {
      var data = null;
      try {
        data = await response.json();
      } catch (err) {
        data = null;
      }
      var errorMessage = (data && (data.error || data.message)) ? (data.error || data.message) : 'Unable to start session.';
      throw new Error(errorMessage);
    }

    var json = await response.json();
    return {
      email: String((json && json.email) || ''),
      messages: normalizeStoredMessages((json && json.messages) || [])
    };
  }

  async function sendToApi(email, messages) {
    var config = window.GithubChatWidgetConfig || {};
    var response = await fetch(config.restUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        email: email,
        messages: messages
      })
    });

    if (!response.ok) {
      var data = null;
      try {
        data = await response.json();
      } catch (err) {
        data = null;
      }
      var errorMessage = (data && (data.error || data.message)) ? (data.error || data.message) : 'Agent Offline.';
      throw new Error(errorMessage);
    }

    var json = await response.json();
    return json.output || '';
  }

  function mountWidget(root) {
    var config = window.GithubChatWidgetConfig || {};
    var chatTitle = config.chatTitle || 'Github Chat';
    var welcomeText = config.welcomeText || 'Hi! How can I help you today?';
    var thinkingText = config.thinkingText || 'Thinking...';
    var inputPlaceholder = config.inputPlaceholder || 'Ask anything...';
    var sendButtonText = config.sendButtonText || 'Send';
    var emailTitleText = config.emailTitle || 'Enter your email to start chat';
    var emailPlaceholderText = config.emailPlaceholder || 'you@example.com';
    var emailButtonText = config.emailButtonText || 'Start Chat';
    var changeEmailText = config.changeEmailText || 'Change Email';
    var launcherAriaLabel = config.launcherAriaLabel || 'Open chat';
    var launcherIconText = config.launcherIcon || String.fromCodePoint(0x1F4AC);
    var launcherImageUrl = config.launcherImageUrl || '';
    var allowedSections = Array.isArray(config.allowedSections) ? config.allowedSections : [];
    config.buttonRouteMap = config.buttonRouteMap || {};

    config.allowedSections = allowedSections;

    applyConfigToRoot(root, config);

    var storedState = loadStoredState();

    var state = {
      isOpen: !!(storedState && storedState.isOpen),
      isLoading: false,
      email: '',
      messages: []
    };

    root.innerHTML = '';

    var launcher = createElement('button', 'github-chat-widget-launcher', '');
    launcher.type = 'button';
    launcher.setAttribute('aria-label', launcherAriaLabel);
    if (launcherImageUrl) {
      var launcherImage = createElement('img', 'github-chat-widget-launcher-image', '');
      launcherImage.src = launcherImageUrl;
      launcherImage.alt = '';
      launcherImage.loading = 'lazy';
      launcher.appendChild(launcherImage);
    } else {
      var launcherIcon = createElement('span', 'github-chat-widget-launcher-icon', '');
      launcherIcon.textContent = launcherIconText;
      launcher.appendChild(launcherIcon);
    }

    var panel = createElement('section', 'github-chat-widget-panel');
    panel.setAttribute('aria-hidden', 'true');

    var header = createElement('header', 'github-chat-widget-header');
    var title = createElement('h4', 'github-chat-widget-title', chatTitle);
    var changeEmail = createElement('button', 'github-chat-widget-change-email', changeEmailText);
    changeEmail.type = 'button';
    changeEmail.style.display = 'none';
    var close = createElement('button', 'github-chat-widget-close', '×');
    close.type = 'button';
    close.setAttribute('aria-label', 'Close chat');

    header.appendChild(title);
    header.appendChild(changeEmail);
    header.appendChild(close);

    var emailGate = createElement('div', 'github-chat-widget-email-gate');
    var emailTitle = createElement('h5', 'github-chat-widget-email-title', emailTitleText);
    var emailForm = createElement('form', 'github-chat-widget-email-form');
    var emailInput = createElement('input', 'github-chat-widget-email-input');
    emailInput.type = 'email';
    emailInput.placeholder = emailPlaceholderText;
    emailInput.autocomplete = 'email';
    emailInput.required = true;

    var emailSubmit = createElement('button', 'github-chat-widget-email-submit', emailButtonText);
    emailSubmit.type = 'submit';
    var emailError = createElement('p', 'github-chat-widget-email-error', '');

    emailForm.appendChild(emailInput);
    emailForm.appendChild(emailSubmit);
    emailGate.appendChild(emailTitle);
    emailGate.appendChild(emailForm);
    emailGate.appendChild(emailError);

    var messagesWrap = createElement('div', 'github-chat-widget-messages');
    var emptyState = createElement('div', 'github-chat-widget-empty', welcomeText);
    if (!state.messages.length) {
      messagesWrap.appendChild(emptyState);
    }

    var thinking = createElement('div', 'github-chat-widget-thinking', thinkingText);

    var form = createElement('form', 'github-chat-widget-form');
    var input = createElement('input', 'github-chat-widget-input');
    input.type = 'text';
    input.placeholder = inputPlaceholder;
    input.autocomplete = 'off';

    var send = createElement('button', 'github-chat-widget-send', sendButtonText);
    send.type = 'submit';

    form.appendChild(input);
    form.appendChild(send);

    panel.appendChild(header);
    panel.appendChild(emailGate);
    panel.appendChild(messagesWrap);
    panel.appendChild(thinking);
    panel.appendChild(form);

    root.appendChild(panel);
    root.appendChild(launcher);

    function renderMessages() {
      messagesWrap.innerHTML = '';
      if (!state.messages.length) {
        messagesWrap.appendChild(emptyState);
        return;
      }

      state.messages.forEach(function (message) {
        var restoredMessage = {
          role: message.role,
          content: message.content,
          uiAction: config.enableUiButtons ? message.uiAction : null
        };
        appendMessage(messagesWrap, restoredMessage, config);
      });
    }

    function setSessionReady(isReady) {
      emailGate.style.display = isReady ? 'none' : 'block';
      messagesWrap.style.display = isReady ? 'flex' : 'none';
      form.style.display = isReady ? 'flex' : 'none';
      changeEmail.style.display = isReady ? 'inline-flex' : 'none';

      if (!isReady) {
        state.messages = [];
        renderMessages();
      }
    }

    function togglePanel(open) {
      state.isOpen = open;
      panel.classList.toggle('is-open', open);
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      launcher.classList.toggle('is-open', open);
      saveStoredState(state);
      if (open) {
        window.setTimeout(function () {
          if (state.email) {
            input.focus();
          } else {
            emailInput.focus();
          }
          scrollToBottom(messagesWrap);
        }, 20);
      }
    }

    function setLoading(isLoading) {
      state.isLoading = isLoading;
      root.classList.toggle('is-loading', isLoading);
      send.disabled = isLoading;
      input.disabled = isLoading;
      emailInput.disabled = isLoading;
      emailSubmit.disabled = isLoading;
      thinking.style.display = isLoading ? 'block' : 'none';
    }

    launcher.addEventListener('click', function () {
      togglePanel(!state.isOpen);
    });

    close.addEventListener('click', function () {
      togglePanel(false);
    });

    changeEmail.addEventListener('click', function () {
      state.email = '';
      state.messages = [];
      emailInput.value = '';
      emailError.textContent = '';
      setSessionReady(false);
      emailInput.focus();
    });

    emailForm.addEventListener('submit', async function (event) {
      event.preventDefault();

      var rawEmail = emailInput.value.trim().toLowerCase();
      if (!rawEmail || state.isLoading) {
        return;
      }

      emailError.textContent = '';
      setLoading(true);

      try {
        var session = await startSession(rawEmail);
        state.email = session.email;
        state.messages = normalizeStoredMessages(session.messages);
        saveStoredState(state);
        renderMessages();
        setSessionReady(true);
        input.focus();
      } catch (error) {
        emailError.textContent = error.message || 'Unable to start session.';
      } finally {
        setLoading(false);
      }
    });

    form.addEventListener('submit', async function (event) {
      event.preventDefault();

      var text = input.value.trim();
      if (!state.email || !text || state.isLoading) {
        return;
      }

      if (emptyState.parentNode) {
        emptyState.parentNode.removeChild(emptyState);
      }

      var userMessage = { role: 'user', content: text };
      state.messages.push(userMessage);
      state.messages = state.messages.slice(-MAX_STORED_MESSAGES);
      saveStoredState(state);
      appendMessage(messagesWrap, userMessage, config);

      input.value = '';
      setLoading(true);

      try {
        var output = await sendToApi(state.email, state.messages);
        var parsed = splitPayload(output);
        var safeContent = String(parsed.content || '');

        var aiMessage = {
          role: 'ai',
          content: '',
          uiAction: parsed.uiAction
        };

        if (!config.enableUiButtons) {
          aiMessage.uiAction = null;
          parsed.uiAction = null;
        }

        state.messages.push({
          role: 'ai',
          content: safeContent,
          uiAction: parsed.uiAction
        });
        state.messages = state.messages.slice(-MAX_STORED_MESSAGES);
        saveStoredState(state);

        var bubble = appendMessage(messagesWrap, aiMessage, config);
        typeText(bubble, safeContent, function () {
          if (parsed.uiAction && parsed.uiAction.show_button) {
            bubble.textContent = safeContent;
            var actionMsg = {
              role: 'ai',
              content: safeContent,
              uiAction: parsed.uiAction
            };
            if (bubble.parentNode && messagesWrap.contains(bubble.parentNode)) {
              messagesWrap.removeChild(bubble.parentNode);
            }
            appendMessage(messagesWrap, actionMsg, config);
          }
          saveStoredState(state);
        });
      } catch (error) {
        var errorMessage = { role: 'ai', content: error.message || 'Agent Offline.', uiAction: null };
        state.messages.push(errorMessage);
        state.messages = state.messages.slice(-MAX_STORED_MESSAGES);
        saveStoredState(state);
        appendMessage(messagesWrap, errorMessage, config);
      } finally {
        setLoading(false);
      }
    });

    setSessionReady(false);
    togglePanel(state.isOpen);
  }

  function init() {
    var roots = document.querySelectorAll('.github-chat-widget-root');
    if (!roots.length) {
      return;
    }

    roots.forEach(function (root) {
      mountWidget(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
