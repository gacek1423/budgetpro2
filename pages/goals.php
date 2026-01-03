<?php
// pages/goals.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('goals_title');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo __('goals_title'); ?></h2>
        <p class="text-gray-500 text-sm"><?php echo __('goals_subtitle'); ?></p>
    </div>
    <button onclick="openGoalModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow-lg flex items-center transition hover:scale-105">
        <i class="fas fa-plus mr-2"></i> <?php echo __('add_goal'); ?>
    </button>
</div>

<div id="goals-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="col-span-full text-center py-20 text-gray-400"><i class="fas fa-circle-notch fa-spin text-3xl"></i></div>
</div>

<div id="goalModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-dark-card rounded-2xl p-6 w-full max-w-md shadow-2xl border dark:border-slate-700">
        <h3 class="text-xl font-bold mb-4 dark:text-white"><?php echo __('new_goal'); ?></h3>
        <form id="goal-form" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('goal_name'); ?></label>
                <input type="text" name="name" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 outline-none" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('target_amount'); ?></label>
                    <input type="number" name="target_amount" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 font-bold" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('current_amount'); ?></label>
                    <input type="number" name="current_amount" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600" value="0">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('deadline'); ?></label>
                <input type="date" name="deadline" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600">
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('icon'); ?></label>
                    <select name="icon" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600">
                        <option value="fa-car">Car</option>
                        <option value="fa-home">Home</option>
                        <option value="fa-plane">Travel</option>
                        <option value="fa-laptop">Tech</option>
                        <option value="fa-piggy-bank">Savings</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('color'); ?></label>
                    <input type="color" name="color" class="h-12 w-full cursor-pointer rounded-lg bg-transparent" value="#3b82f6">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('goalModal').classList.add('hidden')" class="px-4 py-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg"><?php echo __('cancel'); ?></button>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow"><?php echo __('save'); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="depositModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-dark-card rounded-2xl p-6 w-full max-w-sm shadow-2xl border dark:border-slate-700">
        <h3 class="text-lg font-bold mb-2 dark:text-white"><?php echo __('deposit_title'); ?></h3>
        <p class="text-sm text-gray-500 mb-4"><?php echo __('deposit_desc'); ?></p>
        <input type="hidden" id="deposit-goal-id">
        <input type="number" id="deposit-amount" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 text-center text-2xl font-bold mb-4 outline-none" placeholder="0.00">
        <div class="grid grid-cols-3 gap-2 mb-4">
            <button onclick="setDeposit(50)" class="bg-gray-100 dark:bg-slate-800 py-2 rounded text-sm">+50</button>
            <button onclick="setDeposit(100)" class="bg-gray-100 dark:bg-slate-800 py-2 rounded text-sm">+100</button>
            <button onclick="setDeposit(200)" class="bg-gray-100 dark:bg-slate-800 py-2 rounded text-sm">+200</button>
        </div>
        <button onclick="submitDeposit()" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold shadow">
            <i class="fas fa-coins mr-2"></i> <?php echo __('pay'); ?>
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    // JS Logic (skrócona, bo identyczna, ale z użyciem formatCurrency)
    document.addEventListener('DOMContentLoaded', loadGoals);
    async function loadGoals() {
        const container = document.getElementById('goals-container');
        try {
            const res = await fetch('../api/goals_action.php?action=get');
            const data = await res.json();
            if(!data.success || data.data.length === 0) {
                container.innerHTML = `<div class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400"><p><?php echo __('no_data'); ?></p></div>`;
                return;
            }
            container.innerHTML = '';
            data.data.forEach(goal => {
                const percent = Math.min(100, Math.round((goal.current_amount / goal.target_amount) * 100));
                const isCompleted = percent >= 100;
                let timeMsg = "";
                if(goal.deadline) {
                    const daysLeft = Math.ceil((new Date(goal.deadline) - new Date()) / (1000 * 60 * 60 * 24));
                    timeMsg = daysLeft < 0 ? "<?php echo __('overdue'); ?>" : `${daysLeft} <?php echo __('days_left'); ?>`;
                }
                
                // Generowanie HTML karty (analogicznie do poprzednich wersji)
                const card = document.createElement('div');
                card.className = "bg-white dark:bg-dark-card rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 relative overflow-hidden group hover:shadow-md transition";
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-lg" style="background-color: ${goal.color}"><i class="fas ${goal.icon}"></i></div>
                        <button onclick="deleteGoal(${goal.id})" class="text-gray-300 hover:text-red-500 transition opacity-0 group-hover:opacity-100"><i class="fas fa-trash"></i></button>
                    </div>
                    <h3 class="font-bold text-xl text-gray-800 dark:text-white mb-1">${goal.name}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-6 font-medium uppercase tracking-wide">${timeMsg}</p>
                    <div class="flex items-center space-x-6 mb-6">
                        <div class="relative w-20 h-20 rounded-full flex items-center justify-center" style="background: conic-gradient(${goal.color} ${percent}%, #e2e8f0 0)">
                            <div class="w-16 h-16 bg-white dark:bg-dark-card rounded-full flex items-center justify-center text-sm font-bold text-gray-700 dark:text-gray-200">${percent}%</div>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs"><?php echo __('collected'); ?></p>
                            <p class="font-bold text-lg dark:text-white">${formatCurrency(goal.current_amount)}</p>
                            <p class="text-gray-400 text-xs mt-1">/ ${formatCurrency(goal.target_amount)}</p>
                        </div>
                    </div>
                    ${isCompleted ? 
                        `<div class="bg-green-100 text-green-700 py-2 rounded-lg text-center font-bold text-sm"><i class="fas fa-check-circle mr-1"></i> <?php echo __('achieved'); ?></div>` : 
                        `<button onclick="openDeposit(${goal.id})" class="w-full bg-slate-900 hover:bg-black dark:bg-slate-700 dark:hover:bg-slate-600 text-white py-3 rounded-xl font-bold transition shadow-lg flex justify-center items-center"><i class="fas fa-plus-circle mr-2"></i> <?php echo __('deposit'); ?></button>`
                    }
                `;
                container.appendChild(card);
            });
        } catch(e) {}
    }
    // Pozostałe funkcje (openGoalModal, submitDeposit itp.) bez zmian logicznych
    function openGoalModal() { document.getElementById('goalModal').classList.remove('hidden'); document.getElementById('goalModal').classList.add('flex'); }
    function setDeposit(val) { document.getElementById('deposit-amount').value = val; }
    function openDeposit(id) { document.getElementById('deposit-goal-id').value = id; document.getElementById('depositModal').classList.remove('hidden'); document.getElementById('depositModal').classList.add('flex'); }
    
    document.getElementById('goal-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('../api/goals_action.php?action=add', { method: 'POST', body: JSON.stringify(Object.fromEntries(new FormData(e.target))) });
        if((await res.json()).success) { showToast('OK', 'success'); setTimeout(() => location.reload(), 500); }
    });
    
    async function submitDeposit() {
        const id = document.getElementById('deposit-goal-id').value;
        const amount = document.getElementById('deposit-amount').value;
        const res = await fetch(`../api/goals_action.php?action=deposit&id=${id}&amount=${amount}`);
        const data = await res.json();
        if(data.success) {
            document.getElementById('depositModal').classList.add('hidden');
            loadGoals();
            if(data.completed) { confetti(); showToast('<?php echo __('congrats'); ?>', 'success'); }
        }
    }
    async function deleteGoal(id) { if(confirm('Delete?')) { await fetch(`../api/goals_action.php?action=delete&id=${id}`); loadGoals(); } }
    function formatCurrency(amount) {
        // Używamy formatowania z PHP poprzez AJAX lub prostą walutę
        return parseFloat(amount).toLocaleString('pl-PL', {minimumFractionDigits: 2}) + ' <?php echo __('currency_symbol'); ?>'; 
    }
</script>
<?php require_once '../includes/footer.php'; ?>