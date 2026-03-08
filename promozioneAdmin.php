<?php
session_start();

// Sicurezza: Solo un admin loggato può eseguire questa pagina
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once 'connessione.php';

$messaggio_successo = "";
$messaggio_errore = "";

if (isset($_POST['idUtente'])) {
    $idUtente = $_POST['idUtente'];

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "UPDATE utente SET ruolo = 'admin' WHERE id_utente = :idUtente";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':idUtente', $idUtente, PDO::PARAM_INT);
        $stmt->execute();

        // Controlliamo se la query ha effettivamente modificato un utente
        if ($stmt->rowCount() > 0) {
            $messaggio_successo = "Utente promosso ad Amministratore con successo!";
        } else {
            $messaggio_errore = "L'utente specificato non esiste o è già un amministratore.";
        }

    } catch (PDOException $e) {
        $messaggio_errore = "Errore di connessione al database: " . $e->getMessage();
    }
} else {
    $messaggio_errore = "Nessun utente specificato per la promozione.";
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promozione Utente - Admin</title>
    <style>
        /* ====== PALETTE E STILI GLOBALI ====== */
        :root { 
            --primary: #1e3a8a; 
            --primary-hover: #1e40af; 
            --accent: #f59e0b; 
            --bg-color: #f8fafc; 
            --card-bg: #ffffff; 
            --text-main: #334155; 
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
            padding: 20px; 
            text-align: center; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
        }
        
        .header h1 { margin: 0; font-size: 1.6rem; }

        .container { 
            max-width: 500px; 
            margin: 60px auto; 
            background-color: var(--card-bg); 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); 
            text-align: center; 
            border-top: 5px solid var(--primary); 
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .msg-success { 
            background-color: #dcfce7; 
            color: #166534; 
            padding: 20px; 
            border-radius: 6px; 
            margin-bottom: 25px; 
            border-left: 4px solid #10b981; 
            font-weight: bold; 
            font-size: 1.1rem;
        }

        .msg-error { 
            background-color: #fef2f2; 
            color: #b91c1c; 
            padding: 20px; 
            border-radius: 6px; 
            margin-bottom: 25px; 
            border-left: 4px solid #ef4444; 
            font-weight: bold; 
            font-size: 1.1rem;
        }

        .btn { 
            display: inline-block; 
            background-color: var(--primary); 
            color: white; 
            text-decoration: none; 
            padding: 12px 25px; 
            border-radius: 6px; 
            font-weight: bold; 
            transition: 0.3s; 
            margin-top: 10px;
        }

        .btn:hover { 
            background-color: var(--primary-hover); 
        }
    </style>
</head>

<body>

    <header class="header">
        <h1>Sistema di Amministrazione</h1>
    </header>

    <div class="container">
        
        <?php if (!empty($messaggio_successo)): ?>
            <div class="icon">✅</div>
            <div class="msg-success"><?php echo $messaggio_successo; ?></div>
        <?php endif; ?>

        <?php if (!empty($messaggio_errore)): ?>
            <div class="icon">❌</div>
            <div class="msg-error"><?php echo $messaggio_errore; ?></div>
        <?php endif; ?>

        <a href="visualizzaListaUtenti.php" class="btn">&larr; Torna alla lista utenti</a>

    </div>

</body>
</html>