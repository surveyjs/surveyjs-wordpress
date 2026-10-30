(function () {
    const editorOrigin = new URL(surveyjsPreview.editorUrl).origin;
    const container = document.getElementById("wp-sjs-preview-survey");
    let lastMessage = null;

    function render(message) {
        lastMessage = message;
        const model = new Survey.Model(message.json);
        model.onComplete.add(() => {
            const runAgain = document.createElement("button");
            runAgain.type = "button";
            runAgain.className = "wp-element-button wp-sjs-preview__run-again";
            runAgain.textContent = surveyjsPreview.runAgain;
            runAgain.addEventListener("click", () => render(lastMessage));
            container.append(runAgain);
        });
        const adaptive = message.themeMode === "adaptive";
        if (!adaptive && Object.keys(message.theme).length > 0) {
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
        SurveyUI.renderSurvey(model, survey);
    }

    window.addEventListener("message", (event) => {
        if (event.source !== window.parent || event.origin !== editorOrigin) return;
        if (event.data && event.data.type === "surveyjs-preview-render") {
            render(event.data);
        }
    });

    window.parent.postMessage({ type: "surveyjs-preview-ready" }, editorOrigin);
})();
