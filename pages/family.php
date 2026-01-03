<?php
$pageTitle = "Rodzina i Współdzielenie";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="max-w-4xl mx-auto">
    
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white mb-8 shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold mb-2">Budżet Rodzinny</h2>
                <p class="opacity-90">Prowadźcie finanse razem. Zaproś domowników.</p>
            </div>
            <div class="hidden md:block text-6xl opacity-20">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border dark:border-slate-700 p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Twoi Domownicy</h3>
            <button onclick="document.getElementById('inviteModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow transition text-sm font-bold">
                <i class="fas fa-user-plus mr-2"></i> Zaproś
            </button>
        </div>

        <div id="family-list" class="space-y-3">
            <p class="text-gray-400 text-center py-4">Ładowanie...</p>
        </div>
    </div>

    <div class="bg-blue-50 dark:bg-slate-800/50 p-4 rounded-lg border border-blue-100 dark:border-slate-700 text-sm text-gray-600 dark:text-gray-300">
        <i class="fas fa-info-circle mr-2 text-blue-500"></i>
        W tej wersji systemu, zaproszenie użytkownika pozwala Ci widzieć go na liście. Pełne współdzielenie transakcji wymagałoby przebudowy silnika bazy danych na obsługę "Gospodarstw Domowych" (Household ID).
    </div>
</div>

<div id="inviteModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4 flex">
    <div class="bg-white dark:bg-dark-card rounded-xl p-6 w-full max-w-sm shadow-2xl">
        <h3 class="font-bold text-lg mb-4 dark:text-white">Wyślij zaproszenie</h3>
        <form id="invite-form">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Adres Email</label>
            <input type="email" name="email" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 mb-4" placeholder="partner@dom.pl" required>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('inviteModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Anuluj</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold">Wyślij</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadFamily);

    async function loadFamily() {
        const container = document.getElementById('family-list');
        const res = await fetch('../api/family_action.php');
        const data = await res.json();
        
        container.innerHTML = '';

        if(!data.data || data.data.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-400 py-6">Nikogo jeszcze nie zaprosiłeś.</p>';
            return;
        }

        data.data.forEach(m => {
            const isPending = m.status === 'pending';
            const statusBadge = isPending 
                ? '<span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold">Oczekuje</span>' 
                : '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Aktywny</span>';
            
            const name = m.member_name ? m.member_name : m.invitee_email;

            const div = document.createElement('div');
            div.className = "flex items-center justify-between p-4 border rounded-lg hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800 transition";
            div.innerHTML = `
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-gray-500">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="font-bold dark:text-white">${name}</div>
                        <div class="text-xs text-gray-400">${m.invitee_email}</div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    ${statusBadge}
                    <button onclick="removeMember(${m.id})" class="text-gray-300 hover:text-red-500 transition"><i class="fas fa-trash"></i></button>
                </div>
            `;
            container.appendChild(div);
        });
    }

    document.getElementById('invite-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('../api/family_action.php?action=invite', {
                method: 'POST', body: JSON.stringify(Object.fromEntries(formData))
            });
            const d = await res.json();
            if(d.success) {
                showToast('Zaproszenie wysłane!', 'success');
                document.getElementById('inviteModal').classList.add('hidden');
                loadFamily();
            } else {
                showToast(d.error, 'error');
            }
        } catch(e) { showToast('Błąd', 'error'); }
    });

    async function removeMember(id) {
        if(confirm('Usunąć tę osobę z rodziny?')) {
            await fetch(`../api/family_action.php?action=delete&id=${id}`);
            loadFamily();
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>