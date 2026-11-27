/* Settings > General, Spam Protection and Add-on.
   - Auto tabs (General, Add-on): a setting is saved as soon as it changes: checkboxes and selects at once, fields
     when you leave them or press Enter. A snackbar says it was saved. The Save Changes button is hidden.
   - Manual tabs (Spam Protection: the CAPTCHA service and its keys only work together): a Save button next to the
     section title, shown only while something is changed.
   Without JavaScript the form keeps its Save Changes button. Saving posts the whole tab to options.php, as the
   button does, so SurveyJS_SettingsPage keeps the settings of the other tabs. */
(function () {
    const form = document.getElementById("wp-sjs-settings-form");
    if (!form) {
        return;
    }
    const l10n = window.surveyjsSettingsL10n || {};
    const manual = form.dataset.sjsSave === "manual";
    const submit = form.querySelector("p.submit");
    let saving = null;
    let queued = false;

    function notify(type, message) {
        if (window.surveyjsSnackbar) {
            window.surveyjsSnackbar.show({ type: type, message: message });
        }
    }

    function fields() {
        return Array.from(form.querySelectorAll("input, select, textarea")).filter(function (field) {
            return field.name && field.type !== "hidden" && field.type !== "submit";
        });
    }

    function value(field) {
        return field.type === "checkbox" || field.type === "radio" ? String(field.checked) : field.value;
    }

    // The values as saved, to show the Save button of a manual tab only while something differs.
    let saved = new Map();
    function remember() {
        saved = new Map(fields().map(function (field) {
            return [field, value(field)];
        }));
    }

    function changed() {
        return fields().some(function (field) {
            return saved.get(field) !== value(field);
        });
    }

    function save() {
        if (saving) {
            queued = true;
            return saving;
        }
        const data = new FormData(form);
        data.set("submit", "1");
        // getAttribute: form.action is the hidden field named "action" that settings_fields() adds.
        saving = fetch(form.getAttribute("action"), { method: "POST", body: data, credentials: "same-origin" })
            .then(function (response) {
                // options.php answers a saved form with a redirect back to the page with settings-updated=true.
                if (!response.ok || !/[?&]settings-updated=true/.test(response.url)) {
                    throw new Error("not saved");
                }
                return response.text();
            })
            .then(function (html) {
                if (manual) {
                    refresh(html);
                }
                remember();
                notify("success", l10n.saved || "Settings saved.");
            })
            .catch(function () {
                notify("error", l10n.failed || "The settings were not saved.");
            })
            .finally(function () {
                saving = null;
                updateButtons();
                if (queued) {
                    queued = false;
                    save();
                }
            });
        return saving;
    }

    // After a manual save the cards come from the saved page: the secret key field is empty again, with the
    // saved key's last characters as its placeholder.
    function refresh(html) {
        const page = new DOMParser().parseFromString(html, "text/html");
        form.querySelectorAll(".wp-sjs-settings__card").forEach(function (card) {
            const section = card.closest(".wp-sjs-settings__section");
            const fresh = section && page.querySelector("#" + CSS.escape(section.id) + " .wp-sjs-settings__card");
            if (fresh) {
                card.replaceWith(document.importNode(fresh, true));
            }
        });
        updateThreshold();
    }

    // reCAPTCHA v3 only: the score threshold.
    function updateThreshold() {
        const type = document.getElementById("sjs-captcha-type");
        const threshold = document.getElementById("sjs-captcha-threshold");
        if (type && threshold) {
            threshold.disabled = type.value !== "recaptcha_v3";
        }
    }

    const buttons = [];
    function updateButtons() {
        const dirty = changed();
        buttons.forEach(function (button) {
            button.hidden = !dirty;
            button.disabled = Boolean(saving);
        });
    }

    if (submit) {
        submit.hidden = true;
    }
    if (manual) {
        form.querySelectorAll(".wp-sjs-settings__head").forEach(function (head) {
            const button = document.createElement("button");
            button.type = "submit";
            button.className = "button button-primary wp-sjs-settings__save";
            button.textContent = l10n.save || "Save";
            button.hidden = true;
            head.append(button);
            buttons.push(button);
        });
    }
    remember();

    let stepTimer = 0;
    form.addEventListener("change", function (event) {
        if (event.target.id === "sjs-captcha-type") {
            updateThreshold();
        }
        if (manual) {
            updateButtons();
            return;
        }
        if (saved.get(event.target) === value(event.target)) {
            return;
        }
        // The arrows of a number field change it on every click: save once they stop.
        if (event.target.type === "number") {
            window.clearTimeout(stepTimer);
            stepTimer = window.setTimeout(save, 600);
            return;
        }
        save();
    });
    form.addEventListener("input", function () {
        if (manual) {
            updateButtons();
        }
    });
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!manual || changed()) {
            save();
        }
    });
})();
