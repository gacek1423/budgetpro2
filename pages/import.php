<?php
$pageTitle = "Import CSV";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="p-8">
    <h2 class="text-2xl font-bold dark:text-white mb-2">Import Transakcji</h2>
    <div id="step-upload" class="bg-white dark:bg-dark-card p-8 rounded-xl shadow border-2 border-dashed border-gray-300 dark:border-slate-600 text-center">
        <i class="fas fa-file-csv text-5xl text-green-600 mb-4"></i>
        <p class="text-gray-500 mb-4">Wybierz plik CSV ze swojego banku.</p>
        <input type="file" id="csvFile" accept=".csv" class="hidden" onchange="processFile()">
        <button onclick="document.getElementById('csvFile').click()" class="bg-green-600 text-white px-6 py-2 rounded">Wybierz plik</button>
    </div>

    <div id="step-map" class="hidden bg-white dark:bg-dark-card p-6 rounded-xl shadow mt-6">
        <h3 class="font-bold dark:text-white mb-4">Mapowanie Kolumn</h3>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div><label class="block text-sm dark:text-gray-300">Data</label><select id="col-date" class="w-full border p-2 rounded dark:bg-slate-800"></select></div>
            <div><label class="block text-sm dark:text-gray-300">Opis</label><select id="col-desc" class="w-full border p-2 rounded dark:bg-slate-800"></select></div>
            <div><label class="block text-sm dark:text-gray-300">Kwota</label><select id="col-amount" class="w-full border p-2 rounded dark:bg-slate-800"></select></div>
        </div>
        <button onclick="uploadData()" class="bg-blue-600 text-white px-6 py-2 rounded">Importuj</button>
    </div>
</div>

<script>
    let parsedData = [], headers = [];
    function processFile() {
        const file = document.getElementById('csvFile').files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const lines = e.target.result.split('\n').filter(l=>l.trim()!=='');
            const sep = lines[0].includes(';') ? ';' : ',';
            headers = lines[0].split(sep).map(h=>h.replace(/"/g,'').trim());
            parsedData = lines.slice(1).map(l=>l.split(sep).map(c=>c.replace(/"/g,'').trim()));
            
            document.getElementById('step-upload').classList.add('hidden');
            document.getElementById('step-map').classList.remove('hidden');
            
            ['col-date','col-desc','col-amount'].forEach(id=>{
                const sel = document.getElementById(id);
                sel.innerHTML='';
                headers.forEach((h,i)=>{
                    const opt=document.createElement('option'); opt.value=i; opt.text=h;
                    sel.appendChild(opt);
                });
            });
        };
        reader.readAsText(file);
    }

    async function uploadData() {
        const payload = parsedData.map(row=>({
            date: row[document.getElementById('col-date').value],
            description: row[document.getElementById('col-desc').value],
            amount: row[document.getElementById('col-amount').value]
        })).filter(r=>r.date&&r.amount);
        
        const res = await fetch('../api/process_import.php', {
            method: 'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({transactions:payload})
        });
        const result = await res.json();
        if(result.success) { showToast(`Dodano: ${result.added}, Pominięto: ${result.skipped}`); setTimeout(()=>location='transactions.php', 2000); }
    }
</script>

<?php require_once '../includes/footer.php'; ?>