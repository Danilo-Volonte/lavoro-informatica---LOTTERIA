<?php
session_start();
require "connessione.php";
$id = $_GET['id'];


$id = $_GET['id'];
$lotteria = null;

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //prendere tutte le informazioni sulla lotteria in questione
    $sql = "SELECT * FROM lotteria WHERE id_lotteria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $informazioneLotteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lotteria = $stmt->fetch(PDO::FETCH_ASSOC);

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
        .header h1 { margin: 0; font-size: 1.8rem; letter-spacing: 1px; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
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
        
        <?php if ($informazioneLotteria && count($informazioneLotteria) > 0): ?>
            <div class="info-card">
                <?php foreach ($informazioneLotteria as $lotteria): ?>
                    <div class="info-row">
                    </div>
                
                    <div class="info-row">
                    </div>
                
                    <div class="info-row">
                    </div>
                
                    <div class="info-row">
                        <span class="info-label">Stato:</span>
                    </div>
            </div>

        <?php else: ?>
        <?php endif; ?>
        
    </div>
</body>
</html>