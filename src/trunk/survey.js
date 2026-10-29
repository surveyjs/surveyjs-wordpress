(function () {
    function initSurvey(configElement) {
        if (configElement.dataset.initialized) {
            return;
        }
        configElement.dataset.initialized = "true";
        const config = JSON.parse(configElement.textContent);
        const honeypot = configElement.parentElement.querySelector(".wp-sjs-hp input");
        const survey = new Survey.Model(config.json);
        if (config.theme) {
            survey.applyTheme(config.theme);
        }
        window["survey" + config.id] = survey;

        survey.onComplete.add((sender, options) => {
            options.showSaveInProgress();
            fetch(config.saveResultUrl, {
                method: "POST",
                body: new URLSearchParams({ SurveyId: config.id, Json: JSON.stringify(sender.data), Website: honeypot ? honeypot.value : "", _wpnonce: config.saveResultNonce })
            })
                .then((response) => response.ok ? options.showSaveSuccess() : response.text().then((text) => options.showSaveError(text)))
                .catch(() => options.showSaveError());
        });

        survey.onUploadFiles.add((_, options) => {
            const formData = new FormData();
            options.files.forEach((file) => {
                formData.append(file.name, file);
            });
            formData.append("_wpnonce", config.uploadFilesNonce);
            formData.append("SurveyId", config.id);

            fetch(config.uploadFilesUrl, {
                method: "POST",
                body: formData
            })
                .then((response) => response.json())
                .then((data) => {
                    options.callback(
                        options.files.map((file) => {
                            return {
                                file: file,
                                content: data[file.name]
                            };
                        })
                    );
                })
                .catch((error) => {
                    console.error("Error: ", error);
                    options.callback([], [ 'An error occurred during file upload.' ]);
                });
        });

        function deleteFile(fileURL, options) {
            try {
                fetch(`${config.deleteFileUrl}&name=${encodeURIComponent(fileURL)}`);
            } catch (error) {
                options.callback("error");
            }
        }

        survey.onClearFiles.add((_, options) => {
            if (!options.value || options.value.length === 0) {
                options.callback("success");
                return;
            }

            if (!options.fileName && !!options.value) {
                for (const item of options.value) {
                    deleteFile(item.content, options);
                }
            } else {
                const fileToRemove = options.value.find(
                    (item) => item.name === options.fileName
                );
                if (fileToRemove) {
                    deleteFile(fileToRemove.content, options);
                } else {
                    console.error(`File with name ${options.fileName} is not found`);
                }
            }
            options.callback("success");
        });

        SurveyUI.renderSurvey(survey, configElement.parentElement.querySelector(".wp-sjs-survey"));
    }

    window.wpSurveyJSInit = initSurvey;
    document.querySelectorAll(".wp-sjs-survey-config").forEach(initSurvey);
})();
