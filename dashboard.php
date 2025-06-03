<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Function to check user role
function isAdmin() {
    global $user;
    return $user['role'] === 'admin';
}

function isBroker() {
    global $user;
    return $user['role'] === 'broker';
}

function isConsultant() {
    global $user;
    return $user['role'] === 'consultant';
}

// Fetch members for sidebar
$stmt = $pdo->query("SELECT id, username, role FROM users ORDER BY username");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr" class="h-full bg-black">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - Randevu Takip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #dc2626;
            border-radius: 4px;
        }
    </style>
</head>
<body class="h-full bg-black text-white">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 flex flex-col border-r border-gray-800">
            <div class="p-4 border-b border-gray-800">
                <h1 class="text-xl font-bold text-red-600">Randevu Paneli</h1>
            </div>
            
            <nav class="flex-1 overflow-y-auto p-4">
                <button id="myAppointmentsBtn" class="w-full mb-6 bg-red-600 hover:bg-red-700 py-2 px-4 rounded-md font-semibold transition">
                    Randevularım
                </button>

                <div class="mb-4">
                    <h2 class="text-lg font-semibold mb-2">Üyeler</h2>
                    <ul class="space-y-1">
                        <?php foreach ($members as $member): ?>
                            <li>
                                <button class="memberBtn w-full text-left px-3 py-2 rounded hover:bg-gray-800 transition" data-id="<?php echo $member['id']; ?>">
                                    <?php echo htmlspecialchars($member['username']); ?>
                                    <span class="text-sm text-gray-400">(<?php echo $member['role']; ?>)</span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <form action="logout.php" method="POST">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 py-2 px-4 rounded-md font-semibold transition">
                        Çıkış Yap
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto bg-black p-6">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold mb-8">Hoşgeldiniz, <?php echo htmlspecialchars($user['username']); ?></h1>

                <?php if (isAdmin()): ?>
                <div class="space-y-8">
                    <!-- User Creation -->
                    <section class="bg-gray-900 rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Üyelik Oluşturma</h2>
                        <form id="createUserForm" action="create_user.php" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Kullanıcı Adı</label>
                                <input type="text" name="username" required class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Şifre</label>
                                <input type="password" name="password" required class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Kullanıcı Seviyesi</label>
                                <select name="role" required class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                                    <option value="broker">Broker</option>
                                    <option value="consultant">Danışman</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-md font-semibold transition">
                                Üye Oluştur
                            </button>
                        </form>
                    </section>

                    <!-- User Management -->
                    <section class="bg-gray-900 rounded-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Üyelik Düzenleme</h2>
                        <form id="editUserForm" action="edit_user.php" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Kullanıcı Seç</label>
                                <select name="user_id" required class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($members as $member): ?>
                                        <?php if ($member['role'] !== 'admin'): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['username']); ?> (<?php echo $member['role']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Yeni Şifre</label>
                                <input type="password" name="password" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                            </div>
                            <div class="flex space-x-4">
                                <button type="submit" name="action" value="change_password" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-md font-semibold transition">
                                    Şifre Değiştir
                                </button>
                                <button type="submit" name="action" value="delete_user" class="bg-red-600 hover:bg-red-700 px-6 py-2 rounded-md font-semibold transition">
                                    Üyeyi Sil
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
                <?php endif; ?>

                <div id="contentArea" class="mt-8">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
        </main>

        <!-- Chat sidebar -->
        <aside class="w-80 bg-gray-900 flex flex-col border-l border-gray-800">
            <div class="p-4 border-b border-gray-800">
                <div class="flex justify-between items-center">
                    <h3 class="font-semibold text-red-600">Sohbet</h3>
                    <select id="messageReceiver" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm focus:outline-none focus:border-red-600">
                        <option value="">Herkese</option>
                    </select>
                </div>
            </div>

            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3"></div>

            <div class="p-4 border-t border-gray-800">
                <div id="emojiPicker" class="hidden absolute bottom-20 right-4 bg-gray-800 p-2 rounded-lg border border-gray-700 grid grid-cols-8 gap-1">
                    <!-- Emoji list will be populated by JavaScript -->
                </div>
                <form id="chatForm" class="flex space-x-2">
                    <button type="button" id="emojiButton" class="bg-gray-800 hover:bg-gray-700 px-3 rounded">😊</button>
                    <input type="text" id="chatInput" placeholder="Mesaj yazın..." class="flex-1 px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 rounded font-semibold transition">
                        Gönder
                    </button>
                </form>
            </div>
        </aside>
    </div>

    <script>
        // Emoji picker setup
        const emojis = ['😊', '😂', '❤️', '👍', '😎', '🎉', '👋', '😄', '🤔', '😅', '😍', '🙌', '👏', '🌟', '💪', '🤝'];
        const emojiPicker = document.getElementById('emojiPicker');
        const emojiButton = document.getElementById('emojiButton');
        const chatInput = document.getElementById('chatInput');

        // Populate emoji picker
        emojis.forEach(emoji => {
            const button = document.createElement('button');
            button.textContent = emoji;
            button.className = 'hover:bg-gray-700 p-1 rounded';
            button.onclick = () => {
                chatInput.value += emoji;
                emojiPicker.classList.add('hidden');
            };
            emojiPicker.appendChild(button);
        });

        // Toggle emoji picker
        emojiButton.onclick = () => {
            emojiPicker.classList.toggle('hidden');
        };

        // Close emoji picker when clicking outside
        document.addEventListener('click', (e) => {
            if (!emojiPicker.contains(e.target) && e.target !== emojiButton) {
                emojiPicker.classList.add('hidden');
            }
        });

        // Load calendar or other content on clicking "Randevularım"
        document.getElementById('myAppointmentsBtn').addEventListener('click', () => {
            fetch('appointments.php')
                .then(res => res.text())
                .then(html => {
                    document.getElementById('contentArea').innerHTML = html;
                });
        });

        // Load member details on clicking member buttons
        document.querySelectorAll('.memberBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const userId = btn.getAttribute('data-id');
                fetch('member_appointments.php?user_id=' + userId)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('contentArea').innerHTML = html;
                    });
            });
        });

        // Chat functionality
        const chatForm = document.getElementById('chatForm');
        const chatMessages = document.getElementById('chatMessages');
        const messageReceiver = document.getElementById('messageReceiver');

        // Load users for recipient dropdown
        function loadUsers() {
            fetch('load_messages.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.users) {
                        messageReceiver.innerHTML = '<option value="">Herkese</option>';
                        data.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = user.username + ' (' + user.role + ')';
                            messageReceiver.appendChild(option);
                        });
                    }
                });
        }

        chatForm.addEventListener('submit', e => {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (!message) return;

            const formData = new FormData();
            formData.append('message', message);
            if (messageReceiver.value) {
                formData.append('receiver_id', messageReceiver.value);
            }

            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    chatInput.value = '';
                    loadMessages();
                }
            });
        });

        function loadMessages() {
            fetch('load_messages.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        chatMessages.innerHTML = '';
                        data.messages.forEach(msg => {
                            const div = document.createElement('div');
                            div.className = 'p-2 rounded bg-gray-800';
                            
                            let header = msg.sender;
                            if (msg.receiver) {
                                header += ' → ' + msg.receiver;
                            } else {
                                header += ' → Herkes';
                            }

                            div.innerHTML = `
                                <div class="text-xs text-gray-400">${header}</div>
                                <div class="break-words">${msg.message}</div>
                                <div class="text-xs text-gray-400 mt-1">${msg.time}</div>
                            `;
                            chatMessages.appendChild(div);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
        }

        // Initial load
        loadUsers();
        loadMessages();
        setInterval(loadMessages, 5000);

        // Load appointments by default
        document.getElementById('myAppointmentsBtn').click();
    </script>
</body>
</html>
