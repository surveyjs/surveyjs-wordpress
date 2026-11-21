(function () {
    const { __, sprintf } = wp.i18n;
    const options = window.surveyjsAiChat;
    const siteMode = Boolean(options) && !options.formId;
    const editor = siteMode ? null : window.surveyjsEditor;
    const rootElement = document.querySelector(siteMode ? "#wpbody-content > .wrap" : ".wp-sjs-editor");
    if (!options || (!siteMode && !editor) || !rootElement) return;

    const OPEN_KEY = siteMode ? "surveyjs-ai-chat-site-open" : "surveyjs-ai-chat-open";
    const PROGRESS_MS = 1200;
    const SVG_NS = "http://www.w3.org/2000/svg";
    const SUGGESTIONS = siteMode ? [
        __("Create a contact form with name, email and message", "surveyjs"),
        __("Create an event registration form from a template and add a dietary needs question", "surveyjs"),
        __("Where do I set up CAPTCHA?", "surveyjs"),
        __("How do I put a form on a page?", "surveyjs")
    ] : [
        __("Add a required phone number question", "surveyjs"),
        __("Make all questions required", "surveyjs"),
        __("Translate this form into German", "surveyjs"),
        __("Send new entries to sales@example.com", "surveyjs"),
        __("How do I put this form on a page?", "surveyjs")
    ];

    function callEditor(method, ...args) {
        return editor && editor[method] ? editor[method](...args) : undefined;
    }

    let state = null;
    let messages = [];
    let busy = false;
    let loading = null;
    let lastError = null;
    let pendingText = "";
    let progressTimer = null;

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function dashicon(name) {
        const node = element("span", "dashicons dashicons-" + name);
        node.setAttribute("aria-hidden", "true");
        return node;
    }

    function sparkle() {
        const svg = document.createElementNS(SVG_NS, "svg");
        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("aria-hidden", "true");
        svg.setAttribute("class", "wp-sjs-chat__sparkle");
        const path = document.createElementNS(SVG_NS, "path");
        path.setAttribute("d", "M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.9 2.6 2.6.9-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13zM5 15l.7 1.8 1.8.7-1.8.7L5 20l-.7-1.8-1.8-.7 1.8-.7L5 15z");
        svg.append(path);
        return svg;
    }

    function button(label, className, onClick) {
        const node = element("button", "button" + (className ? " " + className : ""), label);
        node.type = "button";
        node.addEventListener("click", onClick);
        return node;
    }

    function post(action, data) {
        const body = new URLSearchParams(Object.assign({ action: "surveyjs_ai_chat_" + action, _ajax_nonce: options.nonce, id: options.formId }, data));
        return fetch(options.ajaxUrl, { method: "POST", credentials: "same-origin", body: body })
            .then((response) => response.json().catch(() => ({ success: false, data: { message: response.statusText || __("The server did not answer. Try again.", "surveyjs") } })))
            .then((result) => {
                if (!result || !result.success) {
                    const data = (result && result.data) || {};
                    const error = new Error(data.message || __("Something went wrong. Try again.", "surveyjs"));
                    error.data = data;
                    throw error;
                }
                return result.data;
            });
    }

    function adminLink(label, url) {
        let target = null;
        try {
            target = new URL(url, window.location.href);
        } catch (error) {
            return null;
        }
        if (target.origin !== window.location.origin || !target.pathname.includes("/wp-admin/")) return null;
        const link = element("a", "", label);
        link.href = target.href;
        return link;
    }

    function appendInline(parent, text) {
        const pattern = /(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)\s]+\))/g;
        let last = 0;
        text.replace(pattern, (match, token, offset) => {
            if (offset > last) parent.append(text.slice(last, offset));
            if (token.startsWith("[")) {
                const parts = token.match(/^\[([^\]]+)\]\(([^)\s]+)\)$/);
                parent.append(adminLink(parts[1], parts[2]) || parts[1]);
                last = offset + token.length;
                return match;
            }
            parent.append(token.startsWith("**") ? element("strong", "", token.slice(2, -2)) : element("code", "", token.slice(1, -1)));
            last = offset + token.length;
            return match;
        });
        if (last < text.length) parent.append(text.slice(last));
    }

    function renderText(container, text) {
        let list = null;
        String(text).split(/\n/).forEach((line) => {
            const item = line.match(/^\s*(?:[-*•]|\d+[.)])\s+(.*)$/);
            if (item) {
                if (!list) {
                    list = element(/^\s*\d/.test(line) ? "ol" : "ul");
                    container.append(list);
                }
                const li = element("li");
                appendInline(li, item[1]);
                list.append(li);
                return;
            }
            list = null;
            if (line.trim() === "") return;
            const paragraph = element("p");
            appendInline(paragraph, line.replace(/^#+\s*/, ""));
            container.append(paragraph);
        });
    }

    const toggle = element("button", "button wp-sjs-chat-toggle");
    toggle.type = "button";
    toggle.setAttribute("aria-controls", "wp-sjs-chat");
    toggle.setAttribute("aria-expanded", "false");
    toggle.append(sparkle(), element("span", "", __("AI assistant", "surveyjs")));

    const panel = element("aside", "wp-sjs-chat");
    panel.id = "wp-sjs-chat";
    panel.hidden = true;
    panel.setAttribute("aria-label", __("AI assistant", "surveyjs"));

    const header = element("div", "wp-sjs-chat__header");
    const title = element("h2", "wp-sjs-chat__title");
    title.append(sparkle(), element("span", "", __("AI assistant", "surveyjs")));
    const newChat = button("", "wp-sjs-chat__icon-button", () => startNewChat());
    newChat.append(dashicon("plus-alt2"), element("span", "", __("New chat", "surveyjs")));
    newChat.title = __("Start new chat", "surveyjs");
    const close = button("", "wp-sjs-chat__icon-button wp-sjs-chat__close", () => setOpen(false));
    close.append(dashicon("no-alt"));
    close.setAttribute("aria-label", __("Close the AI assistant", "surveyjs"));
    const headerActions = element("div", "wp-sjs-chat__header-actions");
    headerActions.append(newChat, close);
    header.append(title, headerActions);

    const modelRow = element("div", "wp-sjs-chat__model");
    const modelLabel = element("label", "", __("Model", "surveyjs"));
    modelLabel.htmlFor = "wp-sjs-chat-model";
    const modelSelect = element("select");
    modelSelect.id = "wp-sjs-chat-model";
    modelRow.append(modelLabel, modelSelect);
    modelRow.hidden = true;

    const log = element("div", "wp-sjs-chat__log");
    log.setAttribute("role", "log");
    log.setAttribute("aria-live", "polite");

    const status = element("div", "wp-sjs-chat__status");
    status.setAttribute("role", "status");
    status.hidden = true;

    const composer = element("form", "wp-sjs-chat__composer");
    const input = element("textarea", "wp-sjs-chat__input");
    input.id = "wp-sjs-chat-input";
    input.rows = 3;
    input.maxLength = options.maxLength;
    input.placeholder = __("Ask for a change or ask a question…", "surveyjs");
    input.setAttribute("aria-label", __("Message to the AI assistant", "surveyjs"));
    const counter = element("span", "wp-sjs-chat__counter");
    const sendButton = element("button", "button button-primary wp-sjs-chat__send", __("Send", "surveyjs"));
    sendButton.type = "submit";
    const composerRow = element("div", "wp-sjs-chat__composer-row");
    composerRow.append(counter, sendButton);
    const privacy = element("p", "wp-sjs-chat__privacy");
    composer.append(input, composerRow, privacy);
    composer.hidden = true;

    panel.append(header, modelRow, log, status, composer);
    rootElement.append(panel);
    if (siteMode) {
        toggle.className = "page-title-action wp-sjs-chat-toggle";
        const actions = rootElement.querySelectorAll(".page-title-action");
        const last = actions[actions.length - 1] || rootElement.querySelector("h1");
        if (last) last.after(toggle);
    } else {
        const anchor = document.getElementById("wp-sjs-full-preview") || document.getElementById("wp-sjs-save");
        if (anchor) anchor.before(toggle);
    }

    function billingLine(provider) {
        return sprintf(__("Requests use your own API key and are billed by %s, not by SurveyJS.", "surveyjs"), provider);
    }

    function updateCounter() {
        counter.textContent = sprintf(__("%1$d / %2$d", "surveyjs"), input.value.length, options.maxLength);
        sendButton.disabled = busy || input.value.trim() === "";
    }

    function updatePrivacy() {
        privacy.replaceChildren();
        if (!state || !state.choice) return;
        privacy.append(billingLine(state.choice.providerName) + " " + (siteMode
            ? sprintf(__("Your message and the forms and settings the AI reads go to %s. Form entries are never sent.", "surveyjs"), state.choice.providerName)
            : sprintf(__("Your message and this form's questions and settings go to %s. Form entries are never sent.", "surveyjs"), state.choice.providerName)));
    }

    function renderModels() {
        modelSelect.replaceChildren();
        if (!state || !state.ready) {
            modelRow.hidden = true;
            return;
        }
        state.providers.forEach((provider) => {
            const group = element("optgroup");
            group.label = provider.name;
            provider.models.forEach((model) => {
                const option = element("option", "", model.name);
                option.value = provider.id + "|" + model.id;
                option.selected = state.choice && state.choice.provider === provider.id && state.choice.model === model.id;
                group.append(option);
            });
            modelSelect.append(group);
        });
        modelRow.hidden = false;
    }

    function changesList(changes) {
        const items = [];
        if (changes.name) items.push(__("Form name", "surveyjs"));
        if (changes.added.length) items.push(sprintf(__("Added: %s", "surveyjs"), changes.added.join(", ")));
        if (changes.changed.length) items.push(sprintf(__("Changed: %s", "surveyjs"), changes.changed.join(", ")));
        if (changes.removed.length) items.push(sprintf(__("Removed: %s", "surveyjs"), changes.removed.join(", ")));
        if (changes.survey) items.push(__("Form properties", "surveyjs"));
        if (changes.theme) items.push(__("Theme", "surveyjs"));
        if (changes.settings.length) items.push(sprintf(__("Settings: %s", "surveyjs"), changes.settings.map((key) => callEditor("settingLabel", key) || key).join("; ")));
        return items;
    }

    function renderMessage(message) {
        if (message.role === "notice") {
            return element("div", "wp-sjs-chat__notice", message.text);
        }
        const node = element("div", "wp-sjs-chat__message is-" + message.role);
        node.dataset.message = message.id;
        const bubble = element("div", "wp-sjs-chat__bubble");
        if (message.role === "user") {
            bubble.textContent = message.text;
        } else {
            renderText(bubble, message.text);
        }
        node.append(bubble);
        if (message.changes) {
            const box = element("div", "wp-sjs-chat__changes" + (message.undone ? " is-undone" : ""));
            const heading = element("p", "wp-sjs-chat__changes-title");
            heading.append(dashicon(message.undone ? "undo" : "edit"), message.undone ? __("Changes undone", "surveyjs") : __("Changed in the draft", "surveyjs"));
            box.append(heading);
            const list = element("ul");
            changesList(message.changes).forEach((item) => list.append(element("li", "", item)));
            box.append(list);
            if (message.canUndo) {
                const undoButton = button("", "wp-sjs-chat__undo", () => undo(message.id, false, undoButton));
                undoButton.append(dashicon("undo"), element("span", "", __("Undo the last AI change", "surveyjs")));
                box.append(undoButton);
            } else if (!message.undone) {
                box.append(element("p", "wp-sjs-chat__hint", __("Review it in the editor and click Save to publish.", "surveyjs")));
            }
            node.append(box);
        }
        if (message.forms && message.forms.length) {
            const box = element("div", "wp-sjs-chat__changes wp-sjs-chat__forms");
            const heading = element("p", "wp-sjs-chat__changes-title");
            heading.append(dashicon("edit"), __("Saved as drafts", "surveyjs"));
            box.append(heading);
            message.forms.forEach((form) => {
                const row = element("p", "wp-sjs-chat__form");
                const link = element("a", "button", sprintf(__("Open \"%s\" to review and click Save", "surveyjs"), form.name));
                link.href = form.editUrl;
                row.append(link);
                if (form.created) row.append(" ", element("span", "wp-sjs-chat__hint", __("New form, not on the site until you save it.", "surveyjs")));
                box.append(row);
            });
            if (message.forms.some((form) => form.created)) {
                const reload = element("a", "", __("Reload the list of forms", "surveyjs"));
                reload.href = window.location.href;
                const reloadRow = element("p", "wp-sjs-chat__hint");
                reloadRow.append(reload);
                box.append(reloadRow);
            }
            node.append(box);
        }
        const meta = element("div", "wp-sjs-chat__meta", message.role === "user" ? message.time : [message.model, message.time].filter(Boolean).join(" · "));
        node.append(meta);
        return node;
    }

    function renderEmpty() {
        const box = element("div", "wp-sjs-chat__empty");
        box.append(sparkle());
        if (siteMode) {
            box.append(element("p", "wp-sjs-chat__greeting", __("Ask me to create a form, start one from a template, change a form or its settings, or ask how to do something in SurveyJS Forms.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("Everything I create or change is saved as a draft: you open the form, review it and click Save. I can't put forms on pages, but I can tell you how.", "surveyjs")));
        } else {
            box.append(element("p", "wp-sjs-chat__greeting", __("Tell me what to change in this form, or ask how to do something in SurveyJS Forms.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("I work on what you see in the editor, including unsaved edits. My changes show up here as a draft: you review them and click Save.", "surveyjs")));
        }
        if (state && state.choice) box.append(element("p", "wp-sjs-chat__billing", billingLine(state.choice.providerName)));
        const list = element("div", "wp-sjs-chat__suggestions");
        SUGGESTIONS.forEach((text) => list.append(button(text, "wp-sjs-chat__suggestion", () => {
            input.value = text;
            updateCounter();
            input.focus();
        })));
        box.append(list);
        return box;
    }

    function renderSetup() {
        const box = element("div", "wp-sjs-chat__setup");
        box.append(element("h3", "", __("Set up the AI chat", "surveyjs")));
        box.append(element("p", "", __("The chat uses an AI provider connected to WordPress with your own API key. The provider bills each request.", "surveyjs")));
        const steps = element("ol", "wp-sjs-chat__setup-steps");
        [
            [state.steps.key, __("Connect an AI provider in Settings > Connectors", "surveyjs")],
            [state.ready, __("Use a provider that can edit forms", "surveyjs")]
        ].forEach(([done, text]) => {
            const item = element("li", done ? "is-done" : "");
            item.append(dashicon(done ? "yes-alt" : "marker"), element("span", "", text));
            steps.append(item);
        });
        box.append(steps);
        state.problems.forEach((problem) => box.append(element("p", "wp-sjs-chat__problem", problem)));
        const actions = element("p", "wp-sjs-chat__setup-actions");
        const setupLink = element("a", "button button-primary", __("Open the setup steps", "surveyjs"));
        setupLink.href = options.settingsUrl;
        actions.append(setupLink, " ", button(__("Check again", "surveyjs"), "", () => load(true)));
        box.append(actions);
        return box;
    }

    function renderError() {
        const box = element("div", "wp-sjs-chat__error");
        box.setAttribute("role", "alert");
        box.append(element("p", "", lastError.message));
        if (lastError.details) {
            const details = element("details");
            details.append(element("summary", "", __("Details", "surveyjs")), element("p", "", lastError.details));
            box.append(details);
        }
        const actions = element("p", "wp-sjs-chat__error-actions");
        if (lastError.link) {
            const link = element("a", "button", lastError.link[1]);
            link.href = lastError.link[0];
            actions.append(link, " ");
        }
        if (lastError.retry && lastError.text) {
            actions.append(button(__("Try again", "surveyjs"), "button-primary", () => send(lastError.text)));
        }
        if (actions.childNodes.length) box.append(actions);
        return box;
    }

    function render() {
        log.replaceChildren();
        if (loading) {
            log.append(element("p", "wp-sjs-chat__loading", __("Loading…", "surveyjs")));
            composer.hidden = true;
            return;
        }
        if (!state) {
            if (lastError) log.append(renderError());
            composer.hidden = true;
            return;
        }
        if (!state.ready) {
            log.append(renderSetup());
            composer.hidden = true;
            newChat.hidden = true;
            renderModels();
            return;
        }
        newChat.hidden = messages.length === 0;
        if (messages.length === 0 && !pendingText) log.append(renderEmpty());
        messages.forEach((message) => log.append(renderMessage(message)));
        if (pendingText) log.append(renderMessage({ id: "pending", role: "user", text: pendingText, time: "" }));
        if (lastError) log.append(renderError());
        composer.hidden = false;
        renderModels();
        updatePrivacy();
        updateCounter();
        log.scrollTop = log.scrollHeight;
    }

    function load(refresh) {
        loading = post("state", refresh ? { refresh: 1 } : {})
            .then((data) => {
                state = data;
                messages = data.messages;
                lastError = null;
            })
            .catch((error) => {
                state = null;
                lastError = { message: error.message };
            })
            .finally(() => {
                loading = null;
                render();
            });
        render();
        return loading;
    }

    function setBusy(value, label) {
        busy = value;
        callEditor("setAiBusy", value);
        panel.classList.toggle("is-busy", value);
        input.disabled = value;
        modelSelect.disabled = value;
        newChat.disabled = value;
        status.hidden = !value;
        status.replaceChildren();
        if (value) status.append(element("span", "wp-sjs-chat__spinner"), element("span", "", label || __("Thinking…", "surveyjs")));
        updateCounter();
    }

    function watchProgress(request) {
        clearInterval(progressTimer);
        progressTimer = setInterval(() => {
            post("progress", { request: request }).then((data) => {
                if (busy && data.label) setBusy(true, data.label);
            }).catch(() => {});
        }, PROGRESS_MS);
    }

    function send(text) {
        text = text.trim();
        if (!text || busy) return;
        const snapshot = siteMode ? {} : callEditor("getSnapshot");
        if (!snapshot) {
            lastError = { message: __("Fix the JSON errors in the editor first: the AI works on what you see there.", "surveyjs") };
            render();
            return;
        }
        const request = Math.random().toString(36).slice(2, 14);
        lastError = null;
        pendingText = text;
        input.value = "";
        setBusy(true);
        render();
        watchProgress(request);
        post("send", siteMode ? { message: text, request: request } : { message: text, editor: JSON.stringify(snapshot), request: request })
            .then((data) => {
                messages = data.messages;
                if (data.changed) callEditor("applyAiDraft", data.draft);
            })
            .catch((error) => {
                const data = error.data || {};
                if (data.changed) callEditor("applyAiDraft", data.draft);
                if (data.setup) {
                    load(true);
                    return;
                }
                if (!input.value) input.value = text;
                lastError = { message: error.message, details: data.details, link: data.link, retry: data.retry !== false, text: text };
            })
            .finally(() => {
                clearInterval(progressTimer);
                pendingText = "";
                setBusy(false);
                render();
                input.focus();
            });
    }

    function undo(messageId, force, trigger) {
        if (busy) return;
        if (!force && callEditor("hasEditsAfterAi") && !window.confirm(__("Undo also discards the edits you made in the editor after this AI change. Continue?", "surveyjs"))) return;
        if (trigger) trigger.disabled = true;
        setBusy(true, __("Undoing…", "surveyjs"));
        post("undo", { message: messageId, force: force ? 1 : 0 })
            .then((data) => {
                messages = data.messages;
                callEditor("applyAiDraft", data.draft, data.editor);
                lastError = null;
            })
            .catch((error) => {
                if (error.data && error.data.conflict) {
                    setBusy(false);
                    if (window.confirm(error.message + "\n\n" + __("Undo anyway?", "surveyjs"))) setTimeout(() => undo(messageId, true), 0);
                    else render();
                    return;
                }
                lastError = { message: error.message };
            })
            .finally(() => {
                if (busy) {
                    setBusy(false);
                    render();
                }
            });
    }

    function startNewChat() {
        if (busy || !window.confirm(__("Start a new chat? This clears the conversation and AI Undo for this form. Your form doesn't change.", "surveyjs"))) return;
        post("reset", {})
            .then(() => {
                messages = [];
                lastError = null;
                render();
                input.focus();
            })
            .catch((error) => {
                lastError = { message: error.message };
                render();
            });
    }

    function remember(open) {
        try {
            window.localStorage.setItem(OPEN_KEY, open ? "1" : "0");
        } catch (error) {
            return;
        }
    }

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute("aria-expanded", String(open));
        toggle.classList.toggle("is-active", open);
        rootElement.classList.toggle("wp-sjs-chat-open", open);
        remember(open);
        if (open) {
            if (!state && !loading) load(false);
            setTimeout(() => (composer.hidden ? close : input).focus(), 0);
        } else {
            toggle.focus();
        }
        window.dispatchEvent(new Event("resize"));
    }

    toggle.addEventListener("click", () => setOpen(panel.hidden));
    panel.addEventListener("keydown", (event) => {
        if (event.key === "Escape") setOpen(false);
    });
    input.addEventListener("input", updateCounter);
    input.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            send(input.value);
        }
    });
    composer.addEventListener("submit", (event) => {
        event.preventDefault();
        send(input.value);
    });
    modelSelect.addEventListener("change", () => {
        post("model", { model: modelSelect.value })
            .then((choice) => {
                state.choice = choice;
                updatePrivacy();
            })
            .catch((error) => {
                lastError = { message: error.message };
                render();
            });
    });

    let reopen = false;
    try {
        reopen = window.localStorage.getItem(OPEN_KEY) === "1";
    } catch (error) {
        reopen = false;
    }
    if (reopen) setOpen(true);
})();
