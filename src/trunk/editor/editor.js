(function () {
    const LINT_DEBOUNCE_MS = 300;
    const SHOWN_ISSUES = 3;
    const PREVIEW_DEBOUNCE_MS = 400;
    const COPIED_LABEL_MS = 2000;
    const UI_BUILDER_CONNECT_TIMEOUT_MS = 15000;
    const UI_BUILDER_POLL_MS = 1000;
    const UI_BUILDER_PROTOCOL_VERSION = 2;
    const { __, _n, sprintf } = wp.i18n;

    const config = JSON.parse(document.getElementById("wp-sjs-editor-config").textContent);
    const nameInput = document.getElementById("wp-sjs-survey-name");
    const nameText = document.getElementById("wp-sjs-name-text");
    // The name is edited in place: the title button turns into the input and back.
    const nameEditButton = document.getElementById("wp-sjs-name-edit");
    const nameView = nameEditButton;
    const nameForm = nameInput;
    const formatButton = document.getElementById("wp-sjs-format");
    const insertImageButton = document.getElementById("wp-sjs-insert-image");
    const setLogoButton = document.getElementById("wp-sjs-set-logo");
    const mediaElement = document.getElementById("wp-sjs-media");
    const backgroundImageButton = document.getElementById("wp-sjs-bg-image");
    const backgroundImageRemove = document.getElementById("wp-sjs-bg-image-remove");
    const gdprLabel = document.getElementById("wp-sjs-gdpr-agreement-label");
    const gdprCheckbox = document.getElementById("wp-sjs-gdpr-agreement");
    const GDPR_NAME = "gdpr_agreement";
    const imageList = document.getElementById("wp-sjs-image-list");
    const saveButton = document.getElementById("wp-sjs-save");
    const uiBuilderButton = document.getElementById("wp-sjs-ui-builder");
    const uiBuilderOrigin = new URL(config.uiBuilderUrl).origin;
    const uiBuilderStatus = document.getElementById("wp-sjs-ui-builder-status");
    const uiBuilderCopyButton = document.getElementById("wp-sjs-ui-builder-copy");
    const noticeElement = document.getElementById("wp-sjs-editor-notice");
    const emptyHint = document.getElementById("wp-sjs-empty-hint");
    const emptyHintUiBuilderButton = document.getElementById("wp-sjs-empty-ui-builder");
    const monacoElement = document.getElementById("wp-sjs-monaco");
    const lintElement = document.getElementById("wp-sjs-lint");
    const previewElement = document.getElementById("wp-sjs-preview");
    const fullPreviewElement = document.getElementById("wp-sjs-full-preview-frame");
    const previewOrigin = new URL(config.previewUrl).origin;
    const fullPreviewButton = document.getElementById("wp-sjs-full-preview");
    const previewCloseButton = document.getElementById("wp-sjs-preview-close");
    const rootElement = document.querySelector(".wp-sjs-editor");
    const tabs = document.querySelectorAll(".wp-sjs-editor__tabs .nav-tab");
    const jsonPane = document.getElementById("wp-sjs-json-pane");
    const settingPanels = document.querySelectorAll(".wp-sjs-editor [data-panel]");
    const settingInputs = document.querySelectorAll(".wp-sjs-editor [data-setting]");
    const themeModeElement = document.getElementById("wp-sjs-theme-mode");
    const themeModeCheckbox = document.getElementById("wp-sjs-theme-mode-adaptive");
    const transparentBackgroundCheckbox = document.getElementById("wp-sjs-transparent-background");
    const markdownCheckbox = document.getElementById("wp-sjs-markdown");
    const themePresetSelect = document.getElementById("wp-sjs-theme-preset");
    const aiPromptButton = document.getElementById("wp-sjs-ai-prompt");
    const aiPasteButton = document.getElementById("wp-sjs-ai-paste");
    const aiConvertButton = document.getElementById("wp-sjs-ai-convert");
    const aiDialog = document.getElementById("wp-sjs-ai-dialog");
    const aiAnswerInput = document.getElementById("wp-sjs-ai-answer");
    const aiApplyButton = document.getElementById("wp-sjs-ai-apply");
    const aiCancelButton = document.getElementById("wp-sjs-ai-cancel");
    const aiNotice = document.getElementById("wp-sjs-ai-notice");
    const testEmailButton = document.getElementById("wp-sjs-send-test-email");
    const testEmailResult = document.getElementById("wp-sjs-test-email-result");
    const REPLY_TO_QUESTION ={ type: "text", title: __("Leave your email so we can get back to you", "surveyjs"), inputType: "email", placeholder: "john.doe@example.com" };
    const FILE_QUESTION = { type: "file", title: __("Attach files", "surveyjs"), allowMultiple: true };
    const emailPicker = (id) => ({
        id: id,
        baseName: "reply_to_email",
        template: REPLY_TO_QUESTION,
        matches: (node) => node.type === "text" && node.inputType === "email"
    });
    const filePicker = (id) => ({
        id: id,
        baseName: "attached_files",
        template: FILE_QUESTION,
        matches: (node) => node.type === "file"
    });
    const pickers = [
        emailPicker("wp-sjs-notification-reply-to"),
        filePicker("wp-sjs-notification-attachments"),
        filePicker("wp-sjs-auto-reply-attachments")
    ].map((picker) => Object.assign(picker, {
        select: document.getElementById(picker.id),
        addElement: document.getElementById(picker.id + "-add"),
        addCheckbox: document.getElementById(picker.id + "-add-question")
    }));

    const sources = {
        survey: JSON.stringify(config.json, null, 2),
        theme: JSON.stringify(config.theme, null, 2)
    };
    const saved = { name: config.name, survey: sources.survey, theme: sources.theme };
    const aiConfig = config.ai || {};
    let aiDraft = aiConfig.draft || null;
    let aiBusy = false;
    const aiNoticeElement = document.getElementById("wp-sjs-ai-draft-notice");
    let aiRevision = "";
    let knownAiRevision = aiDraft ? aiDraft.revision : "";
    let dismissedAiRevision = "";
    let pendingName = null;
    let aiBaseline = null;
    let leavingOnPurpose = false;
    if (aiDraft) {
        sources.survey = JSON.stringify(aiDraft.json, null, 2);
        sources.theme = JSON.stringify(aiDraft.theme, null, 2);
        aiRevision = aiDraft.revision;
        if (aiDraft.name !== config.name) pendingName = aiDraft.name;
    }
    const rules = SurveyLinter.getRules();

    let monaco = null;
    let editor = null;
    let models = null;
    let decorations = null;
    let activeTab = "survey";
    let analysis = { kind: "waiting" };
    let markers = [];
    let selectedPath = null;
    let expanded = false;
    let autoExpanded = false;
    let lintTimer = null;
    let lintOptions;
    let previewTimer = null;
    let saveSnackbar = null;
    let previewFocusName = null;
    let previewShowInvisibleElements = false;
    const saveChecks = [];
    let previewReady = false;
    let fullPreviewReady = false;
    let fullPreviewOpen = false;
    let fullPreviewReturnFocus = null;
    let siteData = null;
    let adaptiveTheme = null;
    let themeTokens = null;
    let contrastCheckPending = false;
    let uiBuilderWindow = null;
    let uiBuilderConnectTimer = null;
    let uiBuilderPollTimer = null;
    let uiBuilderConnected = false;

    nameText.textContent = pendingName ?? config.name;
    document.querySelectorAll("[data-sjs-form-name]").forEach((node) => { node.textContent = nameText.textContent; });

    function fillSettings(values) {
        settingInputs.forEach((input) => {
            const value = values[input.dataset.setting];
            if (input.type === "checkbox") {
                input.checked = "checkedValue" in input.dataset ? value === input.dataset.checkedValue : Boolean(value);
            } else if (pickers.some((picker) => picker.select === input)) {
                renderPickerOptions(pickers.find((picker) => picker.select === input), value);
            } else if (input.dataset.settingType === "json") {
                input.value = JSON.stringify(value ?? null);
            } else {
                input.value = value;
                input.placeholder = config.settingPlaceholders[input.dataset.setting] ?? "";
            }
        });
        document.dispatchEvent(new CustomEvent("surveyjs:editor-settings-filled"));
    }
    fillSettings(config.settings);
    saved.settings = JSON.stringify(readSettings());
    if (aiDraft) {
        fillSettings(aiDraft.settings);
        aiBaseline = { survey: sources.survey, theme: sources.theme, settings: JSON.stringify(readSettings()) };
    }

    document.querySelectorAll("input[data-hint]").forEach((checkbox) => {
        const hint = document.getElementById(checkbox.dataset.hint);
        const updateHint = () => { hint.hidden = !checkbox.checked; };
        checkbox.addEventListener("change", updateHint);
        updateHint();
    });

    const dependentRows = document.querySelectorAll("[data-enabled-by]");
    const rowToggles = (row) => row.dataset.enabledBy.split(" ").map((setting) => document.querySelector(`input[data-setting="${setting}"]`));
    function updateDependentRows() {
        dependentRows.forEach((row) => {
            const enabled = rowToggles(row).some((toggle) => toggle.checked);
            row.hidden = !enabled;
            row.querySelectorAll("input, select, textarea, button").forEach((control) => { control.disabled = !enabled; });
        });
        valueDependents.forEach((element) => {
            const [setting, value] = element.dataset.enabledIf.split("=");
            const enabled = settingInput(setting).value === value;
            element.hidden = !enabled;
            element.querySelectorAll("input, select, textarea, button").forEach((control) => { control.disabled = !enabled; });
        });
        const jsonInvalid = Boolean(parse(sources.survey).error);
        pickers.forEach((picker) => { picker.addCheckbox.disabled ||= jsonInvalid; });
        restrictionsStoreNotice.hidden = settingInput("storeEntries").checked || !(settingInput("limitEntries").checked || settingInput("limitPerRespondent").checked);
    }
    const valueDependents = document.querySelectorAll("[data-enabled-if]");
    const restrictionsStoreNotice = document.getElementById("wp-sjs-restrictions-store-notice");
    function settingInput(setting) {
        return document.querySelector(`[data-setting="${setting}"]`);
    }
    dependentRows.forEach((row) => {
        rowToggles(row).forEach((toggle) => toggle.addEventListener("change", updateDependentRows));
    });
    valueDependents.forEach((element) => settingInput(element.dataset.enabledIf.split("=")[0]).addEventListener("change", updateDependentRows));
    ["storeEntries", "limitEntries", "limitPerRespondent"].forEach((setting) => settingInput(setting).addEventListener("change", updateDependentRows));
    updateDependentRows();

    function checkboxValue(input) {
        if (!("checkedValue" in input.dataset)) return input.checked;
        return input.checked ? input.dataset.checkedValue : input.dataset.uncheckedValue;
    }

    function readSettings() {
        const settings = {};
        settingInputs.forEach((input) => {
            settings[input.dataset.setting] = input.type === "checkbox" ? checkboxValue(input) : input.dataset.settingType === "json" ? JSON.parse(input.value || "null") : input.value;
        });
        return settings;
    }

    function findQuestions(node, picker, found) {
        if (Array.isArray(node)) {
            node.forEach((item) => findQuestions(item, picker, found));
        } else if (node && typeof node === "object") {
            if (picker.matches(node) && typeof node.name === "string") {
                found.push(node);
            }
            Object.values(node).forEach((item) => findQuestions(item, picker, found));
        }
        return found;
    }

    function isAddedQuestion(picker, question) {
        return new RegExp(`^${picker.baseName}\\d*$`).test(question.name)
            && Object.keys(question).length === Object.keys(picker.template).length + 1
            && Object.keys(picker.template).every((key) => question[key] === picker.template[key]);
    }

    function renderPickerOptions(picker, selected) {
        const json = parse(sources.survey).json;
        if (!json) return;
        const questions = findQuestions(json, picker, []);
        const added = questions.some((question) => isAddedQuestion(picker, question));
        picker.addElement.hidden = questions.length > 0 && !added;
        picker.addCheckbox.checked = added;
        const names = questions.map((question) => question.name);
        picker.select.replaceChildren(...[""].concat(names).map((name) => {
            const node = element("option", "", name || "— None —");
            node.value = name;
            return node;
        }));
        picker.select.value = names.includes(selected) ? selected : "";
    }

    function renderAllPickerOptions() {
        pickers.forEach((picker) => renderPickerOptions(picker, picker.select.value));
    }

    function formQuestionNames(json) {
        const names = [];
        const collect = (elements) => (Array.isArray(elements) ? elements : []).forEach((item) => {
            if (!item || typeof item !== "object") return;
            if (!item.type || item.type === "panel") return collect(item.elements);
            if (["html", "image"].includes(item.type) || typeof item.name !== "string" || !item.name) return;
            names.push(item.name);
        });
        collect(json.pages);
        collect(json.elements);
        return names;
    }

    function renderFieldTags() {
        const json = parse(sources.survey).json;
        if (!json) return;
        const names = formQuestionNames(json);
        document.querySelectorAll(".wp-sjs-field-tags").forEach((target) => {
            target.hidden = names.length === 0;
            target.replaceChildren(__("Tags of this form:", "surveyjs") + " ", ...names.flatMap((name, index) => (index ? [" "] : []).concat(element("code", "", "{field:" + name + "}"))));
        });
    }

    function addQuestion(json, picker) {
        const names = new Set();
        const collectNames = (node) => {
            if (Array.isArray(node)) return node.forEach(collectNames);
            if (!node || typeof node !== "object") return;
            if (typeof node.name === "string") names.add(node.name);
            Object.values(node).forEach(collectNames);
        };
        collectNames(json);
        let name = picker.baseName;
        for (let index = 2; names.has(name); index++) {
            name = picker.baseName + index;
        }
        appendElement(json, Object.assign({ name: name }, picker.template));
        return name;
    }

    function appendElement(json, item) {
        const container = Array.isArray(json.pages) && json.pages.length > 0 ? json.pages[json.pages.length - 1] : json;
        if (!Array.isArray(container.elements)) {
            container.elements = [];
        }
        container.elements.push(item);
    }

    function removeAddedQuestions(node, picker) {
        if (Array.isArray(node)) return node.forEach((item) => removeAddedQuestions(item, picker));
        if (!node || typeof node !== "object") return;
        if (Array.isArray(node.elements)) {
            node.elements = node.elements.filter((item) => !(item && typeof item === "object" && isAddedQuestion(picker, item)));
        }
        Object.values(node).forEach((item) => removeAddedQuestions(item, picker));
    }

    function togglePickerQuestion(picker) {
        const json = parse(sources.survey).json;
        if (!json) return;
        const selected = picker.addCheckbox.checked ? addQuestion(json, picker) : "";
        if (!picker.addCheckbox.checked) {
            removeAddedQuestions(json, picker);
        }
        previewFocusName = selected || null;
        replaceSource("survey", json);
        if (!models) {
            renderPreview();
            runLint();
        }
        pickers.forEach((item) => renderPickerOptions(item, item === picker ? selected : item.select.value));
        renderFieldTags();
        closeSaveSnackbar();
        updateState();
    }

    function parse(source) {
        try {
            const parsed = JSON.parse(source);
            if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
                return { error: __("The JSON must be an object.", "surveyjs") };
            }
            return { json: parsed };
        } catch (error) {
            return { error: error.message };
        }
    }

    function buildPathIndex(text) {
        const index = new Map();
        let i = 0;
        let line = 1;
        let lineStart = 0;

        const positionAt = (offset) => ({ line: line, column: offset - lineStart + 1 });
        const fail = () => {
            i = text.length;
        };
        const skipWhitespace = () => {
            while (i < text.length) {
                const ch = text[i];
                if (ch === "\n") {
                    i++;
                    line++;
                    lineStart = i;
                } else if (ch === " " || ch === "\t" || ch === "\r") {
                    i++;
                } else {
                    break;
                }
            }
        };
        const escapes = { "\"": "\"", "\\": "\\", "/": "/", b: "\b", f: "\f", n: "\n", r: "\r", t: "\t" };
        const readString = () => {
            if (text[i] !== "\"") return null;
            i++;
            let out = "";
            while (i < text.length) {
                const ch = text[i];
                if (ch === "\\") {
                    const next = text[i + 1];
                    if (next === "u") {
                        out += String.fromCharCode(parseInt(text.slice(i + 2, i + 6), 16));
                        i += 6;
                    } else {
                        out += escapes[next] ?? next;
                        i += 2;
                    }
                } else if (ch === "\"") {
                    i++;
                    return out;
                } else {
                    if (ch === "\n") {
                        line++;
                        lineStart = i + 1;
                    }
                    out += ch;
                    i++;
                }
            }
            return null;
        };
        const skipPrimitive = () => {
            while (i < text.length && !",}] \t\r\n".includes(text[i])) i++;
        };
        const readValue = (path) => {
            skipWhitespace();
            const ch = text[i];
            if (ch === "{") {
                i++;
                readMembers(path);
            } else if (ch === "[") {
                i++;
                readItems(path);
            } else if (ch === "\"") {
                readString();
            } else if (ch === undefined) {
                fail();
            } else {
                skipPrimitive();
            }
        };
        const readMembers = (path) => {
            for (;;) {
                skipWhitespace();
                if (i >= text.length) return fail();
                if (text[i] === "}") {
                    i++;
                    return;
                }
                if (text[i] === ",") {
                    i++;
                    continue;
                }
                const keyPosition = positionAt(i);
                const key = readString();
                if (key === null) return fail();
                const childPath = path ? path + "." + key : key;
                index.set(childPath, keyPosition);
                skipWhitespace();
                if (text[i] !== ":") return fail();
                i++;
                readValue(childPath);
            }
        };
        const readItems = (path) => {
            let itemIndex = 0;
            for (;;) {
                skipWhitespace();
                if (i >= text.length) return fail();
                if (text[i] === "]") {
                    i++;
                    return;
                }
                if (text[i] === ",") {
                    i++;
                    continue;
                }
                const childPath = path + "[" + itemIndex + "]";
                index.set(childPath, positionAt(i));
                readValue(childPath);
                itemIndex++;
            }
        };

        readValue("");
        return index;
    }

    function locatePath(index, path) {
        let candidate = path;
        for (;;) {
            const hit = index.get(candidate);
            if (hit) return hit;
            const cut = Math.max(candidate.lastIndexOf("."), candidate.lastIndexOf("["));
            if (cut <= 0) return null;
            candidate = candidate.slice(0, cut);
        }
    }

    function countElements(json) {
        let total = 0;
        const walk = (node) => {
            if (Array.isArray(node)) return node.forEach(walk);
            if (!node || typeof node !== "object") return;
            if (typeof node.name === "string") total++;
            Object.values(node).forEach(walk);
        };
        walk(json.pages ?? json);
        return total;
    }

    function failureCase(finding) {
        const reproduction = finding.reproduction;
        if (reproduction) {
            const values = reproduction.steps.flatMap((step) =>
                "set" in step ? Object.entries(step.set).map(([name, value]) => name + " = " + JSON.stringify(value)) : []
            );
            const trigger = values.length ? sprintf(__("Answer %s", "surveyjs"), values.join(", ")) + " — " : "";
            return (trigger + (reproduction.description ?? "")).trim();
        }
        if (finding.suggestion) {
            return sprintf(__("Nothing resolves this reference. Closest known name: \"%s\".", "surveyjs"), finding.suggestion);
        }
        if (finding.related?.length) {
            return sprintf(__("Also involves %s.", "surveyjs"), finding.related.map((item) => item.elementName ?? item.path).join(", "));
        }
        return null;
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function icon(name, className) {
        return element("span", "dashicons dashicons-" + name + (className ? " " + className : ""));
    }

    function renderNotice(container, type, message, issues) {
        container.querySelectorAll(".wp-sjs-editor__message").forEach((notice) => notice.remove());
        if (!message) return;
        const notice = element("div", "notice notice-" + type + " inline wp-sjs-editor__message");
        notice.append(element("p", "", message));
        if (issues) notice.append(...issueDetails(issues));
        container.prepend(notice);
    }

    function showNotice(type, message, issues) {
        renderNotice(noticeElement, type, message, issues);
    }

    // Results of Save, rename and the changes the editor made by itself (GDPR agreement, JSON from the UI Builder or
    // the AI) are snackbars (admin.js). One at a time: a new one, or any change of the form, closes the last one.
    function notify(type, message, issues) {
        closeSaveSnackbar();
        if (!window.surveyjsSnackbar) {
            showNotice(type, message, issues);
            return;
        }
        saveSnackbar = window.surveyjsSnackbar.show({ type: type, message: message, actions: issues ? issueActions(issues) : [] });
    }

    function closeSaveSnackbar() {
        if (saveSnackbar) saveSnackbar.close();
        saveSnackbar = null;
    }

    function issueActions(issues) {
        const actions = [{ label: __("Review and fix", "surveyjs"), onClick: () => reviewIssues(issues[0].path) }];
        if (hasFormChat()) {
            actions.push({ label: __("Fix with AI", "surveyjs"), onClick: () => {
                document.dispatchEvent(new CustomEvent("surveyjs:ai-chat-ask", { detail: { text: __("Fix the JSON issues the form editor shows.", "surveyjs") } }));
            } });
        }
        return actions;
    }

    function lint(json) {
        return SurveyLinter.lintSurvey(json, lintOptions);
    }

    function currentIssues() {
        const json = parse(sources.survey).json;
        if (!json) return null;
        return lint(json).findings.map((finding) => ({ ruleId: finding.ruleId, severity: finding.severity, message: finding.message, path: finding.path }));
    }

    function hasFormChat() {
        return Boolean(window.surveyjsAiChat && window.surveyjsAiChat.mode === "form");
    }

    function issueDetails(issues) {
        const list = element("ul", "wp-sjs-editor__issues");
        issues.slice(0, SHOWN_ISSUES).forEach((issue) => list.append(element("li", "", issue.message)));
        const parts = [list];
        if (issues.length > SHOWN_ISSUES) {
            parts.push(element("p", "description", sprintf(_n("And %d more.", "And %d more.", issues.length - SHOWN_ISSUES, "surveyjs"), issues.length - SHOWN_ISSUES)));
        }
        const actions = element("p", "wp-sjs-editor__issue-actions");
        actions.append(aiButton(__("Review and fix", "surveyjs"), "button-secondary", () => reviewIssues(issues[0].path)));
        if (hasFormChat()) {
            actions.append(" ", aiButton(__("Fix with AI", "surveyjs"), "", () => {
                document.dispatchEvent(new CustomEvent("surveyjs:ai-chat-ask", { detail: { text: __("Fix the JSON issues the form editor shows.", "surveyjs") } }));
            }));
        }
        parts.push(actions);
        return parts;
    }

    // Survey Creator with Pro replaces the Config tab and shows the issues in its own JSON Editor tab, so it may take over.
    function reviewIssues(path) {
        if (!document.dispatchEvent(new CustomEvent("surveyjs:editor-review-issues", { cancelable: true, detail: { path: path } }))) return;
        if (!editor || !tabNames.includes("survey")) return;
        runLint();
        selectedPath = path;
        expanded = true;
        const located = analysis.kind === "done" ? analysis.located.find((item) => item.finding.path === path) : null;
        if (located && located.line !== null) {
            revealLine(located.line);
        } else if (activeTab !== "survey") {
            switchTab("survey");
        }
        renderMarkers();
        renderLint();
        if ((!located || located.line === null) && lintElement) lintElement.scrollIntoView({ block: "center" });
    }

    function syntaxError() {
        const surveyError = parse(sources.survey).error;
        if (surveyError) return "Config: " + surveyError;
        const themeError = parse(sources.theme).error;
        if (themeError) return "Theme: " + themeError;
        return null;
    }

    function isDirty() {
        return (!nameForm.hidden && nameInput.value.trim() !== saved.name) || (pendingName !== null && pendingName !== saved.name) || sources.survey !== saved.survey || sources.theme !== saved.theme || JSON.stringify(readSettings()) !== saved.settings;
    }

    function hasOwnUnsavedChanges() {
        if (!isDirty()) return false;
        if (!aiRevision || !aiBaseline) return true;
        return (!nameForm.hidden && nameInput.value.trim() !== (pendingName ?? saved.name)) || sources.survey !== aiBaseline.survey || sources.theme !== aiBaseline.theme || JSON.stringify(readSettings()) !== aiBaseline.settings;
    }

    function updateState() {
        const error = syntaxError();
        saveButton.disabled = Boolean(error);
        uiBuilderButton.disabled = Boolean(error);
        updateDependentRows();
        formatButton.disabled = !(activeTab in sources) || isThemeJsonLocked() || Boolean(parse(sources[activeTab]).error);
        insertImageButton.disabled = !hasMediaLibrary();
        // Get image URL is for the form JSON (Image questions); the Theme tab sets its image with Background image.
        mediaElement.hidden = activeTab !== "survey";
        const theme = sources.theme.trim() ? parse(sources.theme) : { json: {} };
        backgroundImageButton.disabled = !editor || Boolean(theme.error) || !hasMediaLibrary();
        backgroundImageRemove.hidden = !(theme.json && theme.json.backgroundImage);
        const survey = parse(sources.survey);
        if (setLogoButton) {
            setLogoButton.hidden = activeTab !== "survey";
            setLogoButton.disabled = !editor || Boolean(survey.error) || !hasMediaLibrary();
        }
        if (gdprLabel) {
            gdprLabel.hidden = activeTab !== "survey";
            gdprCheckbox.disabled = Boolean(survey.error);
            if (survey.json) gdprCheckbox.checked = hasGdprAgreement(survey.json);
        }
        updateEmptyHint(survey.json);
        if (error) {
            showNotice("error", error);
        } else {
            showNotice();
        }
    }

    function hasElements(json) {
        const containers = [json].concat(Array.isArray(json.pages) ? json.pages : []);
        return containers.some((container) => isJsonObject(container)
            && ["elements", "questions"].some((key) => Array.isArray(container[key]) && container[key].length > 0));
    }

    function updateEmptyHint(json) {
        if (!emptyHint) return;
        const empty = Boolean(json) && !hasElements(json);
        emptyHint.hidden = !empty;
        rootElement.classList.toggle("wp-sjs-editor--empty", empty);
    }

    function runLint() {
        const parsed = parse(sources.survey);
        if (!parsed.json) {
            analysis = { kind: "waiting" };
        } else {
            const startedAt = performance.now();
            const result = lint(parsed.json);
            const durationMs = performance.now() - startedAt;
            const index = buildPathIndex(sources.survey);
            analysis = {
                kind: "done",
                result: result,
                located: result.findings.map((finding) => ({ finding: finding, line: locatePath(index, finding.path)?.line ?? null })),
                elementCount: countElements(parsed.json),
                durationMs: durationMs
            };
        }
        markers = analysis.kind === "done"
            ? analysis.located.filter((item) => item.line !== null).map((item) => ({
                path: item.finding.path,
                line: item.line,
                severity: item.finding.severity,
                message: item.finding.ruleId + ": " + item.finding.message
            }))
            : [];
        if (analysis.kind === "done" && analysis.result.findings.length > 0 && !autoExpanded) {
            autoExpanded = true;
            expanded = true;
        }
        renderMarkers();
        renderLint();
    }

    function renderMarkers() {
        if (!decorations) return;
        if (activeTab !== "survey") {
            decorations.set([]);
            return;
        }
        const highlightLine = markers.find((marker) => marker.path === selectedPath)?.line ?? null;
        decorations.set(markers.map((marker) => ({
            range: new monaco.Range(marker.line, 1, marker.line, 1),
            options: {
                isWholeLine: true,
                glyphMarginClassName: "wp-sjs-lint-glyph wp-sjs-lint-glyph-" + marker.severity,
                glyphMarginHoverMessage: { value: marker.message },
                linesDecorationsClassName: "wp-sjs-lint-line wp-sjs-lint-line-" + marker.severity,
                className: highlightLine === marker.line ? "wp-sjs-lint-line-highlight" : undefined
            }
        })));
    }

    function scrollToLine(line, center) {
        const lineTop = monacoElement.getBoundingClientRect().top + editor.getTopForLineNumber(line);
        const lineHeight = editor.getOption(monaco.editor.EditorOption.lineHeight);
        const viewTop = document.getElementById("wpadminbar")?.offsetHeight ?? 0;
        const viewBottom = window.innerHeight - (lintElement ? lintElement.offsetHeight : 0);
        if (center) {
            window.scrollBy(0, lineTop - (viewTop + viewBottom - lineHeight) / 2);
        } else if (lineTop < viewTop) {
            window.scrollBy(0, lineTop - viewTop);
        } else if (lineTop + lineHeight > viewBottom) {
            window.scrollBy(0, lineTop + lineHeight - viewBottom);
        }
    }

    function fitHeight() {
        monacoElement.style.height = editor.getContentHeight() + "px";
        editor.layout();
    }

    function pinFindWidget() {
        const findWidget = monacoElement.querySelector(".find-widget");
        if (!findWidget) return;
        const viewTop = document.getElementById("wpadminbar")?.offsetHeight ?? 0;
        const offset = Math.max(0, viewTop - monacoElement.getBoundingClientRect().top);
        findWidget.style.marginTop = Math.min(offset, Math.max(0, monacoElement.offsetHeight - findWidget.offsetHeight)) + "px";
    }

    function revealLine(line) {
        if (activeTab !== "survey") switchTab("survey");
        editor.setPosition({ lineNumber: line, column: 1 });
        scrollToLine(line, true);
        editor.focus({ preventScroll: true });
    }

    function renderLint() {
        if (!lintElement) return;
        const waiting = analysis.kind === "waiting";
        const issueCount = waiting ? 0 : analysis.result.findings.length;
        const failed = issueCount > 0;

        const bar = element("div", "wp-sjs-lint__bar");
        bar.append(icon(waiting ? "marker" : failed ? "warning" : "yes", failed ? "wp-sjs-lint__icon-warning" : ""));
        bar.append(element("span", "wp-sjs-lint__summary", waiting
            ? __("Static analysis: waiting for valid JSON", "surveyjs")
            : failed ? sprintf(_n("Static analysis: %d issue", "Static analysis: %d issues", issueCount, "surveyjs"), issueCount) : __("Static analysis: all checks passed", "surveyjs")));
        if (!waiting) {
            bar.append(element("span", "wp-sjs-lint__meta", sprintf(_n("%1$d check · %2$d ms", "%1$d checks · %2$d ms", rules.length, "surveyjs"), rules.length, Math.round(analysis.durationMs))));
        }
        const toggle = element("button", "button-link wp-sjs-lint__toggle");
        toggle.type = "button";
        toggle.setAttribute("aria-expanded", String(expanded));
        toggle.setAttribute("aria-controls", "wp-sjs-lint-details");
        toggle.append(document.createTextNode(failed ? __("View issues", "surveyjs") : __("View checks", "surveyjs")), icon(expanded ? "arrow-up-alt2" : "arrow-down-alt2"));
        toggle.addEventListener("click", () => {
            expanded = !expanded;
            renderLint();
        });
        bar.append(toggle);

        const children = [bar];
        if (expanded) {
            const details = element("div", "wp-sjs-lint__details");
            details.id = "wp-sjs-lint-details";
            details.append(element("h3", "wp-sjs-lint__heading", __("Checks performed", "surveyjs")));
            details.append(element("p", "description", waiting
                ? __("The form JSON does not parse yet, so no check has run.", "surveyjs")
                : sprintf(_n("%1$d checks over %2$d named element.", "%1$d checks over %2$d named elements.", analysis.elementCount, "surveyjs"), rules.length, analysis.elementCount)));

            const findingsByRule = new Map();
            if (!waiting) {
                analysis.located.forEach((item) => {
                    const list = findingsByRule.get(item.finding.ruleId);
                    if (list) list.push(item);
                    else findingsByRule.set(item.finding.ruleId, [item]);
                });
            }
            rules.filter((rule) => findingsByRule.has(rule.id)).forEach((rule) => {
                const items = findingsByRule.get(rule.id);
                const group = element("div", "wp-sjs-lint__rule");
                const title = element("p", "wp-sjs-lint__rule-title");
                title.append(icon("warning", "wp-sjs-lint__severity-" + items[0].finding.severity), element("code", "", rule.id), element("span", "description", sprintf(_n("%d finding", "%d findings", items.length, "surveyjs"), items.length)));
                group.append(title);
                const list = element("ul", "wp-sjs-lint__findings");
                items.forEach((item) => {
                    const button = element("button", "wp-sjs-lint__finding" + (selectedPath === item.finding.path ? " is-selected" : ""));
                    button.type = "button";
                    button.append(element("span", "wp-sjs-lint__message", item.finding.message));
                    const failure = failureCase(item.finding);
                    if (failure) button.append(element("span", "description", failure));
                    button.append(element("code", "wp-sjs-lint__path", item.finding.path + (item.line !== null ? " · line " + item.line : "")));
                    button.addEventListener("click", () => {
                        selectedPath = item.finding.path;
                        renderMarkers();
                        renderLint();
                        if (item.line !== null) revealLine(item.line);
                    });
                    const listItem = element("li");
                    listItem.append(button);
                    list.append(listItem);
                });
                group.append(list);
                details.append(group);
            });

            if (!waiting) {
                const passing = element("ul", "wp-sjs-lint__passing");
                rules.filter((rule) => !findingsByRule.has(rule.id)).forEach((rule) => {
                    const listItem = element("li");
                    listItem.append(icon("yes"), element("code", "", rule.id));
                    passing.append(listItem);
                });
                details.append(passing);
            }
            children.push(details);
        }
        lintElement.replaceChildren(...children);
    }

    function renderPreview() {
        if (!previewReady && !fullPreviewReady) return;
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) return;
        const focusName = previewFocusName;
        previewFocusName = null;
        const message = {
            type: "surveyjs-preview-render",
            json: survey.json,
            theme: theme.json,
            themeMode: checkboxValue(themeModeCheckbox),
            transparentBackground: transparentBackgroundCheckbox.checked,
            markdown: markdownCheckbox.checked,
            checkContrast: false,
            focusName: focusName,
            showInvisibleElements: previewShowInvisibleElements
        };
        if (fullPreviewReady) fullPreviewElement.contentWindow.postMessage(message, previewOrigin);
        if (previewReady) {
            previewElement.contentWindow.postMessage(Object.assign({}, message, { checkContrast: contrastCheckPending }), previewOrigin);
            contrastCheckPending = false;
        }
    }

    function onPreviewMessage(event) {
        if (event.origin !== previewOrigin) return;
        const fromFullPreview = event.source === fullPreviewElement.contentWindow;
        if (!fromFullPreview && event.source !== previewElement.contentWindow) return;
        if (fromFullPreview && event.data && event.data.type === "surveyjs-preview-ready") {
            fullPreviewReady = true;
            rootElement.classList.remove("wp-sjs-editor--preview-loading");
            renderPreview();
        } else if (event.data && event.data.type === "surveyjs-preview-ready") {
            previewReady = true;
            siteData = event.data.site || null;
            adaptiveTheme = event.data.adaptiveTheme || null;
            aiPromptButton.disabled = !siteData;
            updateThemePreset();
            renderPreview();
        } else if (event.data && event.data.type === "surveyjs-preview-contrast" && event.data.issues.length > 0) {
            const issues = event.data.issues.map((issue) => issue.element + " " + issue.ratio + ":1");
            aiMessage("warning", __("Some text may be hard to read (contrast below 4.5:1):", "surveyjs"), issues, true);
        } else if (event.data && event.data.type === "surveyjs-preview-escape") {
            setFullPreview(false);
        }
    }

    function setFullPreview(open) {
        if (open === fullPreviewOpen) return;
        fullPreviewOpen = open;
        if (open) fullPreviewReturnFocus = document.activeElement;
        rootElement.classList.toggle("wp-sjs-editor--full-preview", open);
        document.body.classList.toggle("wp-sjs-editor-full-preview-open", open);
        fullPreviewButton.setAttribute("aria-expanded", String(open));
        previewCloseButton.hidden = !open;
        document.dispatchEvent(new CustomEvent("surveyjs:editor-full-preview", { detail: { open: open } }));
        if (open && !fullPreviewElement.getAttribute("src")) {
            rootElement.classList.add("wp-sjs-editor--preview-loading");
            fullPreviewElement.src = config.fullPreviewUrl;
        }
        if (open) {
            previewCloseButton.focus();
        } else {
            const returnFocus = fullPreviewReturnFocus && fullPreviewReturnFocus.isConnected && fullPreviewReturnFocus !== document.body ? fullPreviewReturnFocus : fullPreviewButton;
            fullPreviewReturnFocus = null;
            returnFocus.focus();
        }
    }

    function onSourceChange(tab) {
        sources[tab] = models[tab].getValue(monaco.editor.EndOfLinePreference.LF);
        if (tab === "theme") renderThemePreset();
        closeSaveSnackbar();
        updateState();
        if (tab === "survey") {
            clearTimeout(lintTimer);
            lintTimer = setTimeout(runLint, LINT_DEBOUNCE_MS);
        }
        clearTimeout(previewTimer);
        previewTimer = setTimeout(renderPreview, PREVIEW_DEBOUNCE_MS);
    }

    function switchTab(tab) {
        activeTab = tab;
        rootElement.dataset.activeTab = tab;
        tabs.forEach((item) => item.classList.toggle("nav-tab-active", item.dataset.tab === tab));
        const isJsonTab = tab in sources;
        jsonPane.hidden = !isJsonTab;
        themeModeElement.hidden = tab !== "theme";
        updateThemeJsonLock();
        settingPanels.forEach((panel) => {
            panel.hidden = panel.dataset.panel !== tab;
        });
        if (!isJsonTab) {
            renderAllPickerOptions();
            renderFieldTags();
            updateState();
        } else {
            editor.setModel(models[tab]);
            fitHeight();
            renderMarkers();
            updateState();
        }
        document.dispatchEvent(new CustomEvent("surveyjs:editor-tab", { detail: { tab: tab } }));
    }

    function setSource(tab, json) {
        if (!isJsonObject(json)) return false;
        replaceSource(tab, json);
        if (!models) {
            if (tab === "theme") renderThemePreset();
            closeSaveSnackbar();
            updateState();
            renderPreview();
            runLint();
        }
        return true;
    }

    function format() {
        const parsed = parse(sources[activeTab]);
        if (parsed.json) {
            editor.executeEdits("format", [{ range: models[activeTab].getFullModelRange(), text: JSON.stringify(parsed.json, null, 2) }]);
        }
    }

    function hasMediaLibrary() {
        return Boolean(window.wp && window.wp.media);
    }

    function chooseImages(button, multiple, onSelect) {
        const frame = window.wp.media({
            title: button.dataset.frameTitle,
            button: { text: button.dataset.frameButton },
            library: { type: "image" },
            multiple: multiple
        });
        frame.$el.addClass("wp-sjs-media-frame");
        frame.on("select", () => {
            const attachments = frame.state().get("selection").toJSON().filter((attachment) => attachment.url);
            if (attachments.length > 0) onSelect(attachments);
        });
        frame.open();
    }

    function imageListItem(attachment) {
        const labels = imageList.dataset;
        const item = element("li", "wp-sjs-editor__image");
        item.dataset.url = attachment.url;
        const thumbnail = element("img");
        thumbnail.src = attachment.sizes?.thumbnail?.url || attachment.url;
        thumbnail.alt = "";
        const fileName = attachment.filename || attachment.url.split("/").pop();
        const nameElement = element("span", "wp-sjs-editor__image-name", fileName);
        nameElement.title = fileName;
        const urlInput = element("input", "code");
        urlInput.type = "text";
        urlInput.readOnly = true;
        urlInput.value = attachment.url;
        urlInput.setAttribute("aria-label", labels.urlLabel + ": " + fileName);
        urlInput.addEventListener("focus", () => urlInput.select());
        const copyButton = element("button", "button button-small", labels.copyLabel);
        copyButton.type = "button";
        copyButton.addEventListener("click", () => {
            copyText(attachment.url)
                .then(() => showCopied(copyButton, labels.copiedLabel))
                .catch(() => urlInput.select());
        });
        const removeButton = element("button", "button-link wp-sjs-editor__image-remove");
        removeButton.type = "button";
        removeButton.setAttribute("aria-label", labels.removeLabel + ": " + fileName);
        const removeIcon = element("span", "dashicons dashicons-no-alt");
        removeIcon.setAttribute("aria-hidden", "true");
        removeButton.append(removeIcon);
        removeButton.addEventListener("click", () => {
            item.remove();
            imageList.hidden = imageList.children.length === 0;
            insertImageButton.focus();
        });
        item.append(thumbnail, nameElement, urlInput, copyButton, removeButton);
        return item;
    }

    function insertImage() {
        chooseImages(insertImageButton, true, (attachments) => {
            const listed = new Set(Array.from(imageList.children, (item) => item.dataset.url));
            attachments.filter((attachment) => !listed.has(attachment.url)).forEach((attachment) => {
                listed.add(attachment.url);
                imageList.append(imageListItem(attachment));
            });
            imageList.hidden = imageList.children.length === 0;
        });
    }

    function setLogo() {
        chooseImages(setLogoButton, false, (attachments) => {
            const parsed = parse(sources.survey);
            if (!parsed.json) return;
            parsed.json.logo = attachments[0].url;
            replaceSource("survey", parsed.json);
        });
    }

    // Background image (Theme tab, Adaptive off): writes the chosen image to "backgroundImage" of the theme JSON;
    // Remove takes it out.
    function setBackgroundImage(url) {
        const parsed = sources.theme.trim() ? parse(sources.theme) : { json: {} };
        if (!isJsonObject(parsed.json)) return;
        if (url) parsed.json.backgroundImage = url;
        else delete parsed.json.backgroundImage;
        useTheme(parsed.json);
    }

    function chooseBackgroundImage() {
        chooseImages(backgroundImageButton, false, (attachments) => setBackgroundImage(attachments[0].url));
    }

    function elementLists(node, lists = []) {
        if (Array.isArray(node)) {
            node.forEach((item) => elementLists(item, lists));
        } else if (isJsonObject(node)) {
            ["elements", "templateElements"].forEach((key) => {
                if (Array.isArray(node[key])) lists.push(node[key]);
            });
            Object.values(node).forEach((item) => elementLists(item, lists));
        }
        return lists;
    }

    function isGdprAgreement(item) {
        return isJsonObject(item) && item.name === GDPR_NAME;
    }

    function hasGdprAgreement(json) {
        return elementLists(json).some((list) => list.some(isGdprAgreement));
    }

    function toggleGdprAgreement() {
        const json = parse(sources.survey).json;
        if (!json) return;
        const add = gdprCheckbox.checked;
        if (add === hasGdprAgreement(json)) return;
        if (add) {
            // A required boolean also accepts false (checked, then unchecked), so the validator demands true.
            appendElement(json, { name: GDPR_NAME, type: "boolean", displayMode: "checkbox", title: config.gdpr.text, isRequired: true, validators: [{ type: "expression", expression: `{${GDPR_NAME}} = true`, text: config.gdpr.error }] });
            previewFocusName = GDPR_NAME;
        } else {
            elementLists(json).forEach((list) => {
                for (let index = list.length - 1; index >= 0; index--) {
                    if (isGdprAgreement(list[index])) list.splice(index, 1);
                }
            });
        }
        replaceSource("survey", json);
        if (!models) {
            renderPreview();
            runLint();
        }
        renderAllPickerOptions();
        renderFieldTags();
        updateState();
        notify("info", add
            ? __("The GDPR agreement was added at the end of the form. Press Save to keep it.", "surveyjs")
            : __("The GDPR agreement was removed from the form. Press Save to keep the change.", "surveyjs"));
    }

    function save() {
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) return Promise.resolve(false);
        const blocked = saveChecks.map((check) => check()).find(Boolean);
        if (blocked) {
            saveNotice = { type: "error", message: sprintf(__("Save failed: %s", "surveyjs"), blocked) };
            updateState();
            return Promise.resolve(false);
        }
        const issues = currentIssues();
        const settings = readSettings();
        const snapshot = { survey: sources.survey, theme: sources.theme, settings: JSON.stringify(settings) };
        const savedName = pendingName;
        const savedAiRevision = aiRevision;
        const body = new URLSearchParams({
            Id: config.id,
            Json: JSON.stringify(survey.json),
            Theme: JSON.stringify(theme.json),
            Settings: snapshot.settings,
            Issues: JSON.stringify(issues),
            _wpnonce: config.saveNonce
        });
        if (savedName !== null) body.set("Name", savedName);
        if (savedAiRevision) body.set("AiDraft", savedAiRevision);
        saveButton.disabled = true;
        return fetch(config.saveUrl, { method: "POST", body: body })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.statusText)))
            .then((data) => {
                if (data.IsSuccess !== 1) throw new Error(data.Error || __("The form was not saved.", "surveyjs"));
                Object.assign(saved, snapshot);
                if (savedName !== null) {
                    saved.name = savedName;
                    if (pendingName === savedName) pendingName = null;
                }
                if (savedAiRevision) onAiDraftSaved(savedAiRevision);
                if (issues.length > 0) {
                    notify("warning", sprintf(_n("Form saved with %d issue.", "Form saved with %d issues.", issues.length, "surveyjs"), issues.length), issues);
                } else {
                    notify("success", __("Form saved.", "surveyjs"));
                }
                document.dispatchEvent(new CustomEvent("surveyjs:editor-saved", { detail: { id: config.id, json: survey.json, theme: theme.json, settings: settings } }));
                return true;
            })
            .catch((error) => {
                notify("error", sprintf(__("Save failed: %s", "surveyjs"), error.message));
                return false;
            })
            .finally(updateState);
    }

    function showName() {
        const hadFocus = document.activeElement === nameInput;
        nameText.textContent = pendingName ?? saved.name;
        document.querySelectorAll("[data-sjs-form-name]").forEach((node) => { node.textContent = nameText.textContent; });
        nameForm.hidden = true;
        nameView.hidden = false;
        // After Enter or Escape keep the keyboard on the title; a click elsewhere keeps its own focus.
        if (hadFocus) nameView.focus();
    }

    function saveName() {
        const name = nameInput.value.trim();
        if (!name || (name === saved.name && pendingName === null)) {
            showName();
            return;
        }
        nameInput.readOnly = true;
        fetch(config.saveUrl, {
            method: "POST",
            body: new URLSearchParams({ Id: config.id, Name: name, _wpnonce: config.saveNonce })
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.statusText)))
            .then((data) => {
                if (data.IsSuccess !== 1) throw new Error(__("The name was not saved.", "surveyjs"));
                saved.name = name;
                pendingName = null;
                showName();
            })
            .catch((error) => notify("error", sprintf(__("Save failed: %s", "surveyjs"), error.message)))
            .finally(() => {
                nameInput.readOnly = false;
            });
    }

    function sendTestEmail() {
        const showResult = (type, message) => {
            const notice = element("div", "notice notice-" + type + " inline");
            notice.append(element("p", "", message));
            testEmailResult.replaceChildren(notice);
        };
        testEmailButton.disabled = true;
        testEmailResult.replaceChildren();
        fetch(config.testEmailUrl, {
            method: "POST",
            body: new URLSearchParams({ Id: config.id, Settings: JSON.stringify(readSettings()), _wpnonce: config.saveNonce })
        })
            .then((response) => response.json())
            .then((result) => showResult(result.success ? "success" : "error", result.data?.message || __("The test email could not be sent.", "surveyjs")))
            .catch((error) => showResult("error", error.message))
            .finally(() => {
                testEmailButton.disabled = !document.querySelector('input[data-setting="notificationEnabled"]').checked;
            });
    }

    function isJsonObject(value) {
        return typeof value === "object" && value !== null && !Array.isArray(value);
    }

    function replaceSource(tab, json) {
        const text = JSON.stringify(json, null, 2);
        if (!models) {
            sources[tab] = text;
            return;
        }
        models[tab].pushStackElement();
        models[tab].pushEditOperations([], [{ range: models[tab].getFullModelRange(), text: text }], () => null);
        models[tab].pushStackElement();
    }

    function applyExternalJson(survey, theme, message) {
        if (survey) replaceSource("survey", survey);
        if (theme) replaceSource("theme", theme);
        if (theme && !models) renderThemePreset();
        updateState();
        notify("info", message);
        renderPreview();
        runLint();
    }

    function stopUiBuilderWatch() {
        clearTimeout(uiBuilderConnectTimer);
        clearInterval(uiBuilderPollTimer);
    }

    function setUiBuilderStatus(type, message) {
        renderNotice(uiBuilderStatus, type, message);
    }

    function failUiBuilder(type, message) {
        stopUiBuilderWatch();
        setUiBuilderStatus(type, message);
    }

    function openUiBuilder() {
        stopUiBuilderWatch();
        uiBuilderConnected = false;
        uiBuilderWindow = window.open(config.uiBuilderUrl, "surveyjs-ui-builder-" + config.id);
        if (!uiBuilderWindow) {
            failUiBuilder("error", __("The browser blocked the UI Builder window. Allow pop-ups for this site and try again, or use Copy JSON to move the JSON by hand.", "surveyjs"));
            return;
        }
        setUiBuilderStatus("info", __("Opening the UI Builder in a new tab…", "surveyjs"));
        uiBuilderConnectTimer = setTimeout(() => {
            failUiBuilder("warning", __("The UI Builder has not connected to this page yet. If it does not load or shows an error, use Copy JSON to move the JSON by hand.", "surveyjs"));
        }, UI_BUILDER_CONNECT_TIMEOUT_MS);
        uiBuilderPollTimer = setInterval(() => {
            if (!uiBuilderWindow.closed) return;
            if (uiBuilderConnected) {
                stopUiBuilderWatch();
                setUiBuilderStatus();
            } else {
                failUiBuilder("error", __("The connection to the UI Builder was lost: its window was closed or a security setting of this site blocked the link. Try again or use Copy JSON to move the JSON by hand. If this happens every time, check the Cross-Origin-Opener-Policy header of this site: same-origin blocks the link, same-origin-allow-popups keeps it.", "surveyjs"));
            }
        }, UI_BUILDER_POLL_MS);
    }

    function replyToUiBuilder(event, type, extra) {
        if (!event.source) return;
        const reply = Object.assign({ source: "surveyjs-wordpress", type: type, surveyId: config.id }, extra);
        const requestId = event.data.requestId;
        if (typeof requestId === "string" || typeof requestId === "number") reply.requestId = requestId;
        event.source.postMessage(reply, event.origin);
    }

    function onUiBuilderReady(event) {
        clearTimeout(uiBuilderConnectTimer);
        uiBuilderConnected = true;
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) {
            replyToUiBuilder(event, "load-failed", { reason: "invalid-json" });
            setUiBuilderStatus("error", __("The UI Builder could not get this form because its JSON has an error. Fix the error and open the UI Builder again.", "surveyjs"));
            return;
        }
        replyToUiBuilder(event, "load", {
            protocolVersion: UI_BUILDER_PROTOCOL_VERSION,
            name: saved.name,
            json: survey.json,
            theme: theme.json,
            privacyPolicyUrl: config.privacyPolicyUrl
        });
        setUiBuilderStatus("info", __("Connected to the UI Builder. Edit the form there and click Save in the UI Builder to send the changes to this page.", "surveyjs"));
    }

    function onUiBuilderSave(event) {
        const message = event.data;
        if (message.surveyId !== config.id) {
            replyToUiBuilder(event, "save-failed", { reason: "survey-mismatch" });
            setUiBuilderStatus("warning", __("The UI Builder sent changes for another form, so they were not applied here.", "surveyjs"));
            return;
        }
        if (!isJsonObject(message.json)) {
            replyToUiBuilder(event, "save-failed", { reason: "invalid-json" });
            setUiBuilderStatus("error", __("The UI Builder sent changes that are not valid form JSON, so they were not applied.", "surveyjs"));
            return;
        }
        stopUiBuilderWatch();
        applyExternalJson(message.json, isJsonObject(message.theme) ? message.theme : null, __("Changes from the UI Builder are in the editor. Press Save to keep them.", "surveyjs"));
        replyToUiBuilder(event, "saved");
        setUiBuilderStatus("success", sprintf(__("Changes from the UI Builder arrived at %s.", "surveyjs"), new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })));
    }

    function onUiBuilderMessage(event) {
        const message = event.data;
        if (event.origin !== uiBuilderOrigin || !isJsonObject(message) || message.source !== "surveyjs-ui-builder") return;
        if (message.type === "ready") onUiBuilderReady(event);
        else if (message.type === "save") onUiBuilderSave(event);
    }

    // Copy JSON: the manual way to the UI Builder when it cannot connect to this page. Copies the JSON of the open tab
    // (the form or the theme); pasting the edited JSON back over the editor puts it on its tab (see onEditorPaste).
    function copySurveyJson() {
        copyText(sources[activeTab in sources ? activeTab : "survey"])
            .then(() => {
                showCopied(uiBuilderCopyButton, __("Copied!", "surveyjs"));
                setUiBuilderStatus("info", __("JSON copied. Paste it into the JSON Editor tab of the UI Builder. When you finish, copy the JSON there and paste it over the JSON below.", "surveyjs"));
            })
            .catch((error) => setUiBuilderStatus("error", sprintf(__("The JSON was not copied: %s", "surveyjs"), error.message)));
    }

    function isThemeJson(json) {
        return ["cssVariables", "themeName", "colorPalette"].some((key) => key in json) && !["pages", "elements", "questions"].some((key) => key in json);
    }

    // Pasting a whole form or theme JSON over the editor content (or into an empty editor) puts it where it belongs:
    // a theme pasted on the form tab goes to the Theme tab and the other way round, and a theme pasted while Adaptive
    // locks the theme JSON turns Adaptive off. Other pastes are left to the editor.
    function onEditorPaste(event) {
        if (!editor || !(activeTab in sources)) return;
        const parsed = parse((event.clipboardData ? event.clipboardData.getData("text/plain") : "").trim());
        if (!isJsonObject(parsed.json)) return;
        const model = editor.getModel();
        const selection = editor.getSelection();
        const locked = isThemeJsonLocked();
        if (!locked && model.getValue().trim() !== "" && !(selection && selection.containsRange(model.getFullModelRange()))) return;
        const tab = isThemeJson(parsed.json) ? "theme" : "survey";
        if ((tab === activeTab && !locked) || !tabNames.includes(tab)) return;
        event.preventDefault();
        event.stopPropagation();
        let message = sprintf(__("The pasted JSON is on the %s tab. Press Save to keep it.", "surveyjs"), findTab(tab).textContent.trim());
        if (tab === "theme" && themeModeCheckbox.checked) {
            themeModeCheckbox.checked = false;
            updateThemePreset();
            updateThemeJsonLock();
            message = tab === activeTab
                ? __("Adaptive is off and the pasted theme JSON is in the editor. Press Save to keep it.", "surveyjs")
                : sprintf(__("Adaptive is off and the pasted theme JSON is on the %s tab. Press Save to keep it.", "surveyjs"), findTab(tab).textContent.trim());
        }
        applyExternalJson(tab === "survey" ? parsed.json : null, tab === "theme" ? parsed.json : null, message);
        if (activeTab !== tab) switchTab(tab);
    }

    function loadMonaco() {
        return new Promise((resolve) => {
            const base = config.monacoUrl;
            // Monaco's worker must be same-origin; a blob proxy keeps it working when plugin assets are served from a CDN.
            window.MonacoEnvironment = {
                getWorkerUrl: () => URL.createObjectURL(new Blob(
                    ["self.MonacoEnvironment = { baseUrl: " + JSON.stringify(base) + " }; importScripts(" + JSON.stringify(base + "vs/base/worker/workerMain.js") + ");"],
                    { type: "text/javascript" }
                ))
            };
            // The AMD loader defines a global `define`, which makes UMD scripts loaded after it skip their globals, so it is added last.
            const script = document.createElement("script");
            script.src = base + "vs/loader.js";
            script.onload = () => {
                window.require.config({ paths: { vs: base + "vs" } });
                window.require(["vs/editor/editor.main"], () => resolve(window.monaco));
            };
            document.body.append(script);
        });
    }

    tabs.forEach((tab) => tab.addEventListener("click", (event) => {
        event.preventDefault();
        if (editor || !(tab.dataset.tab in sources)) switchTab(tab.dataset.tab);
    }));
    ["input", "change"].forEach((type) => settingPanels.forEach((panel) => panel.addEventListener(type, () => {
        closeSaveSnackbar();
        updateState();
    })));
    formatButton.addEventListener("click", format);
    insertImageButton.addEventListener("click", insertImage);
    if (setLogoButton) setLogoButton.addEventListener("click", setLogo);
    backgroundImageButton.addEventListener("click", chooseBackgroundImage);
    backgroundImageRemove.addEventListener("click", () => {
        setBackgroundImage(null);
        backgroundImageButton.focus();
    });
    if (gdprCheckbox) gdprCheckbox.addEventListener("change", toggleGdprAgreement);
    saveButton.addEventListener("click", save);
    uiBuilderButton.addEventListener("click", openUiBuilder);
    if (emptyHintUiBuilderButton) emptyHintUiBuilderButton.addEventListener("click", openUiBuilder);
    uiBuilderCopyButton.addEventListener("click", copySurveyJson);
    pickers.forEach((picker) => picker.addCheckbox.addEventListener("change", () => togglePickerQuestion(picker)));
    testEmailButton.addEventListener("click", sendTestEmail);
    let themePresets = null;

    function loadThemePresets() {
        if (!themePresets) {
            themePresets = fetch(config.themesUrl).then((response) => response.json());
        }
        return themePresets;
    }

    function renderThemePreset() {
        loadThemePresets().then((presets) => {
            const current = sources.theme.trim();
            themePresetSelect.value = Object.keys(presets).find((slug) => JSON.stringify(presets[slug], null, 2) === current) || "";
        });
    }

    function applyThemePreset() {
        const slug = themePresetSelect.value;
        if (!slug) return;
        loadThemePresets().then((presets) => {
            replaceSource("theme", presets[slug]);
            if (!models) {
                renderPreview();
            }
            closeSaveSnackbar();
            updateState();
        });
    }

    function updateThemePreset() {
        themePresetSelect.disabled = themeModeCheckbox.checked;
        transparentBackgroundCheckbox.disabled = themeModeCheckbox.checked;
        aiConvertButton.disabled = !themeModeCheckbox.checked || !adaptiveTheme;
        aiConvertButton.hidden = !themeModeCheckbox.checked;
        document.getElementById("wp-sjs-theme-preset-row").hidden = themeModeCheckbox.checked;
        document.getElementById("wp-sjs-theme-background-row").hidden = themeModeCheckbox.checked;
        document.getElementById("wp-sjs-theme-bg-image-row").hidden = themeModeCheckbox.checked || transparentBackgroundCheckbox.checked;
        renderThemePreset();
    }

    function isThemeJsonLocked() {
        return activeTab === "theme" && themeModeCheckbox.checked;
    }

    function updateThemeJsonLock() {
        const locked = isThemeJsonLocked();
        jsonPane.classList.toggle("is-theme-json-locked", locked);
        if (editor) editor.updateOptions({ readOnly: locked });
    }
    const THEME_KEYS = ["themeName", "colorPalette", "isPanelless", "backgroundImage", "backgroundImageFit", "backgroundImageAttachment", "backgroundOpacity", "header", "headerView", "cssVariables"];
    const UNSAFE_VALUE = /[;{}<>]|javascript:/i;
    const PROMPT_TOKENS = [
        ["--sjs2-color-project-brand-600", "accent: Complete/Next button background, checked radio/checkbox, selected rating, focused field border"],
        ["--sjs2-color-project-brand-700", "accent on hover/press (usually a bit darker than the accent)"],
        ["--sjs2-color-fg-brand-on-primary", "text on the accent background (button label)"],
        ["--sjs2-color-fg-brand-primary", "accent used as text: Previous/secondary buttons, links"],
        ["--sjs2-color-utility-body", "background of the whole form area (use the page background)"],
        ["--sjs2-color-utility-surface-survey", "tint painted over that background (default #edf9f7); \"transparent\" shows the page background"],
        ["--sjs2-color-bg-basic-primary", "question cards, dropdown popups"],
        ["--sjs2-color-bg-basic-secondary", "text inputs, dropdowns, checkbox boxes"],
        ["--sjs2-color-fg-basic-primary", "main text: titles, question titles, input values"],
        ["--sjs2-color-fg-basic-secondary", "muted text: descriptions, placeholders"],
        ["--sjs2-color-border-basic-secondary", "separators and light borders"],
        ["--sjs2-color-component-formbox-default-border", "border of text inputs and dropdowns"],
        ["--sjs2-color-component-formbox-hovered-border", "the same border on hover"],
        ["--sjs2-color-component-checkbox-false-default-border", "border of an unchecked checkbox"],
        ["--sjs2-color-component-radio-false-default-border", "border of an unchecked radio button"],
        ["--sjs2-color-utility-a11y", "focus ring (usually the accent at about 25% opacity)"],
        ["--sjs2-palette-red-600", "errors and required marks"],
        ["--sjs2-typography-font-family-text", "font of all text (CSS font stack)"],
        ["--sjs2-typography-font-size-default", "body text size (default 16px)"],
        ["--sjs2-typography-line-height-default", "body line height (default 24px)"],
        ["--sjs2-typography-font-family-component-survey-header-title", "form title font"],
        ["--sjs2-typography-font-size-component-survey-header-title", "form title size (default 28px)"],
        ["--sjs2-typography-font-weight-component-survey-header-title", "form title weight (default 600)"],
        ["--sjs2-typography-font-weight-component-question-title", "question title weight (default 600): take it from the site's form label"],
        ["--sjs2-typography-font-weight-component-action-large-content", "Complete/Next button label weight (default 600)"],
        ["--sjs2-typography-font-size-component-action-large-content", "Complete/Next button label size"],
        ["--sjs2-typography-line-height-component-action-large-content", "Complete/Next button label line height"],
        ["--sjs2-layout-component-action-large-box-padding-vertical", "Complete/Next button top/bottom padding"],
        ["--sjs2-layout-component-action-large-box-padding-horizontal", "Complete/Next button left/right padding"],
        ["--sjs2-layout-component-action-large-label-padding-horizontal", "extra left/right padding around the button label (default 16px, added to the button padding)"],
        ["--sjs2-radius-form", "corner radius of inputs (default 8px)"],
        ["--sjs2-radius-component-action", "corner radius of buttons"],
        ["--sjs2-radius-component-panel", "corner radius of question cards"],
        ["--sjs2-layout-component-survey-box-padding-top", "space above the form"],
        ["--sjs2-layout-component-survey-box-padding-bottom", "space below the form"],
        ["--sjs2-layout-component-survey-box-gap-vertical", "space between the form title block, the questions and the buttons"],
        ["--sjs2-layout-component-survey-header-padding-horizontal", "left/right padding of the form header"],
        ["--sjs2-layout-component-survey-title-padding-horizontal", "left/right padding of the form title"],
        ["--sjs2-layout-component-survey-description-padding-horizontal", "left/right padding of the form description"],
        ["--sjs2-layout-component-survey-footer-padding-horizontal", "left/right padding of the button row"],
        ["--sjs2-layout-component-page-content-area-padding-horizontal", "left/right padding of the questions area"],
        ["--sjs2-layout-component-page-content-area-gap-vertical", "space between questions"],
        ["--sjs2-layout-component-page-content-area-gap-horizontal", "space between questions placed in one row"],
        ["--sjs2-layout-component-question-box-gap-vertical", "space between a question title and its input"],
        ["--sjs2-layout-component-question-header-gap-vertical", "space between a question title and its description"]
    ];

    // While the Match your site with AI dialog is open, its messages show in it; after it closes (Theme applied,
    // the contrast check, Customize) they show as a snackbar.
    function aiMessage(type, message, codes, append) {
        if (!aiDialog.open) {
            if (message) notify(type, codes && codes.length ? message + " " + codes.join(", ") : message);
            return;
        }
        if (!append) aiNotice.replaceChildren();
        if (!message) return;
        const notice = element("div", "notice notice-" + type + " inline");
        const paragraph = element("p", "", message);
        (codes || []).forEach((code, index) => paragraph.append(index ? ", " : " ", element("code", "", code)));
        notice.append(paragraph);
        aiNotice.append(notice);
    }

    function describeForm() {
        const json = parse(sources.survey).json || {};
        const questionTypes = {};
        const walk = (elements) => (Array.isArray(elements) ? elements : []).forEach((item) => {
            if (!item || typeof item !== "object") return;
            if (item.type) questionTypes[item.type] = (questionTypes[item.type] || 0) + 1;
            walk(item.elements);
            walk(item.templateElements);
        });
        const pages = Array.isArray(json.pages) ? json.pages : [json];
        pages.forEach((page) => walk(page && page.elements));
        return { title: json.title, description: json.description, pages: pages.length, questionTypes: questionTypes };
    }

    function describeSite() {
        const text = siteData.text || {};
        const withoutText = (style) => Object.fromEntries(Object.entries(style || {}).filter(([name, value]) => name !== "fontFamily" || text.fontFamily !== value));
        return {
            pageBackground: siteData.pageBackground,
            text: text,
            h1: withoutText(siteData.h1),
            h2: withoutText(siteData.h2),
            h3: withoutText(siteData.h3),
            paragraph: siteData.paragraph,
            label: withoutText(siteData.label),
            link: siteData.link,
            button: withoutText(siteData.button),
            textInput: withoutText(siteData.input),
            palette: siteData.palette
        };
    }

    function buildPrompt() {
        return [
            "Create a theme for a SurveyJS form so that it looks like a native part of the WordPress site described below. The theme is a JSON object of SurveyJS design tokens (CSS custom properties).",
            "",
            "Answer with ONE JSON object only, without explanations:",
            "{\"themeName\": \"custom\", \"colorPalette\": \"light\" or \"dark\", \"isPanelless\": true or false, \"cssVariables\": {\"<token>\": \"<CSS value>\"}}",
            "",
            "Rules:",
            "- Use only the tokens listed below. Any other key is discarded.",
            "- Values are plain CSS values: colors as #hex or rgb()/rgba(), lengths in px or rem, fonts as a CSS font stack, weights as numbers. You may reference another listed token with var(--sjs2-...) or use color-mix(). No url(), no semicolons.",
            "- Take the colors, fonts, sizes and corner radii from the site data: the accent from the site's button, text and headings from text/h1-h3, fields from textInput (Arial 13.33px with an inset border means the site leaves fields unstyled: style them like the rest of the site). Use palette entries only when the computed styles do not answer the question.",
            "- Keep text contrast at least 4.5:1, including the text on the accent. If the accent is too light to be used as text on the page background, use the body text color for --sjs2-color-fg-brand-primary.",
            "- Spacing: forms on WordPress pages sit flush with the content edge, so set all horizontal paddings to 0px. Take the space between questions and above/below the form from paragraph margins, and the space between a question title and its input from label.marginBottom (at least 0.25rem). Use px or rem.",
            "- Buttons: make the Complete/Next button the same size as the site's button. Copy its padding into the button padding tokens, set the label padding to 0px, and take the label size, weight and line height from the button.",
            "- isPanelless true puts questions directly on the page (closest to plain WordPress forms); false shows every question in a card.",
            "- Set only the tokens you need. Tokens you omit keep the SurveyJS defaults: teal accent #19b394, Open Sans 16px, 8px corners, light green form background #edf9f7.",
            "",
            "Tokens:",
            ...PROMPT_TOKENS.map(([name, description]) => name + ": " + description),
            "",
            "Site data (computed styles" + (siteData.theme ? " of the WordPress theme \"" + siteData.theme + "\"" : "") + "):",
            JSON.stringify(describeSite()),
            "",
            "Form:",
            JSON.stringify(describeForm())
        ].join("\n");
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        const area = element("textarea");
        area.value = text;
        area.setAttribute("readonly", "");
        area.style.cssText = "position:fixed;top:0;left:-9999px;";
        document.body.append(area);
        area.select();
        const copied = document.execCommand("copy");
        area.remove();
        return copied ? Promise.resolve() : Promise.reject(new Error(__("The browser blocked the clipboard.", "surveyjs")));
    }

    const copiedTimers = new Map();

    function showCopied(button, copiedLabel = "Copied!") {
        button.dataset.label ||= button.textContent;
        button.textContent = copiedLabel;
        clearTimeout(copiedTimers.get(button));
        copiedTimers.set(button, setTimeout(() => { button.textContent = button.dataset.label; }, COPIED_LABEL_MS));
    }

    function copyPrompt() {
        const prompt = buildPrompt();
        copyText(prompt)
            .then(() => {
                showCopied(aiPromptButton);
                aiMessage("info", sprintf(_n("Prompt copied (%d character). Paste it into your AI chat, then paste the answer below and press Apply theme.", "Prompt copied (%d characters). Paste it into your AI chat, then paste the answer below and press Apply theme.", prompt.length, "surveyjs"), prompt.length));
                aiAnswerInput.focus();
            })
            .catch((error) => aiMessage("error", sprintf(__("The prompt was not copied: %s", "surveyjs"), error.message)));
    }

    function toggleAiForm(open) {
        if (open && !aiDialog.open) {
            aiNotice.replaceChildren();
            aiDialog.showModal();
        } else if (!open && aiDialog.open) {
            aiDialog.close();
        }
    }

    function loadThemeTokens() {
        if (!themeTokens) {
            themeTokens = fetch(config.surveyCssUrl)
                .then((response) => response.ok ? response.text() : Promise.reject(new Error(response.statusText)))
                .then((css) => new Set(css.match(/--sjs2-[\w-]+/g)));
        }
        return themeTokens;
    }

    function legacyTokens(names) {
        if (names.length === 0) return new Set();
        const probe = {};
        names.forEach((name) => { probe[name] = "16px"; });
        const model = new Survey.Model({});
        model.applyTheme({ cssVariables: probe });
        return new Set(names.filter((name) => !(name in model.cssVariables)));
    }

    function parseAnswer(text) {
        const direct = parse(text.trim());
        if (direct.json) return direct;
        const unfenced = text.replace(/```[a-z]*/gi, "");
        const start = unfenced.indexOf("{");
        const end = unfenced.lastIndexOf("}");
        return start >= 0 && end > start ? parse(unfenced.slice(start, end + 1)) : direct;
    }

    function sanitizeTheme(json, tokens) {
        const source = Object.keys(json).length > 0 && Object.keys(json).every((key) => key.startsWith("--")) ? { cssVariables: json } : json;
        const theme = {};
        const dropped = [];
        Object.keys(source).forEach((key) => {
            if (THEME_KEYS.includes(key) && key !== "cssVariables") theme[key] = source[key];
            else if (key !== "cssVariables") dropped.push(key);
        });
        const variables = isJsonObject(source.cssVariables) ? source.cssVariables : {};
        const names = Object.keys(variables);
        const legacy = legacyTokens(names.filter((name) => name.startsWith("--sjs-")));
        theme.cssVariables = {};
        names.forEach((name) => {
            const value = variables[name];
            const validValue = typeof value === "number" || (typeof value === "string" && value.trim() !== "" && !UNSAFE_VALUE.test(value));
            if ((tokens.has(name) || legacy.has(name)) && validValue) {
                theme.cssVariables[name] = typeof value === "string" ? value.trim() : value;
            } else {
                dropped.push(validValue ? name : name + " (invalid value)");
            }
        });
        return { theme: theme, dropped: dropped };
    }

    function useTheme(theme) {
        replaceSource("theme", theme);
        if (themeModeCheckbox.checked) {
            themeModeCheckbox.checked = false;
            updateThemePreset();
            updateThemeJsonLock();
        }
        clearTimeout(previewTimer);
        closeSaveSnackbar();
        updateState();
        renderPreview();
    }

    function applyAiAnswer() {
        const parsed = parseAnswer(aiAnswerInput.value);
        if (!parsed.json) {
            aiMessage("error", sprintf(__("The AI answer is not valid JSON: %s", "surveyjs"), parsed.error));
            return;
        }
        loadThemeTokens()
            .then((tokens) => {
                const result = sanitizeTheme(parsed.json, tokens);
                const count = Object.keys(result.theme.cssVariables).length;
                if (count === 0) {
                    aiMessage("error", __("The AI answer has no known SurveyJS tokens, so the theme was not changed.", "surveyjs"), result.dropped);
                    return;
                }
                contrastCheckPending = true;
                useTheme(result.theme);
                toggleAiForm(false);
                aiAnswerInput.value = "";
                const applied = sprintf(_n("Theme applied with %d token. Adaptive is off. Press Save to keep it.", "Theme applied with %d tokens. Adaptive is off. Press Save to keep it.", count, "surveyjs"), count);
                if (result.dropped.length > 0) {
                    aiMessage("warning", applied + " " + sprintf(_n("Dropped %d key that SurveyJS themes do not support:", "Dropped %d keys that SurveyJS themes do not support:", result.dropped.length, "surveyjs"), result.dropped.length), result.dropped);
                } else {
                    aiMessage("success", applied);
                }
            })
            .catch((error) => aiMessage("error", sprintf(__("The token list could not be loaded: %s", "surveyjs"), error.message)));
    }

    function isDarkColor(color) {
        const channels = (/rgba?\(([^)]+)\)/.exec(color || "") || [])[1];
        if (!channels) return false;
        const [r, g, b] = channels.split(/[\s,/]+/).map(parseFloat);
        return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 < 0.5;
    }

    function convertAdaptive() {
        const background = adaptiveTheme["--sjs2-color-utility-body"];
        useTheme({ themeName: "default", colorPalette: isDarkColor(background) ? "dark" : "light", isPanelless: true, cssVariables: Object.assign({}, adaptiveTheme) });
        aiMessage("success", sprintf(_n("The Adaptive look is now in the theme JSON (%d token). Adaptive is off. Edit the JSON or press Save to keep it.", "The Adaptive look is now in the theme JSON (%d tokens). Adaptive is off. Edit the JSON or press Save to keep it.", Object.keys(adaptiveTheme).length, "surveyjs"), Object.keys(adaptiveTheme).length));
    }

    aiPromptButton.addEventListener("click", copyPrompt);
    aiPasteButton.addEventListener("click", () => toggleAiForm(true));
    aiCancelButton.addEventListener("click", () => toggleAiForm(false));
    let aiPressedOnBackdrop = false;
    aiDialog.addEventListener("pointerdown", (event) => {
        aiPressedOnBackdrop = event.target === aiDialog;
    });
    aiDialog.addEventListener("click", (event) => {
        if ((event.target === aiDialog && aiPressedOnBackdrop) || event.target.closest("[data-close]")) toggleAiForm(false);
    });
    aiApplyButton.addEventListener("click", applyAiAnswer);
    aiConvertButton.addEventListener("click", convertAdaptive);
    themeModeCheckbox.addEventListener("change", () => {
        updateThemePreset();
        updateThemeJsonLock();
        closeSaveSnackbar();
        updateState();
        renderPreview();
    });
    transparentBackgroundCheckbox.addEventListener("change", () => {
        updateThemePreset();
        closeSaveSnackbar();
        updateState();
        renderPreview();
    });
    markdownCheckbox.addEventListener("change", renderPreview);
    themePresetSelect.addEventListener("change", applyThemePreset);
    updateThemePreset();
    window.addEventListener("message", onUiBuilderMessage);
    window.addEventListener("message", onPreviewMessage);
    previewElement.src = config.previewUrl;
    fullPreviewButton.hidden = !config.fullPreviewUrl;
    fullPreviewButton.addEventListener("click", () => setFullPreview(true));
    previewCloseButton.addEventListener("click", () => setFullPreview(false));
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && fullPreviewOpen) setFullPreview(false);
    });
    nameEditButton.addEventListener("click", () => {
        nameInput.value = pendingName ?? saved.name;
        nameView.hidden = true;
        nameForm.hidden = false;
        nameInput.focus();
        nameInput.select();
    });
    nameInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault();
            saveName();
        }
        if (event.key === "Escape") {
            event.stopPropagation();
            showName();
        }
    });
    // Clicking or tabbing away saves too; while a save is in flight the input is read-only and is left alone.
    nameInput.addEventListener("blur", () => {
        if (!nameForm.hidden && !nameInput.readOnly) saveName();
    });
    window.addEventListener("beforeunload", (event) => {
        if (leavingOnPurpose || !hasOwnUnsavedChanges()) return;
        event.preventDefault();
        event.returnValue = "";
    });

    runLint();
    renderPreview();
    renderFieldTags();
    updateState();
    const tabNames = Array.from(tabs, (tab) => tab.dataset.tab);
    const queryTab = new URLSearchParams(window.location.search).get("tab");
    const requestedTab = queryTab === "webhooks" ? "integrations" : queryTab;
    const initialTab = tabNames.includes(requestedTab) && !(requestedTab in sources) ? requestedTab : tabNames[0];
    if (initialTab && !(initialTab in sources)) switchTab(initialTab);

    const embedButton = document.getElementById("wp-sjs-embed");
    const embedPanel = document.getElementById("wp-sjs-embed-panel");
    const embedShortcode = document.getElementById("wp-sjs-embed-shortcode");
    const embedCopy = document.getElementById("wp-sjs-embed-copy");
    const embedCopyLabel = embedCopy.textContent;
    const NEXT_HINT_DISMISSED = "surveyjs-next-hint-dismissed";
    const EMBED_HISTORY_KEY = "surveyjsEmbed";
    let embedPressedOnBackdrop = false;

    function embedHistoryEntryIsCurrent() {
        return Boolean(window.history.state && window.history.state[EMBED_HISTORY_KEY]);
    }

    function openEmbed() {
        if (embedPanel.open) return;
        embedPanel.querySelector(".wp-sjs-embed__unsaved").hidden = !isDirty();
        embedPanel.showModal();
        embedButton.setAttribute("aria-expanded", "true");
        // The browser Back button then closes the dialog instead of leaving the editor, as on phones.
        if (!embedHistoryEntryIsCurrent()) window.history.pushState({ ...window.history.state, [EMBED_HISTORY_KEY]: true }, "");
        embedShortcode.focus();
        embedShortcode.select();
    }

    function onEmbedClosed() {
        embedButton.setAttribute("aria-expanded", "false");
        if (embedHistoryEntryIsCurrent()) window.history.back();
    }

    function copyShortcode() {
        embedShortcode.select();
        const copied = navigator.clipboard ? navigator.clipboard.writeText(embedShortcode.value) : Promise.reject(new Error("No clipboard"));
        copied.catch(() => document.execCommand("copy")).then(() => {
            embedCopy.textContent = embedCopy.dataset.copied;
            setTimeout(() => {
                embedCopy.textContent = embedCopyLabel;
            }, COPIED_LABEL_MS);
        });
    }

    function saveBeforeLeaving(event, leave) {
        if (!isDirty()) return;
        event.preventDefault();
        save().then((saved) => {
            if (saved) leave();
        });
    }

    function focusNotification() {
        if (!tabNames.includes("email")) return;
        switchTab("email");
        document.querySelector('input[data-setting="notificationEnabled"]').focus();
        testEmailButton.classList.add("wp-sjs-editor__highlight");
    }

    function showNextHint() {
        const steps = (config.nextSteps || []).filter((step) => step.key !== "notification" || !readSettings().notificationEnabled);
        if (!steps.length || noticeElement.querySelector(".wp-sjs-editor-next")) return;
        try {
            if (window.localStorage.getItem(NEXT_HINT_DISMISSED)) return;
        } catch (error) {
            // Storage can be blocked; the hint then just shows.
        }
        const hint = element("div", "notice notice-info inline wp-sjs-editor-next");
        const text = element("p");
        text.append(element("strong", "", __("Next:", "surveyjs")), " ");
        steps.forEach((step, index) => {
            if (index) text.append(" · ");
            const action = element("button", "button-link", step.label);
            action.type = "button";
            action.addEventListener("click", () => (step.key === "placement" ? openEmbed() : focusNotification()));
            text.append(action);
        });
        const dismiss = element("button", "notice-dismiss");
        dismiss.type = "button";
        dismiss.append(element("span", "screen-reader-text", __("Dismiss this notice.", "surveyjs")));
        dismiss.addEventListener("click", () => {
            hint.remove();
            try {
                window.localStorage.setItem(NEXT_HINT_DISMISSED, "1");
            } catch (error) {
                // Without storage the hint comes back after the next new form is saved.
            }
        });
        hint.classList.add("is-dismissible");
        hint.append(text, dismiss);
        noticeElement.append(hint);
    }

    embedButton.addEventListener("click", openEmbed);
    embedPanel.addEventListener("close", onEmbedClosed);
    embedPanel.addEventListener("pointerdown", (event) => {
        embedPressedOnBackdrop = event.target === embedPanel;
    });
    embedPanel.addEventListener("click", (event) => {
        // A drag that starts inside, for example selecting the shortcode, and ends on the backdrop also clicks the dialog.
        if ((event.target === embedPanel && embedPressedOnBackdrop) || event.target.closest("[data-close]")) embedPanel.close();
    });
    window.addEventListener("popstate", () => {
        if (embedHistoryEntryIsCurrent()) openEmbed();
        else if (embedPanel.open) embedPanel.close();
    });
    embedCopy.addEventListener("click", copyShortcode);
    embedPanel.querySelectorAll("[data-sjs-embed-save]").forEach((target) => {
        if (target.tagName === "FORM") {
            target.addEventListener("submit", (event) => saveBeforeLeaving(event, () => target.submit()));
        } else {
            target.addEventListener("click", (event) => saveBeforeLeaving(event, () => window.location.assign(target.href)));
        }
    });
    if (config.isNew) {
        document.addEventListener("surveyjs:editor-saved", showNextHint);
    }
    const requestedParams = new URLSearchParams(window.location.search);
    if (requestedParams.get("focus") === "notification") focusNotification();
    if (requestedParams.get("embed") === "1" || embedHistoryEntryIsCurrent()) openEmbed();
    if (requestedParams.has("new")) {
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete("new");
        window.history.replaceState(window.history.state, "", cleanUrl.toString());
    }

    function findTab(tab) {
        return Array.from(tabs).find((candidate) => candidate.dataset.tab === tab);
    }

    function aiChanges(draft) {
        const groups = [];
        const add = (tab, label) => {
            if (!tab || !findTab(tab)) return;
            let group = groups.find((item) => item.tab === tab);
            if (!group) {
                group = { tab: tab, labels: [] };
                groups.push(group);
            }
            if (label && !group.labels.includes(label)) group.labels.push(label);
        };
        const jsonTab = ["creator", "survey"].find((tab) => tabNames.includes(tab));
        if (draft.changedJson) add(jsonTab, __("survey JSON", "surveyjs"));
        if (draft.changedTheme) add("theme");
        (draft.changedSettings || []).forEach((key) => {
            const input = settingInput(key);
            const panel = input && input.closest("[data-panel]");
            const row = input && input.closest("tr");
            if (row) row.classList.add("wp-sjs-editor__ai-changed");
            const heading = row && row.querySelector("th");
            add(panel ? panel.dataset.panel : (key === "themeMode" || key === "transparentBackground" ? "theme" : null), heading ? heading.textContent.trim() : "");
        });
        groups.forEach((group) => findTab(group.tab).classList.add("wp-sjs-editor__tab--ai"));
        const parts = groups.map((group) => {
            const label = findTab(group.tab).textContent.trim();
            return group.labels.length ? label + " (" + group.labels.join(", ") + ")" : label;
        });
        if (draft.changedName) parts.unshift(__("Form name", "surveyjs"));
        return parts;
    }

    function aiButton(label, className, onClick) {
        const button = element("button", "button" + (className ? " " + className : ""), label);
        button.type = "button";
        button.addEventListener("click", onClick);
        return button;
    }

    function leaveAndReload() {
        leavingOnPurpose = true;
        window.location.reload();
    }

    function discardAiDraft(button) {
        const question = aiDraft && aiDraft.created
            ? __("Discard the form the AI created? The form stays empty; delete it in All Forms if you don't need it. This cannot be undone.", "surveyjs")
            : __("Discard the AI changes? The form goes back to its last saved version. This cannot be undone.", "surveyjs");
        if (!window.confirm(question)) return;
        button.disabled = true;
        fetch(aiConfig.discardUrl, {
            method: "POST",
            body: new URLSearchParams({ id: config.id, revision: knownAiRevision, _ajax_nonce: aiConfig.nonce })
        })
            .then((response) => response.json())
            .then((result) => {
                if (!result.success) throw new Error(result.data && result.data.message ? result.data.message : __("The AI changes were not discarded.", "surveyjs"));
                leaveAndReload();
            })
            .catch((error) => {
                button.disabled = false;
                renderNotice(aiNoticeElement, "error", error.message);
            });
    }

    function renderAiDraftNotice() {
        aiNoticeElement.replaceChildren();
        if (!aiDraft || !aiRevision) return;
        const notice = element("div", "notice notice-warning inline wp-sjs-editor__ai-notice");
        notice.setAttribute("role", "region");
        notice.setAttribute("aria-label", __("AI changes", "surveyjs"));
        const title = element("p", "wp-sjs-editor__ai-title");
        title.append(icon("admin-site-alt3"), element("strong", "", aiDraft.created
            ? __("This form was created by AI and is not saved yet.", "surveyjs")
            : __("AI changes are loaded into the editor but not saved yet.", "surveyjs")));
        notice.append(title);
        const author = aiDraft.author
            ? sprintf(__("Made with AI as %1$s on %2$s.", "surveyjs"), aiDraft.author, aiDraft.time)
            : sprintf(__("Made with AI on %s.", "surveyjs"), aiDraft.time);
        const effect = aiDraft.created
            ? __("Review the form and the preview, then click Save. Until then the form is empty on your site.", "surveyjs")
            : __("Review them in the editor and the preview, then click Save to publish them. Until then your live form is unchanged.", "surveyjs");
        notice.append(element("p", "", author + " " + effect));
        const changes = aiChanges(aiDraft);
        if (changes.length && !aiDraft.created) {
            notice.append(element("p", "wp-sjs-editor__ai-changes", sprintf(__("Changed: %s.", "surveyjs"), changes.join("; "))));
        }
        if (aiDraft.emailChanged) {
            const warning = element("p", "wp-sjs-editor__ai-alert");
            warning.append(element("strong", "", __("Check the Email tab:", "surveyjs")), " ", __("the AI changed who receives emails or which files they include. Save only if you asked for this.", "surveyjs"));
            notice.append(warning);
        }
        if (aiDraft.webhooksChanged) {
            const warning = element("p", "wp-sjs-editor__ai-alert");
            const text = aiDraft.webhookUrlChanged
                ? __("the AI added a webhook URL or changed one, so entries will be sent to a new address. Save only if you gave the AI exactly this URL.", "surveyjs")
                : __("the AI changed where or when entries are sent to other services. Save only if you asked for this.", "surveyjs");
            warning.append(element("strong", "", __("Check the Integrations tab:", "surveyjs")), " ", text);
            notice.append(warning);
        }
        if (aiDraft.integrationsChanged) {
            const warning = element("p", "wp-sjs-editor__ai-alert");
            warning.append(element("strong", "", __("Check the Integrations tab:", "surveyjs")), " ", __("the AI changed which connected accounts get entries, what is sent to them or when. Save only if you asked for this.", "surveyjs"));
            notice.append(warning);
        }
        if (aiDraft.outdated) {
            notice.append(element("p", "wp-sjs-editor__ai-alert", __("The form was saved after the AI started these changes. Saving replaces the edits made since then.", "surveyjs")));
        }
        const actions = element("p", "wp-sjs-editor__ai-actions");
        const discard = aiButton(__("Discard AI changes", "surveyjs"), "", () => discardAiDraft(discard));
        actions.append(aiButton(__("Save", "surveyjs"), "button-primary", () => save()), " ", discard);
        notice.append(actions);
        aiNoticeElement.append(notice);
    }

    function onAiDraftSaved(revision) {
        if (revision !== aiRevision) return;
        aiRevision = "";
        aiBaseline = null;
        if (knownAiRevision === revision) knownAiRevision = "";
        aiNoticeElement.replaceChildren();
        clearAiMarks();
    }

    function clearAiMarks() {
        rootElement.querySelectorAll(".wp-sjs-editor__ai-changed").forEach((row) => row.classList.remove("wp-sjs-editor__ai-changed"));
        tabs.forEach((tab) => tab.classList.remove("wp-sjs-editor__tab--ai"));
    }

    function applyAiDraft(draft, content) {
        clearAiMarks();
        const shown = content || draft || { name: saved.name, json: JSON.parse(saved.survey), theme: JSON.parse(saved.theme), settings: JSON.parse(saved.settings) };
        if (draft) {
            fillSettings(draft.settings);
            aiBaseline = { survey: JSON.stringify(draft.json, null, 2), theme: JSON.stringify(draft.theme, null, 2), settings: JSON.stringify(readSettings()) };
        }
        replaceSource("survey", shown.json);
        replaceSource("theme", shown.theme);
        fillSettings(shown.settings);
        pendingName = shown.name !== saved.name ? shown.name : null;
        aiDraft = draft || null;
        aiRevision = draft ? draft.revision : "";
        knownAiRevision = aiRevision;
        if (!draft) aiBaseline = null;
        if (nameForm.hidden) nameText.textContent = pendingName ?? saved.name;
        document.querySelectorAll("[data-sjs-form-name]").forEach((node) => { node.textContent = nameText.textContent; });
        document.querySelectorAll("input[data-hint]").forEach((checkbox) => checkbox.dispatchEvent(new Event("change")));
        renderThemePreset();
        updateThemePreset();
        updateThemeJsonLock();
        closeSaveSnackbar();
        updateState();
        renderPreview();
        runLint();
        renderAiDraftNotice();
        document.dispatchEvent(new CustomEvent("surveyjs:editor-external-change"));
    }

    function editorSnapshot() {
        document.dispatchEvent(new CustomEvent("surveyjs:editor-flush"));
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (survey.error || theme.error) return null;
        return {
            name: !nameForm.hidden && nameInput.value.trim() ? nameInput.value.trim() : (pendingName ?? saved.name),
            json: survey.json,
            theme: theme.json,
            settings: readSettings()
        };
    }

    function settingLabel(key) {
        const input = settingInput(key);
        const heading = input && input.closest("tr") && input.closest("tr").querySelector("th");
        const tab = input && input.closest("[data-panel]") ? findTab(input.closest("[data-panel]").dataset.panel) : null;
        if (!heading) return tab ? tab.textContent.trim() : key;
        return (tab ? tab.textContent.trim() + ": " : "") + heading.textContent.trim();
    }

    function onAiHeartbeat(state) {
        if (aiBusy || state.source === "chat" || !state.revision || state.revision === knownAiRevision || state.revision === dismissedAiRevision) return;
        const notice = element("div", "notice notice-warning inline wp-sjs-editor__ai-notice");
        notice.setAttribute("role", "alert");
        const title = element("p", "wp-sjs-editor__ai-title");
        title.append(icon("admin-site-alt3"), element("strong", "", __("An AI app changed this form while you had it open.", "surveyjs")));
        notice.append(title);
        notice.append(element("p", "", (state.time ? sprintf(__("The changes were saved as a draft on %s.", "surveyjs"), state.time) + " " : "")
            + __("Load them to review and save them. Loading replaces what is in the editor now.", "surveyjs")));
        const actions = element("p", "wp-sjs-editor__ai-actions");
        actions.append(aiButton(__("Load AI changes", "surveyjs"), "button-primary", () => {
            if (hasOwnUnsavedChanges() && !window.confirm(__("Your unsaved changes in the editor will be lost. Load the AI changes anyway?", "surveyjs"))) return;
            leaveAndReload();
        }), " ", aiButton(__("Not now", "surveyjs"), "", () => {
            dismissedAiRevision = state.revision;
            renderAiDraftNotice();
        }));
        notice.append(actions);
        aiNoticeElement.replaceChildren(notice);
    }

    renderAiDraftNotice();
    if (window.jQuery && window.wp && wp.heartbeat && aiConfig.heartbeatKey) {
        wp.heartbeat.interval(15);
        window.jQuery(document).on("heartbeat-send", (event, data) => {
            data[aiConfig.heartbeatKey] = { id: config.id };
        });
        window.jQuery(document).on("heartbeat-tick", (event, data) => {
            if (data && data[aiConfig.heartbeatKey]) onAiHeartbeat(data[aiConfig.heartbeatKey]);
        });
    }

    const monacoLoaded = loadMonaco();

    window.surveyjsEditor = {
        config: config,
        whenMonaco: () => monacoLoaded,
        getSurvey: () => parse(sources.survey).json || null,
        setSurvey: (json) => setSource("survey", json),
        getTheme: () => parse(sources.theme).json || null,
        setTheme: (json) => setSource("theme", json),
        getSettings: readSettings,
        getActiveTab: () => activeTab,
        switchTab: (tab) => {
            if (tabNames.includes(tab) && (editor || !(tab in sources))) switchTab(tab);
        },
        isDirty: isDirty,
        hasAiDraft: () => Boolean(aiRevision),
        getSnapshot: editorSnapshot,
        hasEditsAfterAi: () => Boolean(aiRevision) && hasOwnUnsavedChanges(),
        applyAiDraft: applyAiDraft,
        setAiBusy: (busy) => {
            aiBusy = Boolean(busy);
        },
        settingLabel: settingLabel,
        save: save,
        addSaveCheck: (check) => saveChecks.push(check),
        getIssues: currentIssues,
        setLintOptions: (options) => {
            lintOptions = options || undefined;
            runLint();
        },
        isFullPreviewOpen: () => fullPreviewOpen,
        setPreviewShowInvisibleElements: (show) => {
            if (previewShowInvisibleElements === Boolean(show)) return;
            previewShowInvisibleElements = Boolean(show);
            renderPreview();
        }
    };
    document.dispatchEvent(new CustomEvent("surveyjs:editor-ready", { detail: window.surveyjsEditor }));

    monacoLoaded.then((loaded) => {
        monaco = loaded;
        models = {
            survey: monaco.editor.createModel(sources.survey, "json"),
            theme: monaco.editor.createModel(sources.theme, "json")
        };
        Object.values(models).forEach((model) => model.setEOL(monaco.editor.EndOfLineSequence.LF));
        monacoElement.replaceChildren();
        editor = monaco.editor.create(monacoElement, {
            model: models.survey,
            theme: "vs",
            minimap: { enabled: false },
            fontSize: 13,
            tabSize: 2,
            scrollBeyondLastLine: false,
            automaticLayout: true,
            wordWrap: "on",
            glyphMargin: true,
            scrollbar: { vertical: "hidden", horizontal: "hidden", handleMouseWheel: false, alwaysConsumeMouseWheel: false }
        });
        decorations = editor.createDecorationsCollection();
        editor.onDidContentSizeChange(fitHeight);
        monacoElement.addEventListener("paste", onEditorPaste, true);
        fitHeight();
        editor.onDidChangeCursorPosition((event) => {
            if (event.reason !== monaco.editor.CursorChangeReason.ContentFlush) {
                scrollToLine(event.position.lineNumber, false);
            }
        });
        editor.getContribution("editor.contrib.findController").getState().onFindReplaceStateChange(() => requestAnimationFrame(pinFindWidget));
        window.addEventListener("scroll", pinFindWidget, { passive: true });
        models.survey.onDidChangeContent(() => onSourceChange("survey"));
        models.theme.onDidChangeContent(() => onSourceChange("theme"));
        editor.onDidChangeCursorPosition((event) => {
            if (activeTab !== "survey") return;
            const hit = markers.find((marker) => marker.line === event.position.lineNumber);
            if (hit && hit.path !== selectedPath) {
                selectedPath = hit.path;
                renderMarkers();
                renderLint();
            }
        });
        renderMarkers();
        updateState();
        if (initialTab === "theme" && activeTab === "survey") switchTab("theme");
    });
})();
