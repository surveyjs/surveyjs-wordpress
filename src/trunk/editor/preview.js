(function () {
    const editorOrigin = new URL(surveyjsPreview.editorUrl).origin;
    const container = document.getElementById("wp-sjs-preview-survey");
    const paletteVariable = /^--(wp--preset--color--|wp--preset--font-family--|ast-global-color-|global-palette\d|e-global-color-|e-global-typography-[\w-]+-font-family)/;
    const adapterTokenSelector = ".wp-sjs-adaptive .sjs-theme-overrides";
    const corePalette = /^--wp--preset--color--(black|white|cyan-bluish-gray|pale-pink|vivid-red|luminous-vivid-orange|luminous-vivid-amber|light-green-cyan|vivid-green-cyan|pale-cyan-blue|vivid-cyan-blue|vivid-purple|[\w-]*(global-color|palette)[\w-]*)$/;
    const siteVariable = /var\(--(sjs-wp-|ast-|global-palette|e-global-|wp--preset--)|inherit|currentColor/;
    let lastMessage = null;

    function renderMarkdownLinks(_, options) {
        const text = options.text.replace(/[&<>"']/g, (char) => "&#" + char.charCodeAt(0) + ";");
        const html = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s()]+)\)/g, "<a href=\"$2\" target=\"_blank\" rel=\"noopener\">$1</a>");
        if (html !== text) options.html = html;
    }

    function withTransparentBackground(theme) {
        const cssVariables = Object.assign({}, theme && theme.cssVariables, {
            "--sjs2-color-utility-body": "transparent",
            "--sjs2-color-utility-surface-survey": "transparent"
        });
        return Object.assign({}, theme, { cssVariables: cssVariables });
    }

    function render(message) {
        lastMessage = message;
        const model = new Survey.Model(message.json);
        model.onTextMarkdown.add(renderMarkdownLinks);
        model.onComplete.add(() => {
            const runAgain = document.createElement("button");
            runAgain.type = "button";
            runAgain.className = "wp-element-button wp-sjs-preview__run-again";
            runAgain.textContent = surveyjsPreview.runAgain;
            runAgain.addEventListener("click", () => render(lastMessage));
            container.append(runAgain);
        });
        const adaptive = message.themeMode === "adaptive";
        if (!adaptive && message.transparentBackground) {
            model.applyTheme(withTransparentBackground(message.theme));
        } else if (!adaptive && Object.keys(message.theme).length > 0) {
            model.applyTheme(message.theme);
        }
        const focusQuestion = message.focusName ? model.getQuestionByName(message.focusName) : null;
        if (focusQuestion) {
            if (focusQuestion.page) model.currentPage = focusQuestion.page;
            model.onAfterRenderQuestion.add((_, options) => {
                if (options.question !== focusQuestion) return;
                const top = options.htmlElement.getBoundingClientRect().top + window.scrollY - 16;
                window.scrollTo({ top: top, behavior: "smooth" });
            });
        }
        const survey = document.createElement("div");
        container.replaceChildren(survey);
        if (adaptive) {
            survey.classList.add("wp-sjs-adaptive");
            SurveyWordPress.applyWordPressStyles(survey);
        }
        if (message.checkContrast) {
            model.onAfterRenderSurvey.add(() => requestAnimationFrame(() => {
                window.parent.postMessage({ type: "surveyjs-preview-contrast", issues: contrastIssues() }, editorOrigin);
            }));
        }
        SurveyUI.renderSurvey(model, survey);
    }

    function parseColor(value) {
        const rgb = /rgba?\(([^)]+)\)/.exec(value || "");
        if (rgb) {
            const [r, g, b, a = 1] = rgb[1].split(/[\s,/]+/).filter(Boolean).map(parseFloat);
            return [r, g, b, a];
        }
        const srgb = /color\(srgb ([^)]+)\)/.exec(value || "");
        if (srgb) {
            const [r, g, b, a = 1] = srgb[1].split(/[\s/]+/).filter(Boolean).map(parseFloat);
            return [r * 255, g * 255, b * 255, a];
        }
        return null;
    }

    function blend(top, bottom) {
        const alpha = top[3];
        return [0, 1, 2].map((i) => top[i] * alpha + bottom[i] * (1 - alpha)).concat(1);
    }

    function effectiveBackground(element) {
        const layers = [];
        for (let node = element; node && node.nodeType === 1; node = node.parentElement) {
            layers.push(parseColor(getComputedStyle(node).backgroundColor));
        }
        return layers.reverse().reduce((under, layer) => layer ? blend(layer, under) : under, [255, 255, 255, 1]);
    }

    function luminance(color) {
        const [r, g, b] = color.slice(0, 3).map((channel) => {
            const value = channel / 255;
            return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    function contrastIssues() {
        const checks = [
            ["question title", ".sd-question__title"],
            ["input text", ".sd-formbox__input"],
            ["button label", ".sd-navigation__complete-btn, .sd-navigation__next-btn"]
        ];
        return checks.map(([name, selector]) => {
            const element = container.querySelector(selector);
            const color = element && parseColor(getComputedStyle(element).color);
            if (!color) return null;
            const background = effectiveBackground(element);
            const [lighter, darker] = [luminance(blend(color, background)), luminance(background)].sort((a, b) => b - a);
            const ratio = (lighter + 0.05) / (darker + 0.05);
            return ratio < 4.5 ? { element: name, ratio: Math.floor(ratio * 10) / 10 } : null;
        }).filter(Boolean);
    }

    function cssRules() {
        const rules = [];
        const walk = (list) => Array.from(list).forEach((rule) => {
            if (rule.cssRules) walk(rule.cssRules);
            if (rule.style) rules.push(rule);
        });
        Array.from(document.styleSheets).forEach((sheet) => {
            try {
                walk(sheet.cssRules);
            } catch (error) {
                return;
            }
        });
        return rules;
    }

    function customProperties(rules, test) {
        const names = new Set();
        rules.forEach((rule) => Array.from(rule.style).forEach((name) => {
            if (test(name, rule)) names.add(name);
        }));
        return Array.from(names);
    }

    function pageBackground() {
        for (let node = container; node && node.nodeType === 1; node = node.parentElement) {
            const color = getComputedStyle(node).backgroundColor;
            if (color && color !== "transparent" && !/,\s*0\)$/.test(color)) return color;
        }
        return "rgb(255, 255, 255)";
    }

    function pick(style, properties) {
        const result = {};
        properties.forEach((property) => {
            const value = style[property];
            if (value && value !== "none" && value !== "normal") result[property] = value;
        });
        return result;
    }

    function probe(parent, html, select) {
        const wrapper = document.createElement("div");
        wrapper.style.cssText = "position:absolute;left:-9999px;top:0;visibility:hidden;pointer-events:none;";
        wrapper.innerHTML = html;
        parent.append(wrapper);
        try {
            return select(wrapper);
        } finally {
            wrapper.remove();
        }
    }

    function siteData() {
        const parent = container.parentElement || container;
        const rules = cssRules();
        const textProperties = ["color", "fontFamily", "fontSize", "fontWeight", "lineHeight", "letterSpacing", "textTransform"];
        const box = (style) => pick(style, ["backgroundColor", "color", "fontFamily", "fontSize", "fontWeight", "lineHeight", "textTransform", "letterSpacing", "paddingTop", "paddingRight", "paddingBottom", "paddingLeft", "borderTopWidth", "borderTopStyle", "borderTopColor", "borderTopLeftRadius", "boxShadow"]);
        const elements = probe(parent, "<h1>A</h1><h2>A</h2><h3>A</h3><p><a href=\"#\">A</a></p>"
            + "<form><p><label for=\"wp-sjs-probe-input\">A</label></p></form>"
            + "<div class=\"wp-block-button\"><button type=\"button\" class=\"wp-element-button wp-block-button__link\">Submit</button></div>"
            + "<input type=\"text\" />", (wrapper) => ({
            h1: pick(getComputedStyle(wrapper.querySelector("h1")), textProperties),
            h2: pick(getComputedStyle(wrapper.querySelector("h2")), textProperties),
            h3: pick(getComputedStyle(wrapper.querySelector("h3")), textProperties),
            paragraph: pick(getComputedStyle(wrapper.querySelector("p")), ["marginTop", "marginBottom"]),
            label: pick(getComputedStyle(wrapper.querySelector("label")), textProperties.concat(["display", "marginBottom"])),
            link: pick(getComputedStyle(wrapper.querySelector("a")), ["color", "textDecorationLine"]),
            button: box(getComputedStyle(wrapper.querySelector("button"))),
            input: box(getComputedStyle(wrapper.querySelector("input")))
        }));
        const rootStyle = getComputedStyle(document.body);
        const palette = {};
        customProperties(rules, (name) => paletteVariable.test(name) && !corePalette.test(name)).forEach((name) => {
            const value = rootStyle.getPropertyValue(name).trim();
            if (value) palette[name] = value;
        });
        const themeClass = Array.from(document.body.classList).find((name) => name.startsWith("wp-theme-") && !name.startsWith("wp-theme-child"));
        return Object.assign({
            theme: themeClass ? themeClass.slice("wp-theme-".length) : "",
            pageBackground: pageBackground(),
            text: pick(getComputedStyle(parent), textProperties)
        }, elements, { palette: palette, adaptive: SurveyWordPress.getWordPressStyles(container) });
    }

    function adaptiveTheme() {
        const rules = cssRules().filter((rule) => rule.selectorText === adapterTokenSelector);
        const specified = {};
        rules.forEach((rule) => Array.from(rule.style).forEach((name) => {
            if (name.startsWith("--sjs2-")) specified[name] = rule.style.getPropertyValue(name).trim();
        }));
        const host = document.createElement("div");
        host.className = "wp-sjs-adaptive";
        host.style.cssText = "position:absolute;left:-9999px;top:0;visibility:hidden;";
        const target = document.createElement("div");
        target.className = "sjs-theme-overrides";
        host.append(target);
        container.parentElement.append(host);
        try {
            SurveyWordPress.applyWordPressStyles(host);
            const computed = getComputedStyle(target);
            const cssVariables = {};
            Object.keys(specified).forEach((name) => {
                cssVariables[name] = siteVariable.test(specified[name]) ? computed.getPropertyValue(name).trim() : specified[name];
            });
            cssVariables["--sjs2-color-utility-body"] = pageBackground();
            return cssVariables;
        } finally {
            host.remove();
        }
    }

    window.addEventListener("message", (event) => {
        if (event.source !== window.parent || event.origin !== editorOrigin) return;
        if (event.data && event.data.type === "surveyjs-preview-render") {
            render(event.data);
        }
    });

    window.parent.postMessage({ type: "surveyjs-preview-ready", site: siteData(), adaptiveTheme: adaptiveTheme() }, editorOrigin);
})();
