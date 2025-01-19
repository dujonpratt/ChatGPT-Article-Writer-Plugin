jQuery(document).ready(function ($) {
    const promptsList = [];

    function renderPrompts() {
        const $list = $("#prompts-list");
        $list.empty();

        promptsList.forEach((prompt, index) => {
            $list.append(`
                <tr data-index="${index}">
                    <td>${prompt.postTypeLabel}</td>
                    <td>${prompt.prompt}</td>
                    <td>
                        <button type="button" class="delete-prompt button button-link-delete">Delete</button>
                    </td>
                </tr>
            `);
        });
    }

    $("#add-prompt").click(function () {
        const postType = $("#post-type").val();
        const postTypeLabel = $("#post-type option:selected").text();
        const prompt = $("#article-prompt").val().trim();

        if (!prompt) {
            alert("Please enter a prompt.");
            return;
        }

        promptsList.push({ postType, prompt, postTypeLabel });
        renderPrompts();
        $("#article-prompt").val("");
    });

    $("#prompts-list").on("click", ".delete-prompt", function () {
        const index = $(this).closest("tr").data("index");
        promptsList.splice(index, 1);
        renderPrompts();
    });

    $("#generate-articles").click(function () {
        if (promptsList.length === 0) {
            alert("Please add at least one prompt.");
            return;
        }

        $.post(chatgptArticleWriter.ajax_url, {
            action: "generate_articles",
            prompts_list: promptsList,
            security: chatgptArticleWriter.nonce,
        })
            .done(function (response) {
                if (response.success) {
                    alert("Articles generated successfully!");
                    console.log(response.data);
                } else {
                    alert("Error: " + response.data);
                }
            })
            .fail(function (error) {
                alert("AJAX Error: " + error.statusText);
            });
    });
});
