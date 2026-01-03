<?php
// pages/categories.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('menu_categories');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo __('menu_categories'); ?></h2>
        <p class="text-gray-500 text-sm dark:text-gray-400">Zarządzaj kategoriami wydatków i przychodów.</p>
    </div>
    <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow-lg flex items-center transition transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i> <?php echo __('add'); ?>
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b dark:border-slate-700 bg-red-50 dark:bg-red-900/10 flex justify-between items-center">
            <h3 class="font-bold text-red-600 dark:text-red-400"><i class="fas fa-arrow-down mr-2"></i> <?php echo __('filter_expense'); ?></h3>
            <span class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full font-bold count-expense">0</span>
        </div>
        <div id="list-expense" class="divide-y dark:divide-slate-700 max-h-[600px] overflow-y-auto custom-scrollbar p-2">
            <div class="text-center py-10 text-gray-400"><i class="fas fa-circle-notch fa-spin"></i></div>
        </div>
    </div>

    <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b dark:border-slate-700 bg-green-50 dark:bg-green-900/10 flex justify-between items-center">
            <h3 class="font-bold text-green-600 dark:text-green-400"><i class="fas fa-arrow-up mr-2"></i> <?php echo __('filter_income'); ?></h3>
            <span class="text-xs bg-green-100 text-green-600 px-2 py-1 rounded-full font-bold count-income">0</span>
        </div>
        <div id="list-income" class="divide-y dark:divide-slate-700 max-h-[600px] overflow-y-auto custom-scrollbar p-2">
            <div class="text-center py-10 text-gray-400"><i class="fas fa-circle-notch fa-spin"></i></div>
        </div>
    </div>

</div>

<div id="catModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-dark-card rounded-2xl p-6 w-full max-w-md shadow-2xl border dark:border-slate-700 transform transition-all scale-100">
        <h3 id="modal-title" class="text-xl font-bold mb-4 dark:text-white"><?php echo __('new_goal'); // Używamy "Nowy..." ?></h3>
        
        <form id="cat-form" class="space-y-4">
            <input type="hidden" name="id" id="cat-id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('type'); ?></label>
                    <select name="type" id="cat-type" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="expense"><?php echo __('filter_expense'); ?></option>
                        <option value="income"><?php echo __('filter_income'); ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('color'); ?></label>
                    <input type="color" name="color" id="cat-color" class="h-[46px] w-full cursor-pointer rounded-lg bg-transparent border dark:border-slate-600 p-1" value="#3b82f6">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nazwa Kategorii</label>
                <input type="text" name="name" id="cat-name" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="np. Jedzenie, Praca">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('icon'); ?></label>
                <div class="grid grid-cols-6 gap-2 max-h-32 overflow-y-auto border p-2 rounded-lg dark:border-slate-600 custom-scrollbar">
                    <?php 
                    $icons = ['fa-shopping-cart', 'fa-utensils', 'fa-car', 'fa-home', 'fa-heartbeat', 'fa-plane', 'fa-gamepad', 'fa-graduation-cap', 'fa-gift', 'fa-paw', 'fa-briefcase', 'fa-money-bill-wave', 'fa-bus', 'fa-plug', 'fa-mobile-alt', 'fa-tshirt', 'fa-baby', 'fa-tools'];
                    foreach($icons as $ic): ?>
                        <label class="cursor-pointer flex justify-center items-center h-10 w-10 rounded hover:bg-slate-100 dark:hover:bg-slate-700">
                            <input type="radio" name="icon" value="<?php echo $ic; ?>" class="peer hidden">
                            <i class="fas <?php echo $ic; ?> text-gray-400 peer-checked:text-blue-600 text-lg"></i>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition"><?php echo __('cancel'); ?></button>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow transition"><?php echo __('save'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', loadCategories);

    // 1. Ładowanie Kategorii
    async function loadCategories() {
        const listExpense = document.getElementById('list-expense');
        const listIncome = document.getElementById('list-income');
        
        try {
            const res = await fetch('../api/categories_action.php?action=get');
            const data = await res.json();
            
            // Wyczyść listy
            listExpense.innerHTML = '';
            listIncome.innerHTML = '';
            
            let countExp = 0;
            let countInc = 0;

            if(!data.success || data.data.length === 0) {
                listExpense.innerHTML = '<p class="text-center text-gray-400 py-4"><?php echo __('no_data'); ?></p>';
                listIncome.innerHTML = '<p class="text-center text-gray-400 py-4"><?php echo __('no_data'); ?></p>';
                return;
            }

            data.data.forEach(cat => {
                const isExp = cat.type === 'expense';
                if(isExp) countExp++; else countInc++;

                const html = `
                    <div class="flex items-center justify-between p-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition group rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white shadow-sm" style="background-color: ${cat.color}">
                                <i class="fas ${cat.icon}"></i>
                            </div>
                            <span class="font-bold text-gray-700 dark:text-gray-200">${cat.name}</span>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick='editCategory(${JSON.stringify(cat)})' class="text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 p-2 rounded-full"><i class="fas fa-pen"></i></button>
                            <button onclick="deleteCategory(${cat.id})" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 p-2 rounded-full"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `;

                if(isExp) listExpense.insertAdjacentHTML('beforeend', html);
                else listIncome.insertAdjacentHTML('beforeend', html);
            });

            document.querySelector('.count-expense').innerText = countExp;
            document.querySelector('.count-income').innerText = countInc;

        } catch(e) { console.error(e); }
    }

    // 2. Obsługa Modala
    const modal = document.getElementById('catModal');
    const form = document.getElementById('cat-form');

    function openModal(isEdit = false) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('modal-title').innerText = isEdit ? '<?php echo __('edit'); ?>' : '<?php echo __('add'); ?>';
        if(!isEdit) {
            form.reset();
            document.getElementById('cat-id').value = '';
            // Domyślna ikona check
            document.querySelector('input[name="icon"][value="fa-shopping-cart"]').checked = true;
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editCategory(cat) {
        openModal(true);
        document.getElementById('cat-id').value = cat.id;
        document.getElementById('cat-name').value = cat.name;
        document.getElementById('cat-type').value = cat.type;
        document.getElementById('cat-color').value = cat.color;
        
        // Zaznacz ikonę
        const radio = document.querySelector(`input[name="icon"][value="${cat.icon}"]`);
        if(radio) radio.checked = true;
    }

    // 3. Zapisywanie (Add/Edit)
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const action = formData.get('id') ? 'update' : 'add';

        try {
            const res = await fetch(`../api/categories_action.php?action=${action}`, {
                method: 'POST', body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                closeModal();
                loadCategories();
                showToast('<?php echo __('save'); ?>!', 'success');
            } else {
                showToast(data.error || 'Error', 'error');
            }
        } catch(e) { showToast('Błąd połączenia', 'error'); }
    });

    // 4. Usuwanie
    async function deleteCategory(id) {
        if(confirm('<?php echo __('delete_confirm'); ?>\nUwaga: To usunie kategorię z powiązanych transakcji!')) {
            try {
                const res = await fetch('../api/categories_action.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${id}`
                });
                const data = await res.json();
                if(data.success) {
                    loadCategories();
                    showToast('Usunięto', 'success');
                }
            } catch(e) { showToast('Error', 'error'); }
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>