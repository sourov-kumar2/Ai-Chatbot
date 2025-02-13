<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header("Content-Type: application/json");

    $api_key = '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';
    $url = "https://openrouter.ai/api/v1/chat/completions";

    $data = json_decode(file_get_contents("php://input"), true);
    $user_message = $data["message"] ?? "Hello";
    $selected_model = $data["model"] ?? "qwen/qwen-vl-plus:free";

    $messages = [
        ["role" => "system", "content" => "You are a chatbot created by Sourov. Your name is Angel. You are an AI assistant and will answer every query and provide codes if needed."],
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

    echo json_encode(["reply" => $reply]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #ffffff;
            --secondary-bg: #f7f7f8;
            --text-color: #374151;
            --accent-color: #10a37f;
            --user-message-bg: #ffffff;
            --bot-message-bg: #f7f7f8;
            --input-bg: #ffffff;
            --border-color: #e5e7eb;
        }

        body {
            background: var(--secondary-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 260px;
            background: var(--primary-bg);
            border-right: 1px solid var(--border-color);
            padding: 16px;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
        }

        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            padding-bottom: 100px;
        }

        .message {
            margin-bottom: 24px;
            padding: 16px 24px;
            border-radius: 8px;
            max-width: 80%;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            line-height: 1.5;
        }

        .message.user {
            background: var(--user-message-bg);
            margin-left: auto;
            border: 1px solid var(--border-color);
        }

        .message.bot {
            background: var(--bot-message-bg);
            margin-right: auto;
        }

        .input-container {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 800px;
            background: var(--primary-bg);
            padding: 24px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            border-top: 1px solid var(--border-color);
        }

        .model-selector {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .model-selector:hover {
            background: var(--secondary-bg);
        }

        .model-selector.active {
            border-color: var(--accent-color);
            background: var(--secondary-bg);
        }

        .send-button {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .send-button:hover {
            background: #0d8a6d;
        }

        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #6b7280;
            font-size: 0.9em;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="mb-4">
            <button class="send-button w-100" onclick="newChat()">+ New Chat</button>
        </div>
        <div class="model-selector active" data-model="qwen/qwen-vl-plus:free" onclick="selectModel(this)">
            <strong>Qwen</strong>
            <div class="text-muted" style="font-size: 0.9em">General purpose AI</div>
        </div>
        <div class="model-selector" data-model="deepseek/deepseek-r1-distill-llama-70b:free" onclick="selectModel(this)">
            <strong>Deepseek R1</strong>
            <div class="text-muted" style="font-size: 0.9em">Technical specialist</div>
        </div>
        <div class="model-selector" data-model="google/gemini-2.0-pro-exp-02-05:free" onclick="selectModel(this)">
            <strong>Gemini Pro</strong>
            <div class="text-muted" style="font-size: 0.9em">Creative assistant</div>
        </div>
    </div>

    <div class="main-content">
        <div class="chat-container" id="chat"></div>
        <div class="input-container">
            <div class="d-flex gap-2">
                <input type="text" id="userInput" class="form-control" 
                       placeholder="Message AI..." 
                       style="border: 1px solid var(--border-color); padding: 12px; border-radius: 8px">
                <button class="send-button" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="text-muted mt-2" style="font-size: 0.9em">
                AI can make mistakes. Verify important information.
            </div>
        </div>
    </div>

<script>
    let currentModel = 'qwen/qwen-vl-plus:free';
    let chatHistory = [];

    function selectModel(element) {
        document.querySelectorAll('.model-selector').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        currentModel = element.dataset.model;
    }

    async function sendMessage() {
        const userInput = document.getElementById('userInput');
        const text = userInput.value.trim();
        if (!text) return;

        // Add user message
        addMessage('user', text);
        userInput.value = '';

        // Add typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'message bot typing-indicator';
        typingIndicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        document.getElementById('chat').appendChild(typingIndicator);
        window.scrollTo(0, document.body.scrollHeight);

        try {
            const response = await fetch('chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, model: currentModel })
            });
            
            const data = await response.json();
            typingIndicator.remove();
            addMessage('bot', data.reply);
        } catch (error) {
            typingIndicator.remove();
            addMessage('bot', 'Sorry, there was an error processing your request.');
        }
    }

    function addMessage(role, content) {
        const chatDiv = document.getElementById('chat');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        messageDiv.innerHTML = content;
        chatDiv.appendChild(messageDiv);
        window.scrollTo(0, document.body.scrollHeight);
    }

    function newChat() {
        document.getElementById('chat').innerHTML = '';
        chatHistory = [];
    }

    // Handle Enter key
    document.getElementById('userInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
</script>
</body>
</html>