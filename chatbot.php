<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Content-Type: application/json");

    $api_key = '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';
    $url = "https://openrouter.ai/api/v1/chat/completions";

    $data = json_decode(file_get_contents("php://input"), true);
    $user_message = $data["message"] ?? "Hello";
    $selected_model = $data["model"] ?? "qwen/qwen-vl-plus:free";

    $messages = [
        ["role" => "system", "content" => "You are a chatbot named Angel created by Sourov.
         You are an AI assistant that answers questions and provides code when needed.
         Your goal is to provide simple code and explain it with bullet points.
         You can provide code in any language, but use the language the user requests.
         Sometimes, you may need to **bold** and <mark>highlight</mark> important points."],
        ["role" => "user", "content" => $user_message]
    ];

    $request_data = [
        "model" => $selected_model,
        "messages" => $messages
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($request_data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            "Authorization: Bearer $api_key"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $bot_response = json_decode($response, true);
    $reply = $bot_response["choices"][0]["message"]["content"] ?? "Sorry, I couldn't understand.";

    // Format code snippets properly with copy button
    $reply = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function ($matches) {
        $language = $matches[1] ?? 'plaintext'; // Default to plaintext if no language specified
        $code = htmlspecialchars(trim($matches[2]));

        return "<pre class='language-$language'><code class='language-$language'>$code</code></pre>";
    }, $reply);

    // Format inline code (single `code` snippets)
    $reply = preg_replace('/`(.*?)`/', '<code class="inline-code">$1</code>', $reply);

    // Format bullet points
    $reply = preg_replace('/^- (.*?)(\n|$)/m', '<li>$1</li>', $reply);
    $reply = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $reply);

    // Format bold text
    $reply = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $reply);

    // Format headings
    $reply = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $reply);
    $reply = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $reply);
    $reply = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $reply);

    // // Format italic text
    // $reply = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $reply);



   
    echo json_encode(["reply" => $reply]);
    exit;
}
?>
