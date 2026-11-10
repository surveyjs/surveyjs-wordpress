(function () {
    const config = window.surveyjsTemplates;
    const previewOrigin = new URL(config.previewUrl).origin;
    const form = document.getElementById("wp-sjs-templates-form");
    const search = document.getElementById("wp-sjs-templates-search");
    const categoryList = document.querySelector(".wp-sjs-templates__categories");
    const categoryButtons = Array.from(document.querySelectorAll(".wp-sjs-templates__category"));
    const cards = Array.from(document.querySelectorAll(".wp-sjs-template[data-template]"));
    const empty = document.querySelector(".wp-sjs-templates__empty");
    const emptyMine = document.querySelector(".wp-sjs-templates__empty-mine");
    const dialog = document.getElementById("wp-sjs-template-preview");
    const dialogTitle = document.getElementById("wp-sjs-template-preview-title");
    const dialogStatus = document.getElementById("wp-sjs-template-preview-status");
    const dialogUse = document.getElementById("wp-sjs-template-preview-use");
    const frame = dialog.querySelector("iframe");
    const statusText = dialogStatus.textContent;
    const forms = new Map();
    let category = "";
    let previewReady = false;
    let previewData = null;

    function filter() {
        const words = search.value.toLowerCase().split(/\s+/).filter(Boolean);
        let visible = 0;
        cards.forEach((card) => {
            const show = (!category || card.dataset.categories.split(" ").includes(category))
                && words.every((word) => card.dataset.search.includes(word));
            card.hidden = !show;
            if (show) visible++;
        });
        const noTemplatesOfMine = visible === 0 && category === config.mineCategory && words.length === 0;
        empty.hidden = visible > 0 || noTemplatesOfMine;
        emptyMine.hidden = !noTemplatesOfMine;
    }

    function selectCategory(button) {
        category = button.dataset.category;
        categoryButtons.forEach((item) => {
            item.classList.toggle("is-active", item === button);
            item.setAttribute("aria-pressed", item === button ? "true" : "false");
        });
        filter();
    }

    function renderPreview() {
        if (!previewReady || !previewData) return;
        frame.contentWindow.postMessage({
            type: "surveyjs-preview-render",
            json: previewData.json,
            theme: previewData.theme,
            themeMode: previewData.themeMode,
            transparentBackground: false
        }, previewOrigin);
    }

    function loadTemplate(id) {
        if (!forms.has(id)) {
            const url = new URL(config.jsonUrl);
            url.searchParams.set("template", id);
            forms.set(id, fetch(url, { credentials: "same-origin" })
                .then((response) => response.json())
                .then((result) => {
                    if (!result.success) throw new Error(config.loadError);
                    return result.data;
                })
                .catch((error) => {
                    forms.delete(id);
                    throw error;
                }));
        }
        return forms.get(id);
    }

    function openPreview(card) {
        const id = card.dataset.template;
        previewData = null;
        dialogTitle.textContent = card.querySelector(".wp-sjs-template__name").textContent;
        dialogStatus.textContent = statusText;
        dialogUse.value = id;
        if (!frame.getAttribute("src")) frame.src = config.previewUrl;
        dialog.classList.add("is-loading");
        dialog.showModal();
        loadTemplate(id).then((data) => {
            if (dialogUse.value !== id) return;
            previewData = data;
            renderPreview();
            dialog.classList.remove("is-loading");
        }, () => {
            dialogStatus.textContent = config.loadError;
        });
    }

    window.addEventListener("message", (event) => {
        if (event.source !== frame.contentWindow || event.origin !== previewOrigin) return;
        if (event.data && event.data.type === "surveyjs-preview-ready") {
            previewReady = true;
            renderPreview();
        } else if (event.data && event.data.type === "surveyjs-preview-escape") {
            dialog.close();
        }
    });

    form.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && event.target.matches("input[type=text], input[type=search]")) event.preventDefault();
    });
    search.addEventListener("input", filter);
    categoryButtons.forEach((button) => button.addEventListener("click", () => selectCategory(button)));
    cards.forEach((card) => {
        const button = card.querySelector(".wp-sjs-template__preview");
        button.hidden = false;
        button.addEventListener("click", () => openPreview(card));
    });
    dialog.addEventListener("click", (event) => {
        if (event.target === dialog || event.target.closest("[data-close]")) dialog.close();
    });

    search.hidden = false;
    categoryList.hidden = false;
    const initialCategory = categoryButtons.find((button) => button.dataset.category === form.dataset.category);
    if (initialCategory) selectCategory(initialCategory);
})();
