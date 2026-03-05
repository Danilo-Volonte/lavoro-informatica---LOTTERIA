<?php
session_start();
require "connessione.php";
$id = $_GET['id'];

$informazioneLotteria = null;

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //prendere tutte le informazioni sulla lotteria in questione
    $sql = "SELECT * FROM lotteria WHERE id_lotteria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $informazioneLotteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        :root {
            --primary: #1e3a8a;
            --primary-hover: #1e40af;
            --accent: #f59e0b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--bg-color);
            color: var(--text-main);
        }
        .header {
            background-color: var(--primary);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }
        .btn {
            background-color: var(--card-bg);
            color: var(--primary);
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn:hover { background-color: #e2e8f0; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
            border-bottom: 3px solid var(--accent);
            padding-bottom: 5px;
            margin-bottom: 40px;
            font-weight: bold;
        }
        .info-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-top: 5px solid var(--primary);
        }
        .info-row { margin: 20px 0; font-size: 1.1rem; }
        .info-label { font-weight: bold; color: var(--primary); }
        .back-link { margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="section-title">Dettagli Biglietto</h2>
        
        <?php if ($informazioneLotteria && count($informazioneLotteria) > 0): ?>
            <div class="info-card">
                <?php foreach ($informazioneLotteria as $lotteria): ?>
                    <div class="info-row">
                        <span class="info-label">Nome:</span> <?php echo htmlspecialchars($lotteria['nome']); ?>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prezzo biglietto:</span> <?php echo htmlspecialchars($lotteria['prezzo_biglietto']); ?> Crediti
                    </div>
                    <div class="info-row">
                        <span class="info-label">Data fine:</span> <?php echo htmlspecialchars($lotteria['data_fine']); ?>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Stato:</span>
                        <?php 
                            if($lotteria['aperta'] == 1) {
                                echo "la lotteria è aperta";
                            } else {
                                echo "la lotteria è chiusa";
                            }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-light);">Nessun biglietto trovato.</p>
        <?php endif; ?>
        
        <a href="index.php" class="btn back-link">&larr; Torna alla home</a>
    </div>
</body>
</html>