(function () {
    const LINT_DEBOUNCE_MS = 300;
    const PREVIEW_DEBOUNCE_MS = 400;

    const config = JSON.parse(document.getElementById("wp-sjs-editor-config").textContent);
    const nameInput = document.getElementById("wp-sjs-survey-name");
    const formatButton = document.getElementById("wp-sjs-format");
    const saveButton = document.getElementById("wp-sjs-save");
    const uiBuilderButton = document.getElementById("wp-sjs-ui-builder");
    const uiBuilderOrigin = new URL(config.uiBuilderUrl).origin;
    const noticeElement = document.getElementById("wp-sjs-editor-notice");
    const monacoElement = document.getElementById("wp-sjs-monaco");
    const lintElement = document.getElementById("wp-sjs-lint");
    const previewElement = document.getElementById("wp-sjs-preview");
    const tabs = document.querySelectorAll(".wp-sjs-editor__tabs .nav-tab");

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

    nameInput.value = config.name;

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
        if (surveyError) return "Survey JSON: " + surveyError;
        const themeError = parse(sources.theme).error;
        if (themeError) return "Theme JSON: " + themeError;
        return null;
    }

    function isDirty() {
        return nameInput.value !== saved.name || sources.survey !== saved.survey || sources.theme !== saved.theme;
    }

    function updateState() {
        const error = syntaxError();
        saveButton.disabled = Boolean(error);
        uiBuilderButton.disabled = Boolean(error);
        formatButton.disabled = Boolean(parse(sources[activeTab]).error);
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
                ? "The survey JSON does not parse yet, so no check has run."
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
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) return;
        const model = new Survey.Model(survey.json);
        model.readOnly = true;
        if (Object.keys(theme.json).length > 0) {
            model.applyTheme(theme.json);
        }
        const container = element("div");
        previewElement.replaceChildren(container);
        SurveyUI.renderSurvey(model, container);
    }

    function onSourceChange(tab) {
        sources[tab] = models[tab].getValue(monaco.editor.EndOfLinePreference.LF);
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
        tabs.forEach((item) => item.classList.toggle("nav-tab-active", item.dataset.tab === tab));
        editor.setModel(models[tab]);
        fitHeight();
        renderMarkers();
        updateState();
    }

    function format() {
        const parsed = parse(sources[activeTab]);
        if (parsed.json) {
            editor.executeEdits("format", [{ range: models[activeTab].getFullModelRange(), text: JSON.stringify(parsed.json, null, 2) }]);
        }
    }

    function save() {
        const survey = parse(sources.survey);
        const theme = parse(sources.theme);
        if (!survey.json || !theme.json) return;
        const issueCount = SurveyLinter.lintSurvey(survey.json).findings.length;
        const snapshot = { name: nameInput.value, survey: sources.survey, theme: sources.theme };
        saveButton.disabled = true;
        fetch(config.saveUrl, {
            method: "POST",
            body: new URLSearchParams({
                Id: config.id,
                Name: snapshot.name,
                Json: JSON.stringify(survey.json),
                Theme: JSON.stringify(theme.json),
                _wpnonce: config.saveNonce
            })
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error(response.statusText)))
            .then((data) => {
                if (data.IsSuccess !== 1) throw new Error("The survey was not saved.");
                Object.assign(saved, snapshot);
                saveNotice = issueCount > 0
                    ? { type: "warning", message: "Survey saved with " + plural(issueCount, "issue", "issues") + "." }
                    : { type: "success", message: "Survey saved." };
            })
            .catch((error) => {
                saveNotice = { type: "error", message: "Save failed: " + error.message };
            })
            .finally(updateState);
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
                name: nameInput.value,
                json: survey.json,
                theme: theme.json
            }, event.origin);
        } else if (message.type === "save" && message.surveyId === config.id && isJsonObject(message.json)) {
            replaceSource("survey", message.json);
            replaceSource("theme", isJsonObject(message.theme) ? message.theme : {});
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
        if (editor) switchTab(tab.dataset.tab);
    }));
    formatButton.addEventListener("click", format);
    saveButton.addEventListener("click", save);
    uiBuilderButton.addEventListener("click", openUiBuilder);
    window.addEventListener("message", onUiBuilderMessage);
    nameInput.addEventListener("input", () => {
        saveNotice = null;
        updateState();
    });
    window.addEventListener("beforeunload", (event) => {
        if (isDirty()) event.preventDefault();
    });

    runLint();
    renderPreview();
    updateState();

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
    });
})();
