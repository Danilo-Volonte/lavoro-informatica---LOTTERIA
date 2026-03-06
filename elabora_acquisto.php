<?php
session_start();

// 1. Sicurezza: Utente loggato e richiesta POST
if (!isset($_SESSION['id_utente'])) {
    header("Location: login.php?msg=" . urlencode("Devi accedere per acquistare."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_lotteria'])) {
    header("Location: index.php");
    exit;
}

require_once 'connessione.php';

$id_utente = $_SESSION['id_utente'];
$id_lotteria = intval($_POST['id_lotteria']);

$esito = false;
$messaggio = "";
$nuovo_saldo = 0;
$numero_biglietto = 0;

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // =========================================================================
    // INIZIO TRANSAZIONE: Tutto quello che c'è qui dentro o viene fatto al 100% 
    // oppure viene annullato del tutto se c'è un errore.
    // =========================================================================
    $pdo->beginTransaction();

    // 1. Controlliamo i crediti dell'utente
    $sql_utente = "SELECT crediti FROM utente WHERE id_utente = :id_utente FOR UPDATE";
    $stmt_utente = $pdo->prepare($sql_utente);
    $stmt_utente->execute([':id_utente' => $id_utente]);
    $utente = $stmt_utente->fetch(PDO::FETCH_ASSOC);

    // 2. Controlliamo il prezzo e lo stato della lotteria
    $sql_lotteria = "SELECT nome, prezzo_biglietto, aperta FROM lotteria WHERE id_lotteria = :id_lotteria";
    $stmt_lotteria = $pdo->prepare($sql_lotteria);
    $stmt_lotteria->execute([':id_lotteria' => $id_lotteria]);
    $lotteria = $stmt_lotteria->fetch(PDO::FETCH_ASSOC);

    // Verifiche logiche
    if (!$lotteria) {
        throw new Exception("Lotteria non trovata.");
    }
    if ($lotteria['aperta'] == 0) {
        throw new Exception("Spiacenti, questa lotteria è chiusa.");
    }
    if ($utente['crediti'] < $lotteria['prezzo_biglietto']) {
        throw new Exception("Crediti insufficienti. Hai " . $utente['crediti'] . " crediti, ma il biglietto ne costa " . $lotteria['prezzo_biglietto'] . ".");
    }

    // 3. Troviamo il prossimo NUMERO del biglietto disponibile per questa lotteria
    // Usiamo IFNULL per gestire il caso in cui sia il primissimo biglietto venduto (diventerà 0 + 1 = 1)
    $sql_max_num = "SELECT IFNULL(MAX(numero), 0) + 1 AS prossimo_numero FROM biglietto WHERE id_lotteria = :id_lotteria";
    $stmt_max_num = $pdo->prepare($sql_max_num);
    $stmt_max_num->execute([':id_lotteria' => $id_lotteria]);
    $numero_biglietto = $stmt_max_num->fetchColumn();

    // 4. SCALIAMO I CREDITI
    $nuovo_saldo = $utente['crediti'] - $lotteria['prezzo_biglietto'];
    $sql_paga = "UPDATE utente SET crediti = :nuovo_saldo WHERE id_utente = :id_utente";
    $stmt_paga = $pdo->prepare($sql_paga);
    $stmt_paga->execute([':nuovo_saldo' => $nuovo_saldo, ':id_utente' => $id_utente]);

    // 5. INSERIAMO IL BIGLIETTO
    $sql_insert = "INSERT INTO biglietto (numero, id_utente, id_lotteria) VALUES (:numero, :id_utente, :id_lotteria)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([
        ':numero' => $numero_biglietto,
        ':id_utente' => $id_utente,
        ':id_lotteria' => $id_lotteria
    ]);

    // =========================================================================
    // TUTTO È ANDATO BENE: Confermiamo le modifiche nel database!
    // =========================================================================
    $pdo->commit();
    
    $esito = true;
    $messaggio = "Hai acquistato con successo il biglietto della lotteria <strong>" . htmlspecialchars(strtoupper($lotteria['nome'])) . "</strong>!";

} catch (Exception $e) {
    // =========================================================================
    // ERRORE: Annulliamo tutte le modifiche fatte finora (Rollback)
    // =========================================================================
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $esito = false;
    $messaggio = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esito Acquisto - Lotteria</title>
    <style>
        /* ================= PALETTE COLORI GLOBALE ================= */
        :root {
            --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b;
            --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;}
        
        .card { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; text-align: center; border-top: 5px solid var(--primary); }
        .card h2 { margin-top: 0; text-transform: uppercase; }
        
        .success-text { color: #16a34a; }
        .error-text { color: #dc2626; }

        .ticket-box { background-color: #f8fafc; border: 2px dashed var(--primary); border-radius: 10px; padding: 20px; margin: 20px 0; }
        .ticket-number { font-size: 2rem; font-weight: bold; color: var(--primary); display: block; margin: 10px 0;}
        
        .saldo-box { background-color: #fffbeb; color: #b45309; padding: 10px; border-radius: 5px; font-weight: bold; margin-bottom: 25px;}

        .btn { padding: 12px 25px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; width: 100%; box-sizing: border-box;}
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: #e2e8f0; color: var(--text-main); margin-top: 10px; }
        .btn-secondary:hover { background-color: #cbd5e1; }
    </style>
</head>
<body>

    <div class="card">
        
        <?php if ($esito): ?>
            <h2 class="success-text">🎉 ACQUISTO COMPLETATO!</h2>
            <p><?php echo $messaggio; ?></p>
            
            <div class="ticket-box">
                Il tuo numero di biglietto è:
                <span class="ticket-number"># <?php echo $numero_biglietto; ?></span>
            </div>

            <div class="saldo-box">
                Il tuo nuovo saldo è: <?php echo $nuovo_saldo; ?> crediti
            </div>

            <a href="profilo.php" class="btn btn-primary">Vai al tuo Profilo</a>
            <a href="index.php" class="btn btn-secondary">Torna alle Lotterie</a>

        <?php else: ?>
            <h2 class="error-text">❌ OPS, ERRORE!</h2>
            <p style="font-size: 1.1rem; margin: 20px 0;"><?php echo htmlspecialchars($messaggio); ?></p>
            
            <a href="index.php" class="btn btn-primary">Torna alla Home</a>
        <?php endif; ?>

    </div>

</body>
</html>