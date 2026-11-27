// Runs in the browser (page.evaluate), so it must not reference anything outside its own body.
function inspectSurvey(rootSelector) {
  const root = document.querySelector(rootSelector);
  if (!root) return [{ check: "missing", element: rootSelector, detail: "the survey root is not on the page" }];

  const FREEZE_ID = "sjs-theme-qa-freeze";
  const OWN_SHEET = /\/plugins\/surveyjs(-forms-addon)?\//;
  const PAGE_ROOT_SELECTOR = /^(html|body|:root)$/i;
  const PROPS = [
    "background-color", "background-image",
    "border-top-width", "border-right-width", "border-bottom-width", "border-left-width",
    "border-top-style", "border-right-style", "border-bottom-style", "border-left-style",
    "border-top-color", "border-right-color", "border-bottom-color", "border-left-color",
    "border-top-left-radius", "border-top-right-radius", "border-bottom-left-radius", "border-bottom-right-radius",
    "box-shadow", "outline-style", "outline-width",
    "padding-top", "padding-right", "padding-bottom", "padding-left",
    "margin-top", "margin-right", "margin-bottom", "margin-left",
    "min-height", "min-width", "max-width", "display", "position", "opacity", "vertical-align",
    "color", "font-size", "font-weight", "letter-spacing", "text-transform", "text-decoration-line", "list-style-type"
  ];
  const INHERITED = new Set(["color", "font-size", "font-weight", "letter-spacing", "text-transform", "list-style-type"]);

  function describe(el) {
    const classes = [...el.classList].filter((name) => name.startsWith("sd-") || name.startsWith("sv-") || name.startsWith("wp-sjs-")).slice(0, 2);
    return el.tagName.toLowerCase() + (classes.length ? "." + classes.join(".") : "");
  }

  function isVisible(el) {
    const rect = el.getBoundingClientRect();
    const style = getComputedStyle(el);
    return rect.width > 0 && rect.height > 0 && style.visibility !== "hidden" && style.opacity !== "0";
  }

  function parseColor(value) {
    let match = /^rgba?\(([^)]+)\)$/.exec(value);
    if (match) {
      const parts = match[1].split(/[\s,/]+/).filter(Boolean).map(parseFloat);
      return { rgb: parts.slice(0, 3), alpha: parts.length > 3 ? parts[3] : 1 };
    }
    match = /^color\(srgb ([^)]+)\)$/.exec(value);
    if (match) {
      const parts = match[1].split(/[\s/]+/).filter(Boolean).map(parseFloat);
      return { rgb: parts.slice(0, 3).map((part) => part * 255), alpha: parts.length > 3 ? parts[3] : 1 };
    }
    return null;
  }

  function luminance(rgb) {
    const [r, g, b] = rgb.map((value) => {
      const channel = value / 255;
      return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
  }

  function blend(top, bottom) {
    return top.rgb.map((value, index) => value * top.alpha + bottom[index] * (1 - top.alpha));
  }

  function backgroundBehind(el) {
    const layers = [];
    const rect = el.getBoundingClientRect();
    for (let node = el; node && node.nodeType === 1; node = node.parentElement) {
      const box = node.getBoundingClientRect();
      if (box.left > rect.left + 1 || box.right < rect.right - 1 || box.top > rect.top + 1 || box.bottom < rect.bottom - 1) continue;
      const color = parseColor(getComputedStyle(node).backgroundColor);
      if (color && color.alpha > 0) {
        layers.push(color);
        if (color.alpha >= 1) break;
      }
    }
    return layers.reverse().reduce((rgb, layer) => blend(layer, rgb), [255, 255, 255]);
  }

  function contrast(first, second) {
    const [light, dark] = [luminance(first), luminance(second)].sort((a, b) => b - a);
    return (light + 0.05) / (dark + 0.05);
  }

  const findings = [];
  const add = (check, el, detail) => findings.push({ check, element: describe(el), detail });

  const elements = [root, ...root.querySelectorAll("*")].filter((el) => !el.closest(".sd-html, svg, .sd-visuallyhidden, .sv-visuallyhidden"));
  const indexOf = new Map(elements.map((el, index) => [el, index]));
  const tracked = [root.parentElement, ...elements];

  function snapshot() {
    return tracked.map((el) => {
      const style = getComputedStyle(el);
      const typed = el.computedStyleMap ? el.computedStyleMap() : null;
      return PROPS.map((prop) => (typed && /^margin-/.test(prop) && String(typed.get(prop)) === "auto" ? "auto" : style.getPropertyValue(prop)));
    });
  }

  function removeSiteRules() {
    const removed = [];
    const walk = (owner, rules) => {
      for (let index = rules.length - 1; index >= 0; index--) {
        const rule = rules[index];
        if (rule.cssRules && !(rule instanceof CSSStyleRule)) {
          walk(rule, rule.cssRules);
          continue;
        }
        if (!(rule instanceof CSSStyleRule)) continue;
        const declared = [...rule.style];
        const onlyVariables = declared.every((prop) => prop.startsWith("--"));
        const pageRootOnly = rule.selectorText.split(",").every((selector) => PAGE_ROOT_SELECTOR.test(selector.trim()));
        if (onlyVariables || pageRootOnly) continue;
        removed.push({ owner, index, cssText: rule.cssText, selectorText: rule.selectorText, declared: declared.join(" ") });
        owner.deleteRule(index);
      }
    };
    for (const sheet of document.styleSheets) {
      const node = sheet.ownerNode;
      if ((sheet.href && OWN_SHEET.test(sheet.href)) || (node && node.id && (node.id.startsWith("wps-") || node.id === FREEZE_ID))) continue;
      let rules;
      try {
        rules = sheet.cssRules;
      } catch (error) {
        continue;
      }
      walk(sheet, rules);
    }
    return removed;
  }

  function restore(removed) {
    for (let index = removed.length - 1; index >= 0; index--) {
      const rule = removed[index];
      rule.owner.insertRule(rule.cssText, rule.index);
    }
  }

  const freeze = document.createElement("style");
  freeze.id = FREEZE_ID;
  freeze.textContent = "*, *::before, *::after { transition: none !important; animation: none !important; }";
  document.head.append(freeze);
  let withSite;
  let withoutSite;
  let removed = [];
  try {
    withSite = snapshot();
    removed = removeSiteRules();
    withoutSite = snapshot();
  } finally {
    restore(removed);
    freeze.remove();
  }

  const leaks = new Map();
  const hasOwnText = (el) => [...el.childNodes].some((node) => node.nodeType === 3 && node.textContent.trim());
  const column = (prop) => PROPS.indexOf(prop);
  const rowOf = (el) => (el === root.parentElement ? 0 : indexOf.has(el) ? indexOf.get(el) + 1 : -1);
  const hasChrome = (row) => [withSite, withoutSite].some((state) => {
    const background = parseColor(state[row][column("background-color")]);
    return (background && background.alpha > 0) || state[row][column("border-top-style")] !== "none" || state[row][column("box-shadow")] !== "none";
  });

  function record(el, prop, expected, actual) {
    const key = describe(el) + " " + prop;
    if (leaks.has(key)) {
      leaks.get(key).count++;
      return;
    }
    const family = prop.replace(/-(top|right|bottom|left)(-(left|right))?/, "").split("-")[0];
    const sources = removed.filter((rule) => rule.declared.includes(family) && (() => {
      try {
        return el.matches(rule.selectorText);
      } catch (error) {
        return false;
      }
    })()).map((rule) => rule.selectorText).slice(0, 3);
    leaks.set(key, { element: describe(el), prop, expected, actual, count: 1, sources });
  }

  elements.forEach((el, position) => {
    const row = position + 1;
    if (!isVisible(el)) return;
    PROPS.forEach((prop, index) => {
      const actual = withSite[row][index];
      const expected = withoutSite[row][index];
      if (actual === expected) return;
      const side = /^border-(top|right|bottom|left)-(color|width)$/.exec(prop);
      if (side && withSite[row][column(`border-${side[1]}-style`)] === "none" && withoutSite[row][column(`border-${side[1]}-style`)] === "none") return;
      if (prop === "outline-width" && withSite[row][column("outline-style")] === "none" && withoutSite[row][column("outline-style")] === "none") return;
      if (/radius$/.test(prop) && !hasChrome(row)) return;
      if (!INHERITED.has(prop)) {
        record(el, prop, expected, actual);
        return;
      }
      if (!hasOwnText(el)) return;
      let origin = el;
      for (let parent = origin.parentElement; parent && parent !== root.parentElement; parent = parent.parentElement) {
        const parentRow = rowOf(parent);
        if (parentRow < 0 || withSite[parentRow][index] !== actual || withoutSite[parentRow][index] !== expected) break;
        origin = parent;
      }
      if (origin === root && withSite[0][index] === actual && withoutSite[0][index] === expected) return;
      record(origin, prop, expected, actual);
    });
  });
  for (const leak of leaks.values()) {
    findings.push({ check: "theme-leak", element: leak.element, detail: `${leak.prop}: ${leak.expected} -> ${leak.actual}${leak.count > 1 ? ` (x${leak.count})` : ""}${leak.sources.length ? ` from ${leak.sources.join(" | ")}` : ""}` });
  }

  const rootRect = root.getBoundingClientRect();
  const CONTROL = /^(INPUT|BUTTON|SELECT|TEXTAREA|IMG|CANVAS)$/;
  const isPainted = (el) => {
    const style = getComputedStyle(el);
    const background = parseColor(style.backgroundColor);
    return CONTROL.test(el.tagName) || hasOwnText(el) || (background && background.alpha > 0) || style.borderTopStyle !== "none" || style.boxShadow !== "none";
  };
  const outside = new Set();
  const scrolling = new Set();
  for (const el of elements) {
    if (!isVisible(el) || outside.has(el.parentElement)) continue;
    const rect = el.getBoundingClientRect();
    let container = null;
    for (let node = el.parentElement; node && node !== root.parentElement; node = node.parentElement) {
      if (getComputedStyle(node).overflowX !== "visible") {
        container = node;
        break;
      }
    }
    const box = container ? container.getBoundingClientRect() : rootRect;
    if (rect.right <= box.right + 1 && rect.left >= box.left - 1) continue;
    if (container && getComputedStyle(container).textOverflow === "ellipsis") continue;
    if (container && /auto|scroll/.test(getComputedStyle(container).overflowX) && container.scrollWidth > container.clientWidth + 1) {
      if (!scrolling.has(container)) {
        scrolling.add(container);
        add("h-scroll", container, `${container.scrollWidth}px of content in ${container.clientWidth}px`);
      }
      continue;
    }
    if (!isPainted(el)) continue;
    outside.add(el);
    add(container ? "clipped" : "overflow", el, `${Math.round(rect.left - box.left)}..${Math.round(rect.right - box.left)} of ${Math.round(box.width)}px`);
  }

  // survey-core's mobile Multiple Text shows the item title in place of an empty input and widens the input on focus or answer.
  const EMPTY_FLOATING_LABEL_ITEM = ".sd-multipletext--mobile .sd-multipletext__item-container:not(.sd-multipletext__item-container--answered)";
  for (const el of root.querySelectorAll("button, input, select, textarea, [role='button'], .sd-action")) {
    const style = getComputedStyle(el);
    if (style.display === "none" || el.closest(".sd-visuallyhidden") || el.classList.contains("sd-visuallyhidden")) continue;
    const rect = el.getBoundingClientRect();
    if (el.getClientRects().length && (rect.width < 8 || rect.height < 8) && style.opacity !== "0" && !/^(checkbox|radio|file|hidden)$/.test(el.type) && !el.classList.contains("sd-tagbox__filter-string-input") && !el.closest(EMPTY_FLOATING_LABEL_ITEM)) {
      add("collapsed", el, `${Math.round(rect.width)}x${Math.round(rect.height)}`);
    }
    if (/^(outset|inset)$/.test(style.borderTopStyle)) add("ua-default", el, `border-style: ${style.borderTopStyle}`);
  }

  for (const el of elements) {
    const text = [...el.childNodes].filter((node) => node.nodeType === 3).map((node) => node.textContent.trim()).join("");
    if (!text || !isVisible(el)) continue;
    const color = parseColor(getComputedStyle(el).color);
    if (!color || color.alpha === 0) continue;
    const background = backgroundBehind(el);
    const ratio = contrast(blend(color, background), background);
    if (ratio < 2) add("low-contrast", el, `${ratio.toFixed(2)}:1 for "${text.slice(0, 30)}"`);
  }

  return findings;
}

module.exports = { inspectSurvey };
