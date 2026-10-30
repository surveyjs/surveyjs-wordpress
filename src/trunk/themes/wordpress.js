(function () {
    const probeCss = "position:absolute;left:-9999px;top:0;visibility:hidden;pointer-events:none;";

    function parseColor(color) {
        const match = /^rgba?\(([^)]+)\)$/.exec(color || "");
        if (!match) return null;
        const parts = match[1].split(/[\s,/]+/).filter(Boolean).map(parseFloat);
        return { rgb: parts.slice(0, 3), alpha: parts.length > 3 ? parts[3] : 1 };
    }

    function isVisibleColor(color) {
        if (!color || color === "transparent") return false;
        const parsed = parseColor(color);
        return !parsed || parsed.alpha > 0;
    }

    function luminance(rgb) {
        const [r, g, b] = rgb.map((value) => {
            const channel = value / 255;
            return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    function contrastRatio(first, second) {
        const [light, dark] = [luminance(first), luminance(second)].sort((a, b) => b - a);
        return (light + 0.05) / (dark + 0.05);
    }

    function pageBackground(element) {
        for (let node = element; node && node.nodeType === 1; node = node.parentElement) {
            const parsed = parseColor(getComputedStyle(node).backgroundColor);
            if (parsed && parsed.alpha > 0) return parsed.rgb;
        }
        return [255, 255, 255];
    }

    function hasBorder(style) {
        return style.borderTopStyle !== "none" && parseFloat(style.borderTopWidth) > 0;
    }

    function createProbe(parent, tagName, setup) {
        const el = parent.ownerDocument.createElement(tagName);
        setup(el);
        el.style.cssText = probeCss;
        parent.appendChild(el);
        return el;
    }

    function getWordPressStyles(container) {
        const result = {};
        const set = (name, value, isValid = !!value) => {
            if (isValid) result[name] = value;
        };
        const parent = container.parentElement || container;

        const buttonWrapper = createProbe(parent, "div", (el) => {
            el.className = "wp-block-button";
        });
        const button = buttonWrapper.ownerDocument.createElement("button");
        button.type = "button";
        button.className = "wp-element-button wp-block-button__link";
        button.textContent = "Submit";
        buttonWrapper.appendChild(button);
        const input = createProbe(parent, "input", (el) => {
            el.type = "text";
        });
        try {
            const buttonStyle = getComputedStyle(button);
            if (isVisibleColor(buttonStyle.backgroundColor)) {
                set("--sjs-wp-primary", buttonStyle.backgroundColor);
                set("--sjs-wp-primary-foreground", buttonStyle.color, isVisibleColor(buttonStyle.color));
            }
            set("--sjs-wp-button-radius", buttonStyle.borderTopLeftRadius);

            const inputStyle = getComputedStyle(input);
            set("--sjs-wp-input-border", inputStyle.borderTopColor, hasBorder(inputStyle) && isVisibleColor(inputStyle.borderTopColor));
            set("--sjs-wp-surface", inputStyle.backgroundColor, isVisibleColor(inputStyle.backgroundColor));
            set("--sjs-wp-radius", inputStyle.borderTopLeftRadius);

            const textStyle = getComputedStyle(container);
            set("--sjs-wp-foreground", textStyle.color, isVisibleColor(textStyle.color));

            const primary = parseColor(result["--sjs-wp-primary"]);
            if (primary && contrastRatio(primary.rgb, pageBackground(container)) < 4.5) {
                set("--sjs-wp-primary-text", textStyle.color, isVisibleColor(textStyle.color));
            }
            set("--sjs-wp-font-family", textStyle.fontFamily);
            set("--sjs-wp-font-size", textStyle.fontSize);
            set("--sjs-wp-line-height", textStyle.lineHeight, !!textStyle.lineHeight && textStyle.lineHeight !== "normal");
        } finally {
            buttonWrapper.remove();
            input.remove();
        }
        return result;
    }

    function applyWordPressStyles(container) {
        const styles = getWordPressStyles(container);
        Object.keys(styles).forEach((name) => container.style.setProperty(name, styles[name]));
    }

    window.SurveyWordPress = { getWordPressStyles, applyWordPressStyles };
})();
