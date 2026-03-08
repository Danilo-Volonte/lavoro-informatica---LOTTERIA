<?php
session_start();
require 'connessione.php';

// Controlli di sicurezza
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) && !isset($_POST['id_lotteria'])) {
    die("Errore: ID lotteria mancante. Torna alla home.");
}

$id_lotteria = $_GET['id'] ?? $_POST['id_lotteria'];
$messaggio_errore = "";

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Recuperiamo i dati della lotteria per fare i calcoli
    $sql_lotteria = "SELECT nome, prezzo_biglietto, n_biglietti_tot, n_biglietti_vincenti FROM lotteria WHERE id_lotteria = :id";
    $stmt_l = $pdo->prepare($sql_lotteria);
    $stmt_l->execute([':id' => $id_lotteria]);
    $lotteria = $stmt_l->fetch(PDO::FETCH_ASSOC);

    if (!$lotteria) {
        die("Lotteria non trovata.");
    }

    // 2. MATEMATICA DEL MONTEPREMI
    $ricavato_totale = $lotteria['n_biglietti_tot'] * $lotteria['prezzo_biglietto'];
    $montepremi_da_distribuire = $ricavato_totale * 0.85; // 85% del ricavato
    $num_premi = $lotteria['n_biglietti_vincenti'];

    // 3. Elaborazione Form POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $percentuali = $_POST['percentuali']; // Questo è l'array con le percentuali
        
        // Calcoliamo la somma delle percentuali inserite
        $somma_percentuali = array_sum($percentuali);

        if ($somma_percentuali != 100) {
            $messaggio_errore = "Attenzione: La somma delle percentuali deve essere esattamente 100. Attualmente è $somma_percentuali.";
        } else {
            // =========================================================
            // FIX: Nomi delle colonne allineati al Diagramma E/R!
            // ID_LOTTERIA, POSIZIONE, VINCITA
            // =========================================================
            $sql_premio = "INSERT INTO premio (id_lotteria, posizione, vincita) VALUES (:id_lot, :posizione, :valore)";
            $stmt_p = $pdo->prepare($sql_premio);

            foreach ($percentuali as $index => $perc) {
                $posizione = $index + 1; // L'array parte da 0, la posizione da 1 (1° posto, 2° posto...)
                $valore_premio = $montepremi_da_distribuire * ($perc / 100);

                $stmt_p->execute([
                    ':id_lot' => $id_lotteria,
                    ':posizione' => $posizione,
                    ':valore' => $valore_premio
                ]);
            }

            // Tutto finito! Rimandiamo l'admin a vedere il capolavoro
            header("Location: dettaglio_lotteria.php?id=" . $id_lotteria);
            exit;
        }
    }

} catch (PDOException $e) {
    die("Errore database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configura Premi - Admin</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); }
        .header { background-color: var(--primary); color: white; padding: 20px 30px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .info-panel { background-color: #eff6ff; border-left: 5px solid var(--primary); padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0; }
        .info-panel h3 { margin-top: 0; color: var(--primary); }
        .form-container { background-color: var(--card-bg); padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); }
        .premio-row { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #e2e8f0; }
        .premio-row:last-child { border-bottom: none; }
        .premio-label { font-weight: bold; font-size: 1.1rem; width: 150px;}
        .premio-input { width: 100px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-size: 1.1rem; }
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 15px; width: 100%; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-weight: bold; }
        
        /* Contatore in tempo reale */
        .status-bar { background: #334155; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-size: 1.2rem; position: sticky; top: 10px;}
        .valore-calcolato { color: var(--text-light); font-size: 0.9rem; margin-left: 15px; width: 150px; text-align: right;}
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin:0;">Configurazione Premi</h1>
    </header>

    <div class="container">
        
        <div class="info-panel">
            <h3>Riepilogo Lotteria: <?php echo htmlspecialchars($lotteria['nome']); ?></h3>
            <p><strong>Ricavato Totale:</strong> <?php echo number_format($ricavato_totale, 2); ?> Crediti</p>
            <p><strong>Montepremi da Distribuire (85%):</strong> <span style="color:var(--accent); font-weight:bold; font-size:1.2rem;"><?php echo number_format($montepremi_da_distribuire, 2); ?> Crediti</span></p>
            <p><strong>Numero di Vincitori:</strong> <?php echo $num_premi; ?></p>
        </div>

        <?php if (!empty($messaggio_errore)): ?>
            <div class="msg-errore"><?php echo $messaggio_errore; ?></div>
        <?php endif; ?>

        <div class="status-bar">
            Somma Attuale: <span id="sommaTotale" style="color: var(--accent); font-weight: bold;">0</span>% / 100%
        </div>

        <div class="form-container">
            <form method="POST" action="inserisciPremi.php" id="formPremi">
                <input type="hidden" name="id_lotteria" value="<?php echo htmlspecialchars($id_lotteria); ?>">
                
                <?php for ($i = 0; $i < $num_premi; $i++): ?>
                    <div class="premio-row">
                        <span class="premio-label"><?php echo $i + 1; ?>° Premio:</span>
                        
                        <div>
                            <input type="number" name="percentuali[]" class="premio-input perc-input" min="0" max="100" step="0.1" required placeholder="%"> 
                            <span style="font-size: 1.2rem; font-weight:bold;">%</span>
                        </div>
                        
                        <span class="valore-calcolato" id="valore_<?php echo $i; ?>">0.00 Crediti</span>
                    </div>
                <?php endfor; ?>

                <button type="submit" class="btn-submit">Salva e Attiva Lotteria</button>
            </form>
        </div>

    </div>

    <script>
        // JS per far vedere all'admin la somma in tempo reale e i crediti per posizione
        const montepremi = <?php echo $montepremi_da_distribuire; ?>;
        const inputs = document.querySelectorAll('.perc-input');
        const sommaDisplay = document.getElementById('sommaTotale');

        function aggiornaCalcoli() {
            let somma = 0;
            inputs.forEach((input, index) => {
                let perc = parseFloat(input.value) || 0;
                somma += perc;
                
                // Calcola e mostra i crediti reali per questa posizione
                let creditiReali = (montepremi * (perc / 100)).toFixed(2);
                document.getElementById('valore_' + index).textContent = creditiReali + ' Crediti';
            });
            
            sommaDisplay.textContent = somma.toFixed(1);
            
            // Colora di rosso se sballa il 100%, verde se è perfetto
            if(Math.abs(somma - 100) < 0.01) { // Tolleranza per arrotondamenti decimali
                sommaDisplay.style.color = '#10b981'; 
            } else if (somma > 100) { 
                sommaDisplay.style.color = '#ef4444'; 
            } else { 
                sommaDisplay.style.color = '#f59e0b'; 
            }
        }

        inputs.forEach(input => {
            input.addEventListener('input', aggiornaCalcoli);
        });
    </script>
</body>
</html>