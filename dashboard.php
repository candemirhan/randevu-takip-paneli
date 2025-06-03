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
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel - Randevu Takip</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
    <style>
        /* Custom scrollbar for sidebar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #b91c1c; /* red-700 */
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-black text-white flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="bg-gray-900 w-64 flex flex-col">
        <div class="p-4 border-b border-gray-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-red-600">Randevu Paneli</h2>
            <button id="toggleSidebar" class="text-red-600 hover:text-red-400 focus:outline-none md:hidden">☰</button>
        </div>
        <nav class="flex-1 overflow-y-auto p-4">
            <button id="myAppointmentsBtn" class="w-full mb-4 bg-red-600 hover:bg-red-700 py-2 rounded font-semibold transition">Randevularım</button>
            <h3 class="mb-2 font-semibold border-b border-gray-700 pb-1">Üyeler</h3>
            <ul>
                <?php foreach ($members as $member): ?>
                    <li class="mb-1">
                        <button class="memberBtn w-full text-left px-2 py-1 rounded hover:bg-red-700 transition" data-id="<?php echo $member['id']; ?>">
                            <?php echo htmlspecialchars($member['username']); ?> (<?php echo $member['role']; ?>)
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <div class="p-4 border-t border-gray-700">
            <form method="POST" action="logout.php">
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 py-2 rounded font-semibold transition">Çıkış Yap</button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <main id="mainContent" class="flex-1 p-6 overflow-auto">
        <h1 class="text-3xl font-bold mb-6">Hoşgeldiniz, <?php echo htmlspecialchars($user['username']); ?></h1>
        <?php if (isAdmin()): ?>
            <section id="userManagement" class="mb-6">
                <h2 class="text-2xl font-semibold mb-4">Üyelik Oluşturma</h2>
                <form id="createUserForm" method="POST" action="create_user.php" class="space-y-4 max-w-md">
                    <div>
                        <label for="newUsername" class="block mb-1">Kullanıcı Adı</label>
                        <input type="text" id="newUsername" name="username" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
                    </div>
                    <div>
                        <label for="newPassword" class="block mb-1">Şifre</label>
                        <input type="password" id="newPassword" name="password" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
                    </div>
                    <div>
                        <label for="newRole" class="block mb-1">Kullanıcı Seviyesi</label>
                        <select id="newRole" name="role" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                            <option value="broker">Broker</option>
                            <option value="consultant">Danışman</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 py-2 px-4 rounded font-semibold transition">Üye Oluştur</button>
                </form>
            </section>
            <section id="userManagementActions" class="mb-6">
                <h2 class="text-2xl font-semibold mb-4">Üyelik Düzenleme</h2>
                <form id="editUserForm" method="POST" action="edit_user.php" class="space-y-4 max-w-md">
                    <div>
                        <label for="editUserSelect" class="block mb-1">Kullanıcı Seç</label>
                        <select id="editUserSelect" name="user_id" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600">
                            <option value="">Seçiniz</option>
                            <?php foreach ($members as $member): ?>
                                <?php if ($member['role'] !== 'admin'): ?>
                                    <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['username']); ?> (<?php echo $member['role']; ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="editPassword" class="block mb-1">Yeni Şifre</label>
                        <input type="password" id="editPassword" name="password" class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
                    </div>
                    <div>
                        <button type="submit" name="action" value="change_password" class="bg-red-600 hover:bg-red-700 py-2 px-4 rounded font-semibold transition mr-2">Şifre Değiştir</button>
                        <button type="submit" name="action" value="delete_user" class="bg-red-600 hover:bg-red-700 py-2 px-4 rounded font-semibold transition">Üyeyi Sil</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <p>Üyelik oluşturma ve düzenleme yetkiniz yoktur.</p>
        <?php endif; ?>

        <section id="contentArea">
            <!-- Content like calendar or appointment details will be loaded here -->
        </section>
    </main>

    <!-- Right chatbox -->
    <aside class="bg-gray-900 w-80 flex flex-col border-l border-gray-700">
        <div class="p-4 border-b border-gray-700">
            <div class="flex justify-between items-center">
                <h3 class="font-semibold text-red-600">Sohbet</h3>
                <select id="messageReceiver" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-sm focus:outline-none focus:border-red-600">
                    <option value="">Herkese</option>
                </select>
            </div>
        </div>
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3"></div>
        <div class="p-4 border-t border-gray-700">
            <div id="emojiPicker" class="hidden absolute bottom-20 right-4 bg-gray-800 p-2 rounded-lg border border-gray-700 grid grid-cols-8 gap-1">
                <!-- Emoji list will be populated by JavaScript -->
            </div>
            <form id="chatForm" class="flex space-x-2">
                <button type="button" id="emojiButton" class="bg-gray-800 hover:bg-gray-700 px-3 rounded">😊</button>
                <input type="text" id="chatInput" placeholder="Mesaj yazın..." class="flex-1 p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
                <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 rounded font-semibold transition">Gönder</button>
            </form>
        </div>
    </aside>

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

        // Sidebar toggle for mobile
        const toggleSidebarBtn = document.getElementById('toggleSidebar');
        const sidebar = document.querySelector('aside');
        toggleSidebarBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Load calendar or other content on clicking "Randevularım"
        const myAppointmentsBtn = document.getElementById('myAppointmentsBtn');
        const contentArea = document.getElementById('contentArea');

        myAppointmentsBtn.addEventListener('click', () => {
            fetch('appointments.php')
                .then(res => res.text())
                .then(html => {
                    contentArea.innerHTML = html;
                });
        });

        // Load member details or appointments on clicking member buttons
        document.querySelectorAll('.memberBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const userId = btn.getAttribute('data-id');
                fetch('member_appointments.php?user_id=' + userId)
                    .then(res => res.text())
                    .then(html => {
                        contentArea.innerHTML = html;
                    });
            });
        });

        // Enhanced chat functionality
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
            }).then(res => res.json())
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
                            div.className = 'p-2 rounded';
                            
                            let header = msg.sender;
                            if (msg.receiver) {
                                header += ' → ' + msg.receiver;
                            } else {
                                header += ' → Herkes';
                            }

                            div.innerHTML = `
                                <div class="text-xs text-gray-500">${header}</div>
                                <div class="break-words">${msg.message}</div>
                                <div class="text-xs text-gray-500 mt-1">${msg.time}</div>
                            `;
                            chatMessages.appendChild(div);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
        }

        loadUsers();
        loadMessages();
        setInterval(loadMessages, 5000);
    </script>
</body>
</html>
