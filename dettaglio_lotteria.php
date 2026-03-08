<?php
session_start();
require "connessione.php";

if (!isset($_GET['id'])) {
    die("Errore: ID lotteria mancante.");
}

$id = $_GET['id'];
$lotteria = null;
$venduti = 0; // Nuova variabile per contare i biglietti venduti

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Recupero i dettagli della lotteria
    $sql = "SELECT * FROM lotteria WHERE id_lotteria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $lotteria = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Se la lotteria esiste, conto quanti biglietti sono già stati venduti
    if ($lotteria) {
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM biglietto WHERE id_lotteria = :id_lott");
        $stmt_count->execute([':id_lott' => $id]);
        $venduti = $stmt_count->fetchColumn();
    }

} catch (PDOException $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['id_utente']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Lotteria</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); }
        .header { background-color: var(--primary); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }
        .btn-top { background-color: var(--card-bg); color: var(--primary); padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: all 0.3s ease; }
        .btn-top:hover { background-color: #e2e8f0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .section-title { font-size: 1.5rem; color: var(--primary); border-bottom: 3px solid var(--accent); padding-bottom: 5px; margin-bottom: 40px; display: inline-block; font-weight: bold; }
        .info-card { background-color: var(--card-bg); border-radius: 10px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid var(--primary); max-width: 500px; margin: 0 auto;}
        .info-row { margin: 15px 0; font-size: 1.1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;}
        .info-row:last-of-type { border-bottom: none; }
        .info-label { font-weight: bold; color: var(--text-light); display: block; font-size: 0.9rem; text-transform: uppercase;}
        .info-value { font-size: 1.2rem; font-weight: 600; color: var(--primary); }
        .btn-acquista { background-color: var(--accent); color: white; border: none; padding: 15px 20px; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px; text-transform: uppercase; transition: background-color 0.3s, transform 0.1s; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3); }
        .btn-acquista:hover { background-color: #d97706; transform: translateY(-2px); }
        .btn-acquista:disabled { background-color: #cbd5e1; cursor: not-allowed; box-shadow: none; transform: none; }
        .back-link { display: inline-block; margin-top: 30px; color: var(--text-light); text-decoration: none; }
        .back-link:hover { color: var(--primary); text-decoration: underline; }
    </style>
</head>
<body>

    <header class="header">
        <h1>LOTTERIE ONLINE</h1>
        <?php if ($is_logged_in): ?>
            <a href="profilo.php" class="btn-top">IL MIO PROFILO</a>
        <?php else: ?>
            <a href="login.php" class="btn-top">ACCEDI</a>
        <?php endif; ?>
    </header>

    <div class="container">
        
        <?php if ($lotteria): ?>
            <h2 class="section-title">Dettagli Lotteria</h2>
            
            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">Nome:</span> 
                    <span class="info-value"><?php echo htmlspecialchars(strtoupper($lotteria['nome'])); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Prezzo biglietto:</span> 
                    <span class="info-value"><?php echo htmlspecialchars($lotteria['prezzo_biglietto']); ?> Crediti</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Data ed Ora Estrazione:</span> 
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($lotteria['data_fine'])); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Stato:</span>
                    <span class="info-value">
                        <?php echo ($lotteria['aperta'] == 1) ? "<span style='color:#16a34a;'>Aperta</span>" : "<span style='color:#dc2626;'>Chiusa</span>"; ?>
                    </span>
                </div>

                <div class="info-row" style="text-align: center; margin-top: 25px; border-bottom: none;">
                    <span style="font-weight: bold; color: var(--text-main); font-size: 1.1rem;">
                        Biglietti rimanenti: 
                        <span style="color: var(--accent); font-size: 1.3rem;">
                            <?php echo ($lotteria['n_biglietti_tot'] - $venduti); ?> / <?php echo $lotteria['n_biglietti_tot']; ?>
                        </span>
                    </span>
                </div>

                <?php 
                // LOGICA DEL BOTTONE: Controllo sold out e scadenza temporale
                $is_sold_out = ($venduti >= $lotteria['n_biglietti_tot']);
                $is_scaduta = (time() > strtotime($lotteria['data_fine'])); 

                // Disabilita se chiusa, scaduta o sold out
                $isDisabled = ($lotteria['aperta'] == 0 || $is_scaduta || $is_sold_out) ? 'disabled' : ''; 

                // Testo dinamico del bottone
                $testo_bottone = 'Acquista il biglietto';
                if ($is_sold_out) {
                    $testo_bottone = 'SOLD OUT';
                } elseif ($is_scaduta || $lotteria['aperta'] == 0) {
                    $testo_bottone = 'Estrazione in corso...';
                }
                ?>

                <form action="richiestaAcquistoBiglietto.php" method="GET">
                    <input type="hidden" name="id_lotteria" value="<?php echo htmlspecialchars($_GET["id"]); ?>">
                    <button type="submit" class="btn-acquista" <?php echo $isDisabled; ?> style="<?php echo $is_sold_out ? 'background-color: #94a3b8;' : ''; ?>">
                        <?php echo $testo_bottone; ?>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <p style="color: var(--text-light); text-align: center; margin-top: 50px;">
                Nessuna lotteria è stata trovata
            </p>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="index.php" class="back-link">&larr; Torna alla home</a>
        </div>
    </div>
</body>
</html>