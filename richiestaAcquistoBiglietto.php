<?php
session_start();
require_once "connessione.php";

// 1. Controllo di sicurezza: verifichiamo che l'ID sia stato passato
if (!isset($_GET["id_lotteria"])) {
    die("Errore: Nessuna lotteria selezionata. <a href='index.php'>Torna alla Home</a>");
}

$idLotteria = $_GET["id_lotteria"];
$lotteria = null;

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prendiamo le info sulla lotteria (anche il nome fa comodo per la grafica!)
    $sql = "SELECT nome, prezzo_biglietto FROM lotteria WHERE id_lotteria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $idLotteria, PDO::PARAM_INT);
    $stmt->execute();
    
    // USIAMO fetch() INVECE DI fetchAll()
    $lotteria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lotteria) {
        die("Errore: Lotteria non trovata.");
    }

} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acquisto Biglietto - Lotteria</title>
    <style>
        /* ================= PALETTE COLORI GLOBALE ================= */
        :root {
            --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b;
            --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155;
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px;}
        
        .card { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; border-top: 5px solid var(--primary); }
        .card h2 { margin-top: 0; color: var(--primary); text-transform: uppercase; }
        
        .price-box { background-color: #fffbeb; border: 2px solid var(--accent); border-radius: 10px; padding: 20px; margin: 20px 0; }
        .price-box .amount { display: block; font-size: 2.5rem; font-weight: bold; color: #d97706; }
        .price-box .label { font-size: 1rem; color: #b45309; font-weight: bold; }

        .btn-group { display: flex; gap: 10px; justify-content: center; margin-top: 30px; }
        
        .btn { padding: 12px 25px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; flex: 1; text-decoration: none; display: inline-block;}
        .btn-yes { background-color: #22c55e; color: white; }
        .btn-yes:hover { background-color: #16a34a; }
        .btn-no { background-color: #ef4444; color: white; }
        .btn-no:hover { background-color: #dc2626; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Conferma Acquisto</h2>
        <p>Stai per acquistare un biglietto per la lotteria:<br> <strong><?php echo htmlspecialchars($lotteria['nome']); ?></strong></p>
        
        <div class="price-box">
            <span class="amount"><?php echo htmlspecialchars($lotteria['prezzo_biglietto']); ?></span>
            <span class="label">Crediti</span>
        </div>

        <p>Vuoi procedere con l'acquisto?</p>

        <div class="btn-group">
            <a href="index.php" class="btn btn-no">NO</a>

            <form action="elabora_acquisto.php" method="POST" style="flex: 1;">
                <input type="hidden" name="id_lotteria" value="<?php echo htmlspecialchars($idLotteria); ?>">
                <button type="submit" class="btn btn-yes" style="width: 100%;">SÌ, ACQUISTA</button>
            </form>
        </div>
    </div>

</body>
</html>