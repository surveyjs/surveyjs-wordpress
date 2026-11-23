(function () {
    const { __, sprintf } = wp.i18n;
    const options = window.surveyjsAiChat;
    const mode = (options && options.mode) || "form";
    const siteMode = mode !== "form";
    const editor = siteMode ? null : window.surveyjsEditor;
    const rootElement = document.querySelector(siteMode ? "#wpbody-content > .wrap" : ".wp-sjs-editor");
    if (!options || (!siteMode && !editor) || !rootElement) return;

    const context = (options && options.context) || {};
    const screen = context.screen || "forms";
    const entriesScreen = screen === "results" || screen === "dashboard";
    const OPEN_KEY = "surveyjs-ai-chat-" + mode + "-open";
    const PROGRESS_MS = 1200;
    const SVG_NS = "http://www.w3.org/2000/svg";
    function suggestions() {
        const entries = Boolean(state && state.entries);
        if (entriesScreen) {
            return entries ? [
                __("Summarize entries for the last 30 days", "surveyjs"),
                __("Which form gets the most entries?", "surveyjs"),
                __("Find common themes in answers", "surveyjs")
            ] : [
                __("How do I export entries to CSV?", "surveyjs"),
                __("How do I find entries from last week?", "surveyjs"),
                __("How do I download an entry as a PDF?", "surveyjs")
            ];
        }
        if (screen === "welcome") {
            return [
                __("What can this plugin do?", "surveyjs"),
                __("Create my first form", "surveyjs"),
                __("How do I put a form on a page?", "surveyjs")
            ];
        }
        if (screen === "settings") {
            return [
                __("Where do I set up CAPTCHA?", "surveyjs"),
                __("Explain this setting", "surveyjs"),
                __("How do I delete old entries automatically?", "surveyjs")
            ];
        }
        if (siteMode) {
            return [
                __("Create a contact form with name, email and message", "surveyjs"),
                __("Create an event registration form from a template and add a dietary needs question", "surveyjs"),
                entries ? __("Which form got the most entries this month?", "surveyjs") : __("Where do I set up CAPTCHA?", "surveyjs"),
                __("How do I put a form on a page?", "surveyjs")
            ];
        }
        return [
            __("Add a required phone number question", "surveyjs"),
            __("Translate this form into German", "surveyjs"),
            __("Send new entries to sales@example.com", "surveyjs"),
            entries ? __("Summarize the latest entries of this form", "surveyjs") : __("How do I put this form on a page?", "surveyjs")
        ];
    }

    function callEditor(method, ...args) {
        return editor && editor[method] ? editor[method](...args) : undefined;
    }

    let state = null;
    let intro = false;
    let messages = [];
    let busy = false;
    let loading = null;
    let lastError = null;
    let pendingText = "";
    let pendingFiles = [];
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

    function post(action, data, extra) {
        const where = siteMode ? { context_page: context.page || "", context_tab: context.tab || "", context_form: context.form || 0, context_view: context.view || "" } : {};
        const fields = Object.assign({ action: "surveyjs_ai_chat_" + action, _ajax_nonce: options.nonce, id: options.formId }, where, data);
        let body = new URLSearchParams(fields);
        if (extra && extra.files && extra.files.length) {
            body = new FormData();
            Object.keys(fields).forEach((key) => body.append(key, fields[key]));
            extra.files.forEach((item) => body.append("files[]", item, item.name));
        }
        return fetch(options.ajaxUrl, { method: "POST", credentials: "same-origin", body: body, signal: extra && extra.signal })
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
    input.placeholder = entriesScreen ? __("Ask about the entries or how to work with them…", "surveyjs") : __("Ask for a change or ask a question…", "surveyjs");
    input.setAttribute("aria-label", __("Message to the AI assistant", "surveyjs"));
    const counter = element("span", "wp-sjs-chat__counter");
    const sendButton = element("button", "button button-primary wp-sjs-chat__send", __("Send", "surveyjs"));
    sendButton.type = "submit";
    const tools = element("div", "wp-sjs-chat__tools");
    const fileInput = element("input");
    fileInput.type = "file";
    fileInput.multiple = true;
    fileInput.hidden = true;
    const attachButton = button("", "wp-sjs-chat__icon-button wp-sjs-chat__attach", () => fileInput.click());
    attachButton.append(dashicon("paperclip"));
    attachButton.hidden = true;
    const micButton = button("", "wp-sjs-chat__icon-button wp-sjs-chat__mic", () => toggleDictation());
    micButton.append(dashicon("microphone"));
    micButton.hidden = true;
    const webLabel = element("label", "wp-sjs-chat__web");
    const webToggle = element("input");
    webToggle.type = "checkbox";
    webLabel.append(webToggle, " ", __("Search the web", "surveyjs"));
    webLabel.hidden = true;
    tools.append(attachButton, micButton, webLabel, fileInput);
    const attachmentList = element("ul", "wp-sjs-chat__attachments");
    attachmentList.hidden = true;
    const composerRow = element("div", "wp-sjs-chat__composer-row");
    composerRow.append(tools, counter, sendButton);
    const privacy = element("p", "wp-sjs-chat__privacy");
    composer.append(attachmentList, input, composerRow, privacy);
    composer.hidden = true;

    panel.append(header, modelRow, log, status, composer);
    rootElement.append(panel);
    const starters = document.querySelectorAll("[data-sjs-ai-start]");
    if (siteMode) {
        toggle.className = "page-title-action wp-sjs-chat-toggle";
        const actions = rootElement.querySelectorAll(".page-title-action");
        const heading = rootElement.querySelector("h1, h2");
        if (!actions.length && heading) heading.classList.add("wp-heading-inline");
        const last = actions[actions.length - 1] || heading;
        if (last) last.after(toggle);
        else rootElement.prepend(toggle);
        // WordPress moves admin notices below .wp-header-end, or right below the first heading when there is none.
        if (!rootElement.querySelector(".wp-header-end")) toggle.after(element("hr", "wp-header-end"));
    } else {
        const anchor = document.getElementById("wp-sjs-full-preview") || document.getElementById("wp-sjs-save");
        if (anchor) anchor.before(toggle);
    }

    function billingLine(provider) {
        return sprintf(__("Requests use your own API key and are billed by %s, not by SurveyJS.", "surveyjs"), provider);
    }

    function updateCounter() {
        counter.textContent = sprintf(__("%1$d / %2$d", "surveyjs"), input.value.length, options.maxLength);
        sendButton.disabled = stopping || (!busy && input.value.trim() === "");
        sendButton.textContent = busy ? (stopping ? __("Stopping…", "surveyjs") : __("Stop", "surveyjs")) : __("Send", "surveyjs");
        sendButton.classList.toggle("is-stop", busy);
    }

    let attachments = [];
    let stopping = false;
    let controller = null;
    let currentRequest = "";

    function acceptedTypes() {
        const kinds = state && state.choice ? state.choice.files || [] : [];
        return kinds.flatMap((kind) => options.fileTypes[kind] || []);
    }

    function updateTools() {
        const choice = state && state.choice;
        const types = acceptedTypes();
        attachButton.hidden = !types.length;
        fileInput.accept = types.join(",");
        const kinds = (choice && choice.files) || [];
        attachButton.title = sprintf(__("Attach images, PDF or audio the model can read (%s): for example a photo or scan of a paper form. Up to %d files; files are sent to the AI provider with this message and are not stored on the site.", "surveyjs"), kinds.join(", "), options.maxFiles);
        attachButton.setAttribute("aria-label", __("Attach files", "surveyjs"));
        webLabel.hidden = !(choice && choice.webSearch);
        webLabel.title = choice ? sprintf(__("%s searches the web while answering this message. The search words go to the provider and the search may cost extra.", "surveyjs"), choice.providerName) : "";
        if (webLabel.hidden) webToggle.checked = false;
        attachments = attachments.filter((item) => types.includes(item.type));
        renderAttachments();
    }

    function renderAttachments() {
        attachmentList.replaceChildren();
        attachmentList.hidden = attachments.length === 0;
        attachments.forEach((item, index) => {
            const chip = element("li", "wp-sjs-chat__attachment");
            chip.append(dashicon(item.type.startsWith("image/") ? "format-image" : (item.type.startsWith("audio/") ? "format-audio" : "media-document")), element("span", "", item.name));
            const remove = button("", "wp-sjs-chat__icon-button", () => {
                attachments.splice(index, 1);
                renderAttachments();
            });
            remove.append(dashicon("no-alt"));
            remove.setAttribute("aria-label", sprintf(__("Remove %s", "surveyjs"), item.name));
            chip.append(remove);
            attachmentList.append(chip);
        });
    }

    function addFiles(list) {
        const types = acceptedTypes();
        const problems = [];
        Array.from(list || []).forEach((item) => {
            const total = attachments.reduce((sum, other) => sum + other.size, 0) + item.size;
            if (!types.includes(item.type)) {
                problems.push(sprintf(__("%s: this model can't read this kind of file.", "surveyjs"), item.name));
            } else if (attachments.length >= options.maxFiles) {
                problems.push(sprintf(__("Attach up to %d files at a time.", "surveyjs"), options.maxFiles));
            } else if (item.size > options.maxFileBytes || total > options.maxTotalBytes) {
                problems.push(sprintf(__("%1$s is too large: up to %2$s MB per file.", "surveyjs"), item.name, Math.floor(options.maxFileBytes / 1048576)));
            } else {
                attachments.push(item);
            }
        });
        lastError = problems.length ? { message: problems.join(" ") } : lastError;
        renderAttachments();
        if (problems.length) render();
    }

    let recognition = null;
    let dictationBase = "";
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const dictationNote = __("Dictate. Your browser turns speech into text and may send the audio to its speech service (Google in Chrome, Microsoft in Edge, Apple in Safari). SurveyJS Forms never receives the audio; only the text goes to your AI provider when you send it.", "surveyjs");
    if (Recognition) {
        micButton.hidden = false;
        micButton.title = dictationNote;
        micButton.setAttribute("aria-label", __("Dictate a message", "surveyjs"));
        micButton.setAttribute("aria-pressed", "false");
    }

    function stopDictation() {
        if (recognition) recognition.stop();
    }

    function toggleDictation() {
        if (recognition) {
            stopDictation();
            return;
        }
        const lang = options.locale || navigator.language || "en-US";
        const start = (local) => {
            recognition = new Recognition();
            recognition.lang = lang;
            recognition.interimResults = true;
            recognition.continuous = true;
            if (local) recognition.processLocally = true;
            dictationBase = input.value ? input.value.replace(/s*$/, " ") : "";
            recognition.onresult = (event) => {
                let finalText = "";
                let interim = "";
                for (let i = 0; i < event.results.length; i++) {
                    if (event.results[i].isFinal) finalText += event.results[i][0].transcript;
                    else interim += event.results[i][0].transcript;
                }
                input.value = (dictationBase + finalText + interim).slice(0, options.maxLength);
                updateCounter();
            };
            recognition.onerror = (event) => {
                if (["not-allowed", "service-not-allowed", "network", "language-not-supported", "audio-capture"].includes(event.error)) {
                    micButton.hidden = true;
                    lastError = { message: __("Dictation is not available in this browser or was blocked. Type your message instead.", "surveyjs") };
                    render();
                }
            };
            recognition.onend = () => {
                recognition = null;
                micButton.classList.remove("is-active");
                micButton.setAttribute("aria-pressed", "false");
                input.focus();
            };
            micButton.title = local ? __("Dictate. Speech is recognized on this device; the audio does not leave it.", "surveyjs") : dictationNote;
            micButton.classList.add("is-active");
            micButton.setAttribute("aria-pressed", "true");
            recognition.start();
        };
        if (typeof Recognition.available === "function") {
            Recognition.available({ langs: [lang], processLocally: true })
                .then((result) => start(result === "available"))
                .catch(() => start(false));
        } else {
            start(false);
        }
    }

    function updatePrivacy() {
        privacy.replaceChildren();
        if (!state || !state.choice) return;
        const sent = mode === "form"
            ? sprintf(__("Your message and this form's questions and settings go to %s.", "surveyjs"), state.choice.providerName)
            : sprintf(__("Your message and the forms and settings the AI reads go to %s.", "surveyjs"), state.choice.providerName);
        const entries = state.entries
            ? __("Entries the AI reads are sent too, because reading entries is allowed.", "surveyjs")
            : __("Form entries are never sent.", "surveyjs");
        privacy.append(billingLine(state.choice.providerName) + " " + sent + " " + entries);
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
            (message.files || []).forEach((name) => {
                const chip = element("span", "wp-sjs-chat__file");
                chip.append(dashicon("paperclip"), element("span", "", name));
                bubble.append(chip);
            });
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
        const meta = element("div", "wp-sjs-chat__meta", message.role === "user" ? message.time : [message.stopped ? __("Stopped", "surveyjs") : "", message.model, message.time].filter(Boolean).join(" · "));
        if (message.stopped) node.classList.add("is-stopped");
        node.append(meta);
        return node;
    }

    function renderIntro() {
        const node = element("div", "wp-sjs-chat__message is-assistant wp-sjs-chat__intro");
        const bubble = element("div", "wp-sjs-chat__bubble");
        bubble.append(element("p", "", __("What form do you want to create? Describe it in your own words. I can find a ready-made template, adapt a template or build a new form from scratch.", "surveyjs")));
        node.append(bubble);
        const list = element("div", "wp-sjs-chat__suggestions");
        [
            __("Find a ready-made template for a customer feedback survey", "surveyjs"),
            __("Take the event registration template and add a question about dietary needs", "surveyjs"),
            __("Create a new job application form with name, email, a CV upload and a cover letter", "surveyjs")
        ].forEach((text) => list.append(button(text, "wp-sjs-chat__suggestion", () => {
            input.value = text;
            updateCounter();
            input.focus();
        })));
        node.append(list);
        return node;
    }

    function renderEmpty() {
        const box = element("div", "wp-sjs-chat__empty");
        box.append(sparkle());
        if (entriesScreen) {
            box.append(element("p", "wp-sjs-chat__greeting", state && state.entries
                ? (screen === "dashboard"
                    ? __("Ask me about the entries of all your forms: which forms get the most entries, trends, summaries and common answers.", "surveyjs")
                    : __("Ask me about the entries of this form: summaries, common answers, trends, or how to export and filter them.", "surveyjs"))
                : __("Ask me how to export, filter or download entries, or how to set up your forms.", "surveyjs")));
            if (!(state && state.entries)) {
                const hint = element("p", "wp-sjs-chat__hint", __("I can't read the entries: reading them is turned off. ", "surveyjs"));
                const link = element("a", "", __("Allow the AI chat to read entries", "surveyjs"));
                link.href = options.settingsUrl + "#wp-sjs-ai-chat-entries";
                hint.append(link);
                box.append(hint);
            }
        } else if (screen === "welcome") {
            box.append(element("p", "wp-sjs-chat__greeting", __("Ask me what SurveyJS Forms can do, or let me create your first form from your description or a template.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("Everything I create or change is saved as a draft: you open the form, review it and click Save.", "surveyjs")));
        } else if (screen === "settings") {
            box.append(element("p", "wp-sjs-chat__greeting", __("Ask me what a setting does or where to find it. I can't see this screen, so name the setting you mean.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("I never get your keys and passwords: CAPTCHA secret keys, the license key and AI provider keys stay on the site.", "surveyjs")));
        } else if (siteMode) {
            box.append(element("p", "wp-sjs-chat__greeting", __("Ask me to create a form, start one from a template, change a form or its settings, or ask how to do something in SurveyJS Forms.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("Everything I create or change is saved as a draft: you open the form, review it and click Save. I can't put forms on pages, but I can tell you how.", "surveyjs")));
        } else {
            box.append(element("p", "wp-sjs-chat__greeting", __("Tell me what to change in this form, or ask how to do something in SurveyJS Forms.", "surveyjs")));
            box.append(element("p", "wp-sjs-chat__hint", __("I work on what you see in the editor, including unsaved edits. My changes show up here as a draft: you review them and click Save.", "surveyjs")));
        }
        if (state && state.choice) box.append(element("p", "wp-sjs-chat__billing", billingLine(state.choice.providerName)));
        const list = element("div", "wp-sjs-chat__suggestions");
        suggestions().forEach((text) => list.append(button(text, "wp-sjs-chat__suggestion", () => {
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
        if (messages.length === 0 && !pendingText && !intro) log.append(renderEmpty());
        messages.forEach((message) => log.append(renderMessage(message)));
        if (intro && !pendingText) log.append(renderIntro());
        if (pendingText) log.append(renderMessage({ id: "pending", role: "user", text: pendingText, files: pendingFiles, time: "" }));
        if (lastError) log.append(renderError());
        composer.hidden = false;
        renderModels();
        updatePrivacy();
        updateTools();
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
        if (!value) stopping = false;
        attachButton.disabled = value;
        micButton.disabled = value;
        webToggle.disabled = value;
        if (value) stopDictation();
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

    function finishStopped(request) {
        const started = Date.now();
        const poll = () => post("state", {}).then((data) => {
            const reply = data.messages.findIndex((message) => message.role === "user" && message.request === request);
            if (reply === -1 && Date.now() - started < 180000) {
                setTimeout(poll, 1500);
                return;
            }
            state = data;
            messages = data.messages;
            const answer = reply === -1 ? null : data.messages[reply + 1];
            if (answer && answer.changes) callEditor("applyAiDraft", data.draft);
            clearInterval(progressTimer);
            pendingText = "";
            setBusy(false);
            render();
        }).catch(() => setTimeout(poll, 3000));
        poll();
    }

    function stop() {
        if (!busy || stopping || !currentRequest) return;
        stopping = true;
        setBusy(true, __("Stopping…", "surveyjs"));
        const request = currentRequest;
        post("cancel", { request: request }).finally(() => {
            if (controller) controller.abort();
            finishStopped(request);
        });
    }

    function send(text) {
        if (busy) {
            stop();
            return;
        }
        text = text.trim();
        if (!text) return;
        const snapshot = siteMode ? {} : callEditor("getSnapshot");
        if (!snapshot) {
            lastError = { message: __("Fix the JSON errors in the editor first: the AI works on what you see there.", "surveyjs") };
            render();
            return;
        }
        const request = Math.random().toString(36).slice(2, 14);
        intro = false;
        const files = attachments.slice();
        const fields = siteMode ? { message: text, request: request } : { message: text, editor: JSON.stringify(snapshot), request: request };
        const issues = siteMode ? null : callEditor("getIssues");
        if (issues) fields.issues = JSON.stringify(issues);
        if (webToggle.checked && !webLabel.hidden) fields.web = 1;
        lastError = null;
        pendingText = text;
        pendingFiles = files.map((item) => item.name);
        input.value = "";
        attachments = [];
        renderAttachments();
        currentRequest = request;
        controller = typeof AbortController === "function" ? new AbortController() : null;
        setBusy(true);
        render();
        watchProgress(request);
        post("send", fields, { files: files, signal: controller ? controller.signal : undefined })
            .then((data) => {
                messages = data.messages;
                if (data.changed) callEditor("applyAiDraft", data.draft);
            })
            .catch((error) => {
                if (error.name === "AbortError" || stopping) return;
                const data = error.data || {};
                if (data.changed) callEditor("applyAiDraft", data.draft);
                if (data.setup) {
                    load(true);
                    return;
                }
                if (!input.value) input.value = text;
                if (!attachments.length) {
                    attachments = files;
                    renderAttachments();
                }
                lastError = { message: error.message, details: data.details, link: data.link, retry: data.retry !== false && !files.length, text: text };
            })
            .finally(() => {
                if (stopping) return;
                currentRequest = "";
                clearInterval(progressTimer);
                pendingText = "";
                pendingFiles = [];
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
    document.addEventListener("surveyjs:ai-chat-ask", (event) => {
        const text = event.detail && event.detail.text;
        if (!text || busy) return;
        if (panel.hidden) setOpen(true);
        Promise.resolve(loading).then(() => {
            if (busy) return;
            if (composer.hidden) {
                input.value = text;
                return;
            }
            send(text);
        });
    });
    starters.forEach((start) => start.addEventListener("click", () => {
        intro = true;
        if (panel.hidden) setOpen(true);
        else render();
        setTimeout(() => input.focus(), 0);
    }));
    panel.addEventListener("keydown", (event) => {
        if (event.key === "Escape") setOpen(false);
    });
    input.addEventListener("input", updateCounter);
    fileInput.addEventListener("change", () => {
        addFiles(fileInput.files);
        fileInput.value = "";
    });
    input.addEventListener("paste", (event) => {
        const pasted = Array.from((event.clipboardData && event.clipboardData.files) || []);
        if (pasted.length && !attachButton.hidden) {
            event.preventDefault();
            addFiles(pasted);
        }
    });
    panel.addEventListener("dragover", (event) => {
        if (attachButton.hidden || busy || !event.dataTransfer || !Array.from(event.dataTransfer.types).includes("Files")) return;
        event.preventDefault();
        panel.classList.add("is-dropping");
    });
    panel.addEventListener("dragleave", (event) => {
        if (!panel.contains(event.relatedTarget)) panel.classList.remove("is-dropping");
    });
    panel.addEventListener("drop", (event) => {
        panel.classList.remove("is-dropping");
        if (attachButton.hidden || busy || !event.dataTransfer) return;
        event.preventDefault();
        addFiles(event.dataTransfer.files);
    });
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
                updateTools();
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
