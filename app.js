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
        if (data.reply) {
            addMessage('bot', data.reply);
        } else {
            addMessage('bot', 'Sorry, there was an error processing your request.');
        }
    } catch (error) {
        typingIndicator.remove();
        addMessage('bot', 'An error occurred. Please try again.');
    }
}

// function copyToClipboard(button) {
//     const codeElement = button.previousElementSibling;
//     const textArea = document.createElement("textarea");
//     textArea.value = codeElement.innerText;
//     document.body.appendChild(textArea);
//     textArea.select();
//     document.execCommand("copy");
//     document.body.removeChild(textArea);
//     button.innerText = "Copied!";
//     setTimeout(() => button.innerText = "Copy", 2000);
// }

function addMessage(role, content) {
    const chatDiv = document.getElementById('chat');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role}`;
    messageDiv.innerHTML = content;
    chatDiv.appendChild(messageDiv);

    // Highlight code blocks with Prism
    Prism.highlightAllUnder(messageDiv);

    // // Add event listeners to copy buttons
    // document.querySelectorAll('.prism-copy-button').forEach(button => {
    //     button.addEventListener('click', function() {
    //         copyToClipboard(this);
    //     });
    // });

    window.scrollTo(0, document.body.scrollHeight);
}

function newChat() {
    document.getElementById('chat').innerHTML = '';
    chatHistory = [];
}

document.getElementById('userInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});