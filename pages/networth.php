<?php
// pages/networth.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('networth_title');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="space-y-6">
        
        <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow border dark:border-slate-700">
            <h3 class="font-bold text-lg dark:text-white mb-4"><?php echo __('update_status'); ?></h3>
            <p class="text-sm text-gray-500 mb-4"><?php echo __('update_desc'); ?></p>
            
            <form id="networth-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-green-600 mb-1"><?php echo __('assets'); ?></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="assets" id="inp-assets" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600 dark:text-white font-bold outline-none focus:ring-2 focus:ring-green-500" placeholder="0.00" required>
                        <span class="absolute right-3 top-2 text-gray-400 text-sm"><?php echo __('currency_symbol'); ?></span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-red-600 mb-1"><?php echo __('liabilities'); ?></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="liabilities" id="inp-liabilities" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600 dark:text-white font-bold outline-none focus:ring-2 focus:ring-red-500" placeholder="0.00" required>
                        <span class="absolute right-3 top-2 text-gray-400 text-sm"><?php echo __('currency_symbol'); ?></span>
                    </div>
                </div>
                
                <div class="pt-4 border-t dark:border-slate-700 mt-2">
                    <div class="flex justify-between text-sm font-bold dark:text-white mb-4">
                        <span><?php echo __('current_networth'); ?>:</span>
                        <span id="preview-net" class="text-lg">0.00 <?php echo __('currency_symbol'); ?></span>
                    </div>
                    <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 dark:bg-blue-600 dark:hover:bg-blue-700 text-white py-2.5 rounded-lg shadow transition font-bold">
                        <?php echo __('save'); ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 dark:bg-slate-800/50 p-4 rounded-xl border border-blue-100 dark:border-slate-700 text-sm dark:text-slate-300">
            <h4 class="font-bold mb-1"><i class="fas fa-info-circle mr-1 text-blue-500"></i> <?php echo __('networth_help'); ?></h4>
            <p><?php echo __('networth_help_desc'); ?></p>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-dark-card p-6 rounded-xl shadow border dark:border-slate-700 flex flex-col">
        <h3 class="font-bold text-lg dark:text-white mb-6"><?php echo __('history_chart'); ?></h3>
        <div class="flex-1 w-full relative min-h-[300px]">
            <canvas id="nwChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const symbol = '<?php echo __('currency_symbol'); ?>';
    
    // 1. Kalkulator w formularzu (na żywo)
    const inpAssets = document.getElementById('inp-assets');
    const inpLiab = document.getElementById('inp-liabilities');
    const preview = document.getElementById('preview-net');

    function calcPreview() {
        const a = parseFloat(inpAssets.value) || 0;
        const l = parseFloat(inpLiab.value) || 0;
        const net = a - l;
        
        // Formatowanie waluty
        const formatted = net.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ' + symbol;
        
        preview.textContent = formatted;
        
        // Kolory
        if(net >= 0) {
            preview.classList.remove('text-red-600');
            preview.classList.add('text-green-600');
        } else {
            preview.classList.remove('text-green-600');
            preview.classList.add('text-red-600');
        }
    }
    
    inpAssets.addEventListener('input', calcPreview);
    inpLiab.addEventListener('input', calcPreview);

    // 2. Zapisywanie do bazy
    document.getElementById('networth-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('../api/networth_action.php?action=add', {
                method: 'POST', body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                showToast('<?php echo __('save'); ?>!', 'success');
                loadChart(); // Odśwież wykres
            } else {
                showToast(data.error || 'Error', 'error');
            }
        } catch(e) { showToast('Błąd połączenia', 'error'); }
    });

    // 3. Wykres Historii
    async function loadChart() {
        try {
            const res = await fetch('../api/networth_action.php?action=get_history');
            const json = await res.json();
            
            if(!json.success || !json.data || json.data.length === 0) {
                // Jeśli brak danych, można pokazać komunikat, ale zostawiamy pusty wykres
                return;
            }

            const labels = json.data.map(d => d.date_formatted);
            const netData = json.data.map(d => d.net_worth);
            const assetData = json.data.map(d => d.assets);

            const ctx = document.getElementById('nwChart');
            
            // Kolory dla Dark Mode
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#334155' : '#e2e8f0';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            if(window.myNwChart) window.myNwChart.destroy();

            window.myNwChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '<?php echo __('current_networth'); ?>',
                            data: netData,
                            borderColor: '#3b82f6', // blue-500
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: '<?php echo __('assets'); ?>',
                            data: assetData,
                            borderColor: '#10B981', // emerald-500
                            borderDash: [5, 5],
                            borderWidth: 2,
                            tension: 0.3,
                            fill: false,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { labels: { color: textColor } } 
                    },
                    scales: {
                        y: { 
                            grid: { color: gridColor },
                            ticks: { color: textColor } 
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { color: textColor } 
                        }
                    }
                }
            });
            
            // Wypełnij inputy ostatnimi danymi (żeby użytkownik nie musiał wpisywać od nowa)
            const last = json.data[json.data.length - 1];
            inpAssets.value = last.assets;
            inpLiab.value = last.liabilities;
            calcPreview();

        } catch(e) { console.error("Chart load error", e); }
    }

    // Załaduj przy starcie
    loadChart();
</script>

<?php require_once '../includes/footer.php'; ?>