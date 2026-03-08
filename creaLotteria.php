<?php
session_start();
require 'connessione.php';

// Controlli di sicurezza: solo admin loggati possono accedere
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$messaggio_successo = "";
$messaggio_errore = "";

// Variabili per Sticky Form (mantiene i dati se c'è un errore)
$nome_inserito = "";
$prezzo_inserito = "";
$data_fine_inserita = "";
$totali_inseriti = "";
$vincenti_inseriti = "";

// Elaborazione form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nomeLotteria'], $_POST['prezzoBiglietto'], $_POST['dataFine'], $_POST['bigliettiTotali'], $_POST['bigliettiVincenti'])) {
        
        $nome_inserito = trim($_POST['nomeLotteria']);
        $prezzo_inserito = $_POST['prezzoBiglietto'];
        $data_fine_inserita = $_POST['dataFine'];
        $totali_inseriti = $_POST['bigliettiTotali'];
        $vincenti_inseriti = $_POST['bigliettiVincenti'];

        $idUtente = $_SESSION['id_utente']; // Usa ID admin dalla sessione
        $prezzoBiglietto = (float) $prezzo_inserito;
        $bigliettiTotali = (int) $totali_inseriti;
        $bigliettiVincenti = (int) $vincenti_inseriti;

        // L'input datetime-local invia la data con una 'T' in mezzo (es. 2026-03-08T18:30)
        // Dobbiamo sostituirla con uno spazio per renderla compatibile con MySQL (2026-03-08 18:30)
        $dataFineFormattata = str_replace('T', ' ', $data_fine_inserita);

        // Validazioni
        $percentuale = ($bigliettiTotali > 0) ? (($bigliettiVincenti / $bigliettiTotali) * 100) : 0;
        
        if (empty($nome_inserito)) {
             $messaggio_errore = "Il nome della lotteria è obbligatorio.";
        } elseif ($prezzoBiglietto < 1 || $prezzoBiglietto > 5) {
             $messaggio_errore = "Il prezzo del biglietto deve essere tra 1 e 5 crediti.";
        } elseif ($bigliettiTotali <= 0) {
             $messaggio_errore = "Il numero di biglietti totali deve essere maggiore di zero.";
        } elseif ($bigliettiVincenti <= 0 || $bigliettiVincenti > $bigliettiTotali) {
             $messaggio_errore = "I biglietti vincenti devono essere > 0 e <= ai biglietti totali.";
        } elseif ($percentuale < 10) {
             $messaggio_errore = "La percentuale di biglietti vincenti deve essere almeno il 10% del totale.";
        } elseif (strtotime($dataFineFormattata) <= time()) {
             $messaggio_errore = "La data e l'ora di fine lotteria devono essere nel futuro.";
        } else {
            // Tutto corretto: salviamo!
            // data_inizio deve essere DATETIME come da database (Y-m-d H:i:s)
            $dataInizio = date('Y-m-d H:i:s'); 
            
            try {
                $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = "INSERT INTO lotteria (nome, id_utente, prezzo_biglietto, data_inizio, data_fine, n_biglietti_tot, n_biglietti_vincenti, aperta) 
                        VALUES (:nome, :id_u, :prez_b, :data_inizio, :data_fine, :bigl_tot, :bigl_vinc, 1)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':nome', $nome_inserito, PDO::PARAM_STR);
                $stmt->bindParam(':id_u', $idUtente, PDO::PARAM_INT);
                $stmt->bindParam(':prez_b', $prezzoBiglietto, PDO::PARAM_STR);
                $stmt->bindParam(':data_inizio', $dataInizio, PDO::PARAM_STR);
                $stmt->bindParam(':data_fine', $dataFineFormattata, PDO::PARAM_STR);
                $stmt->bindParam(':bigl_tot', $bigliettiTotali, PDO::PARAM_INT);
                $stmt->bindParam(':bigl_vinc', $bigliettiVincenti, PDO::PARAM_INT);
                
                // Eseguiamo l'inserimento
                $stmt->execute();

                // =========================================================
                // MAGIA: Recupero l'ID della lotteria e mando l'Admin ai premi!
                // =========================================================
                $id_nuova_lotteria = $pdo->lastInsertId();
                header("Location: inserisciPremi.php?id=" . $id_nuova_lotteria);
                exit;
                
            } catch (PDOException $e) {
                $messaggio_errore = "Errore database: " . $e->getMessage();
            }
        }
    } else {
        $messaggio_errore = "Dati mancanti per la creazione della lotteria.";
    }
}

