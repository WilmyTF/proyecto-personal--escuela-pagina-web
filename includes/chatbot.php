<?php
// Verificar que el archivo se está incluyendo

error_log('Chatbot incluido correctamente');
?>

<!-- Cargar el CSS del chatbot -->
<link rel="stylesheet" href="../css/chatbot.css">

<!-- Chatbot -->
<div class="chatbot-container">
    <div class="chatbot-box" id="chatbot">
        <div class="chat-header">
            <h3>Asistente Virtual</h3>
            <button class="close-chat" onclick="toggleChat()">×</button>
        </div>
        <div class="chat-messages" id="chat-messages">
            <div class="message bot">
                ¡Hola! ¿En qué puedo ayudarte?
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="user-input" placeholder="Escribe tu mensaje...">
            <button onclick="sendMessage()">Enviar</button>
        </div>
    </div>
    <button class="chat-toggle" onclick="toggleChat()">
        <i class="fas fa-comments"></i>
    </button>
</div>

<script>
// Verificar que el script se está cargando
console.log('Script del chatbot cargado');

function toggleChat() {
    const chatbot = document.getElementById('chatbot');
    const chatToggle = document.querySelector('.chat-toggle');
    
    if (chatbot.style.display === 'none' || chatbot.style.display === '') {
        chatbot.style.display = 'flex';
        chatToggle.style.display = 'none';
    } else {
        chatbot.style.display = 'none';
        chatToggle.style.display = 'flex';
    }
}

function sendMessage() {
    const input = document.getElementById('user-input');
    const message = input.value.trim();
    
    if (message) {
        const chatMessages = document.getElementById('chat-messages');
        
        // Agregar mensaje del usuario
        const userMessage = document.createElement('div');
        userMessage.className = 'message user';
        userMessage.textContent = message;
        chatMessages.appendChild(userMessage);
        
        // Limpiar el campo de entrada
        input.value = '';
        
        // Enviar la pregunta al servidor
        fetch('chatbot_responses.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'question=' + encodeURIComponent(message)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Agregar la respuesta del bot
            const botMessage = document.createElement('div');
            botMessage.className = 'message bot';
            botMessage.textContent = data.response;
            chatMessages.appendChild(botMessage);
            
            // Auto-scroll al último mensaje
            chatMessages.scrollTop = chatMessages.scrollHeight;
        })
        .catch(error => {
            console.error('Error:', error);
            // Mensaje de error
            const errorMessage = document.createElement('div');
            errorMessage.className = 'message bot';
            errorMessage.textContent = 'Prueba docente, diríjase a gestión de personal - docente para consultar su horario';
            chatMessages.appendChild(errorMessage);
            
            // Auto-scroll al último mensaje
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }
}

// Permitir enviar mensaje con Enter
document.getElementById('user-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Inicializar el chatbot como oculto
document.addEventListener('DOMContentLoaded', function() {
    const chatbot = document.getElementById('chatbot');
    chatbot.style.display = 'none';
});
</script> 