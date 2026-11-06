(function () {
    const LINT_DEBOUNCE_MS = 300;
    const PREVIEW_DEBOUNCE_MS = 400;
    const COPIED_LABEL_MS = 2000;

    const config = JSON.parse(document.getElementById("wp-sjs-editor-config").textContent);
    const nameInput = document.getElementById("wp-sjs-survey-name");
    const nameText = document.getElementById("wp-sjs-name-text");
    const nameView = document.getElementById("wp-sjs-name-view");
    const nameForm = document.getElementById("wp-sjs-name-form");
    const nameEditButton = document.getElementById("wp-sjs-name-edit");
    const nameSaveButton = document.getElementById("wp-sjs-name-save");
    const nameCancelButton = document.getElementById("wp-sjs-name-cancel");
    const formatButton = document.getElementById("wp-sjs-format");
    const insertImageButton = document.getElementById("wp-sjs-insert-image");
    const setLogoButton = document.getElementById("wp-sjs-set-logo");
    const gdprLabel = document.getElementById("wp-sjs-gdpr-agreement-label");
    const gdprCheckbox = document.getElementById("wp-sjs-gdpr-agreement");
    const GDPR_NAME = "gdpr_agreement";
    const imageList = document.getElementById("wp-sjs-image-list");
    const saveButton = document.getElementById("wp-sjs-save");
    const uiBuilderButton = document.getElementById("wp-sjs-ui-builder");
    const uiBuilderOrigin = new URL(config.uiBuilderUrl).origin;
    const noticeElement = document.getElementById("wp-sjs-editor-notice");
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
    const aiForm = document.getElementById("wp-sjs-ai-form");
    const aiAnswerInput = document.getElementById("wp-sjs-ai-answer");
    const aiApplyButton = document.getElementById("wp-sjs-ai-apply");
    const aiCancelButton = document.getElementById("wp-sjs-ai-cancel");
    const aiNotice = document.getElementById("wp-sjs-ai-notice");
    const testEmailButton = document.getElementById("wp-sjs-send-test-email");
    const testEmailResult = document.getElementById("wp-sjs-test-email-result");
    const REPLY_TO_QUESTION ={ type: "text", title: "Leave your email so we can get back to you", inputType: "email", placeholder: "john.doe@example.com" };
    const FILE_QUESTION = { type: "file", title: "Attach files", allowMultiple: true };
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
    let previewTimer = null;
    let saveNotice = null;
    let previewFocusName = null;
    let previewReady = false;
    let fullPreviewReady = false;
    let fullPreviewOpen = false;
    let fullPreviewReturnFocus = null;
    let siteData = null;
    let adaptiveTheme = null;
    let themeTokens = null;
    let contrastCheckPending = false;

    nameText.textContent = config.name;
    settingInputs.forEach((input) => {
        const value = config.settings[input.dataset.setting];
        if (input.type === "checkbox") {
            input.checked = "checkedValue" in input.dataset ? value === input.dataset.checkedValue : Boolean(value);
        } else if (pickers.some((picker) => picker.select === input)) {
            renderPickerOptions(pickers.find((picker) => picker.select === input), value);
        } else {
            input.value = value;
            input.placeholder = config.settingPlaceholders[input.dataset.setting] ?? "";
        }
    });
    saved.settings = JSON.stringify(readSettings());

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
            row.querySelectorAll("input, select, textarea, button").forEach((control) => { control.disabled = !enabled; });
        });
        const jsonInvalid = Boolean(parse(sources.survey).error);
        pickers.forEach((picker) => { picker.addCheckbox.disabled ||= jsonInvalid; });
    }
    dependentRows.forEach((row) => {
        rowToggles(row).forEach((toggle) => toggle.addEventListener("change", updateDependentRows));
    });
    updateDependentRows();

    function checkboxValue(input) {
        if (!("checkedValue" in input.dataset)) return input.checked;
        return input.checked ? input.dataset.checkedValue : input.dataset.uncheckedValue;
    }

    function readSettings() {
        const settings = {};
        settingInputs.forEach((input) => {
            settings[input.dataset.setting] = input.type === "checkbox" ? checkboxValue(input) : input.value;
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
            target.replaceChildren("Tags of this form: ", ...names.flatMap((name, index) => (index ? [" "] : []).concat(element("code", "", "{field:" + name + "}"))));
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
        saveNotice = null;
        updateState();
    }

    function parse(source) {
        try {
            const parsed = JSON.parse(source);
            if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
                return { error: "The JSON must be an object." };
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
            const trigger = values.length ? "Answer " + values.join(", ") + " — " : "";
            return (trigger + (reproduction.description ?? "")).trim();
        }
        if (finding.suggestion) {
            return "Nothing resolves this reference. Closest known name: \"" + finding.suggestion + "\".";
        }
        if (finding.related?.length) {
            return "Also involves " + finding.related.map((item) => item.elementName ?? item.path).join(", ") + ".";
        }
        return null;
    }

    function plural(count, one, many) {
        return count + " " + (count === 1 ? one : many);
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

    function showNotice(type, message) {
        noticeElement.replaceChildren();
        if (!message) return;
        const notice = element("div", "notice notice-" + type + " inline");
        notice.append(element("p", "", message));
        noticeElement.append(notice);
    }

    function syntaxError() {
        const surveyError = parse(sources.survey).error;
        if (surveyError) return "Config: " + surveyError;
        const themeError = parse(sources.theme).error;
        if (themeError) return "Theme: " + themeError;
        return null;
    }

    function isDirty() {
        return (!nameForm.hidden && nameInput.value.trim() !== saved.name) || sources.survey !== saved.survey || sources.theme !== saved.theme || JSON.stringify(readSettings()) !== saved.settings;
    }

    function updateState() {
        const error = syntaxError();
        saveButton.disabled = Boolean(error);
        uiBuilderButton.disabled = Boolean(error);
        updateDependentRows();
        formatButton.disabled = !(activeTab in sources) || isThemeJsonLocked() || Boolean(parse(sources[activeTab]).error);
        insertImageButton.disabled = !hasMediaLibrary();
        setLogoButton.hidden = activeTab !== "survey";
        setLogoButton.disabled = !editor || Boolean(parse(sources.survey).error) || !hasMediaLibrary();
        const survey = parse(sources.survey);
        gdprLabel.hidden = activeTab !== "survey";
        gdprCheckbox.disabled = Boolean(survey.error);
        if (survey.json) gdprCheckbox.checked = hasGdprAgreement(survey.json);
        if (error) {
            showNotice("error", error);
        } else if (saveNotice) {
            showNotice(saveNotice.type, saveNotice.message);
        } else {
            showNotice();
        }
    }

    function runLint() {
        const parsed = parse(sources.survey);
        if (!parsed.json) {
            analysis = { kind: "waiting" };
        } else {
            const startedAt = performance.now();
            const result = SurveyLinter.lintSurvey(parsed.json);
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
        const viewBottom = window.innerHeight - lintElement.offsetHeight;
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
        const waiting = analysis.kind === "waiting";
        const issueCount = waiting ? 0 : analysis.result.findings.length;
        const failed = issueCount > 0;

        const bar = element("div", "wp-sjs-lint__bar");
        bar.append(icon(waiting ? "marker" : failed ? "warning" : "yes", failed ? "wp-sjs-lint__icon-warning" : ""));
        bar.append(element("span", "wp-sjs-lint__summary", waiting
            ? "Static analysis: waiting for valid JSON"
            : failed ? "Static analysis: " + plural(issueCount, "issue", "issues") : "Static analysis: all checks passed"));
        if (!waiting) {
            bar.append(element("span", "wp-sjs-lint__meta", rules.length + " checks · " + Math.round(analysis.durationMs) + "ms"));
        }
        const toggle = element("button", "button-link wp-sjs-lint__toggle");
        toggle.type = "button";
        toggle.setAttribute("aria-expanded", String(expanded));
        toggle.setAttribute("aria-controls", "wp-sjs-lint-details");
        toggle.append(document.createTextNode(failed ? "View issues" : "View checks"), icon(expanded ? "arrow-up-alt2" : "arrow-down-alt2"));
        toggle.addEventListener("click", () => {
            expanded = !expanded;
            renderLint();
        });
        bar.append(toggle);

        const children = [bar];
        if (expanded) {
            const details = element("div", "wp-sjs-lint__details");
            details.id = "wp-sjs-lint-details";
            details.append(element("h3", "wp-sjs-lint__heading", "Checks performed"));
            details.append(element("p", "description", waiting
                ? "The form JSON does not parse yet, so no check has run."
                : rules.length + " checks over " + analysis.elementCount + " named elements."));

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
                title.append(icon("warning", "wp-sjs-lint__severity-" + items[0].finding.severity), element("code", "", rule.id), element("span", "description", plural(items.length, "finding", "findings")));
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
            focusName: focusName
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
            aiMessage("warning", "Some text may be hard to read (contrast below 4.5:1):", issues, true);
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
        saveNotice = null;
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
            saveNotice = null;
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
        saveNotice = add
            ? { type: "info", message: "The GDPR agreement was added at the end of the form. Press Save to keep it." }
            : { type: "info", message: "The GDPR agreement was removed from the form. Press Save to keep the change." };
        updateState();
    }

    function save() {
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) return;
        const issueCount = SurveyLinter.lintSurvey(survey.json).findings.length;
        const settings = readSettings();
        const snapshot = { survey: sources.survey, theme: sources.theme, settings: JSON.stringify(settings) };
        saveButton.disabled = true;
        fetch(config.saveUrl, {
            method: "POST",
            body: new URLSearchParams({
                Id: config.id,
                Json: JSON.stringify(survey.json),
                Theme: JSON.stringify(theme.json),
                Settings: snapshot.settings,
                _wpnonce: config.saveNonce
            })
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.statusText)))
            .then((data) => {
                if (data.IsSuccess !== 1) throw new Error("The form was not saved.");
                Object.assign(saved, snapshot);
                saveNotice = issueCount > 0
                    ? { type: "warning", message: "Form saved with " + plural(issueCount, "issue", "issues") + "." }
                    : { type: "success", message: "Form saved." };
                document.dispatchEvent(new CustomEvent("surveyjs:editor-saved", { detail: { id: config.id, json: survey.json, theme: theme.json, settings: settings } }));
            })
            .catch((error) => {
                saveNotice = { type: "error", message: "Save failed: " + error.message };
            })
            .finally(updateState);
    }

    function showName() {
        nameText.textContent = saved.name;
        nameForm.hidden = true;
        nameView.hidden = false;
    }

    function saveName() {
        const name = nameInput.value.trim();
        if (!name || name === saved.name) {
            showName();
            return;
        }
        nameSaveButton.disabled = true;
        fetch(config.saveUrl, {
            method: "POST",
            body: new URLSearchParams({ Id: config.id, Name: name, _wpnonce: config.saveNonce })
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.statusText)))
            .then((data) => {
                if (data.IsSuccess !== 1) throw new Error("The name was not saved.");
                saved.name = name;
                showName();
            })
            .catch((error) => showNotice("error", "Save failed: " + error.message))
            .finally(() => {
                nameSaveButton.disabled = false;
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
            .then((result) => showResult(result.success ? "success" : "error", result.data?.message || "The test email could not be sent."))
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

    function openUiBuilder() {
        window.open(config.uiBuilderUrl, "surveyjs-ui-builder-" + config.id);
    }

    function onUiBuilderMessage(event) {
        const message = event.data;
        if (event.origin !== uiBuilderOrigin || !isJsonObject(message) || message.source !== "surveyjs-ui-builder") return;
        if (message.type === "ready") {
            const survey = parse(sources.survey);
            const theme = parse(sources.theme);
            if (!survey.json || !theme.json) return;
            event.source.postMessage({
                source: "surveyjs-wordpress",
                type: "load",
                surveyId: config.id,
                name: saved.name,
                json: survey.json,
                theme: theme.json
            }, event.origin);
        } else if (message.type === "save" && message.surveyId === config.id && isJsonObject(message.json)) {
            replaceSource("survey", message.json);
            if (isJsonObject(message.theme)) replaceSource("theme", message.theme);
            saveNotice = { type: "info", message: "Changes from the UI Builder are in the editor. Press Save to keep them." };
            updateState();
            renderPreview();
            runLint();
        }
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
        saveNotice = null;
        updateState();
    })));
    formatButton.addEventListener("click", format);
    insertImageButton.addEventListener("click", insertImage);
    setLogoButton.addEventListener("click", setLogo);
    gdprCheckbox.addEventListener("change", toggleGdprAgreement);
    saveButton.addEventListener("click", save);
    uiBuilderButton.addEventListener("click", openUiBuilder);
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
            saveNotice = null;
            updateState();
        });
    }

    function updateThemePreset() {
        themePresetSelect.disabled = themeModeCheckbox.checked;
        transparentBackgroundCheckbox.disabled = themeModeCheckbox.checked;
        aiConvertButton.disabled = !themeModeCheckbox.checked || !adaptiveTheme;
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

    function aiMessage(type, message, codes, append) {
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
        return copied ? Promise.resolve() : Promise.reject(new Error("The browser blocked the clipboard."));
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
                aiMessage("info", "Prompt copied (" + prompt.length + " characters). Paste it into your AI chat, then paste the answer below and press Apply theme.");
                toggleAiForm(true);
            })
            .catch((error) => aiMessage("error", "The prompt was not copied: " + error.message));
    }

    function toggleAiForm(open) {
        aiForm.hidden = !open;
        aiPasteButton.setAttribute("aria-expanded", String(open));
        if (open) aiAnswerInput.focus();
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
        saveNotice = null;
        updateState();
        renderPreview();
    }

    function applyAiAnswer() {
        const parsed = parseAnswer(aiAnswerInput.value);
        if (!parsed.json) {
            aiMessage("error", "The AI answer is not valid JSON: " + parsed.error);
            return;
        }
        loadThemeTokens()
            .then((tokens) => {
                const result = sanitizeTheme(parsed.json, tokens);
                const count = Object.keys(result.theme.cssVariables).length;
                if (count === 0) {
                    aiMessage("error", "The AI answer has no known SurveyJS tokens, so the theme was not changed.", result.dropped);
                    return;
                }
                contrastCheckPending = true;
                useTheme(result.theme);
                toggleAiForm(false);
                aiAnswerInput.value = "";
                const applied = "Theme applied with " + plural(count, "token", "tokens") + ". Adaptive is off. Press Save to keep it.";
                if (result.dropped.length > 0) {
                    aiMessage("warning", applied + " Dropped " + plural(result.dropped.length, "key", "keys") + " that SurveyJS themes do not support:", result.dropped);
                } else {
                    aiMessage("success", applied);
                }
            })
            .catch((error) => aiMessage("error", "The token list could not be loaded: " + error.message));
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
        aiMessage("success", "The Adaptive look is now in the theme JSON (" + plural(Object.keys(adaptiveTheme).length, "token", "tokens") + "). Adaptive is off. Edit the JSON or press Save to keep it.");
    }

    aiPromptButton.addEventListener("click", copyPrompt);
    aiPasteButton.addEventListener("click", () => toggleAiForm(aiForm.hidden));
    aiCancelButton.addEventListener("click", () => toggleAiForm(false));
    aiApplyButton.addEventListener("click", applyAiAnswer);
    aiConvertButton.addEventListener("click", convertAdaptive);
    themeModeCheckbox.addEventListener("change", () => {
        updateThemePreset();
        updateThemeJsonLock();
        saveNotice = null;
        updateState();
        renderPreview();
    });
    transparentBackgroundCheckbox.addEventListener("change", () => {
        saveNotice = null;
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
        nameInput.value = saved.name;
        nameView.hidden = true;
        nameForm.hidden = false;
        nameInput.focus();
        nameInput.select();
    });
    nameCancelButton.addEventListener("click", showName);
    nameSaveButton.addEventListener("click", saveName);
    nameInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") saveName();
        if (event.key === "Escape") showName();
    });
    window.addEventListener("beforeunload", (event) => {
        if (!isDirty()) return;
        event.preventDefault();
        event.returnValue = "";
    });

    runLint();
    renderPreview();
    renderFieldTags();
    updateState();
    const tabNames = Array.from(tabs, (tab) => tab.dataset.tab);
    const requestedTab = new URLSearchParams(window.location.search).get("tab");
    const initialTab = tabNames.includes(requestedTab) && !(requestedTab in sources) ? requestedTab : tabNames[0];
    if (initialTab && !(initialTab in sources)) switchTab(initialTab);

    window.surveyjsEditor = {
        config: config,
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
        save: save,
        isFullPreviewOpen: () => fullPreviewOpen
    };
    document.dispatchEvent(new CustomEvent("surveyjs:editor-ready", { detail: window.surveyjsEditor }));

    loadMonaco().then((loaded) => {
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