$is_logged_in = isset($_SESSION['id_utente']);
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Lotteria - Admin</title>
    <style>
        /* ====== PALETTE E STILI GLOBALI ====== */
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); }
        .header { background-color: var(--primary); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }
        .btn { background-color: var(--card-bg); color: var(--primary); padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: all 0.3s ease; border: 2px solid transparent; }
        .btn:hover { background-color: #e2e8f0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; text-align: center; }
        .section-title { display: inline-block; font-size: 1.5rem; color: var(--primary); border-bottom: 3px solid var(--accent); padding-bottom: 5px; margin-bottom: 40px; font-weight: bold; }
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); width: 100%; max-width: 500px; margin: 0 auto; border-top: 5px solid var(--primary); }
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: var(--text-main); }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background-color 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: var(--primary-hover); }
        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem; text-align: left;}
        .links { margin-top: 25px; text-align: center; font-size: 0.9rem; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .links a:hover { color: var(--accent); text-decoration: underline; }
    </style>
</head>

<body>

    <header class="header">
        <h1>LOTTERIE ONLINE - ADMIN</h1>
        <?php if ($is_logged_in): ?>
            <a href="profilo.php" class="btn">IL MIO PROFILO</a>
        <?php else: ?>
            <a href="login.php" class="btn">ACCEDI</a>
        <?php endif; ?>
    </header>

    <div class="container">
        <h2 class="section-title">Nuova Estrazione</h2>

        <div class="form-container">
            <h2>Dettagli Lotteria</h2>

            <?php if (!empty($messaggio_errore)): ?>
                <div class="msg-errore"><?php echo htmlspecialchars($messaggio_errore); ?></div>
            <?php endif; ?>

            <form method="POST" action="creaLotteria.php">
                <div class="form-group">
                    <label for="nomeLotteria">Nome Lotteria</label>
                    <input type="text" id="nomeLotteria" name="nomeLotteria" value="<?php echo htmlspecialchars($nome_inserito); ?>" required placeholder="Es. Spring Fortune">
                </div>

                <div class="form-group">
                    <label for="prezzoBiglietto">Prezzo Biglietto (1-5 Crediti)</label>
                    <input type="number" id="prezzoBiglietto" name="prezzoBiglietto" value="<?php echo htmlspecialchars($prezzo_inserito); ?>" min="1" max="5" step="0.5" required placeholder="3">
                </div>

                <div class="form-group">
                    <label for="dataFine">Data ed Ora Estrazione</label>
                    <input type="datetime-local" id="dataFine" name="dataFine" value="<?php echo htmlspecialchars($data_fine_inserita); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bigliettiTotali">Numero Biglietti Totali</label>
                    <input type="number" id="bigliettiTotali" name="bigliettiTotali" value="<?php echo htmlspecialchars($totali_inseriti); ?>" min="1" required placeholder="100">
                </div>

                <div class="form-group">
                    <label for="bigliettiVincenti">Numero Biglietti Vincenti (min 10% del totale)</label>
                    <input type="number" id="bigliettiVincenti" name="bigliettiVincenti" value="<?php echo htmlspecialchars($vincenti_inseriti); ?>" min="1" required placeholder="10">
                </div>

                <button type="submit" class="btn-submit">Prosegui ai Premi &rarr;</button>
            </form>

            <div class="links">
                <a href="index.php">&larr; Torna alla Home</a>
            </div>
        </div>
    </div>

</body>
</html>