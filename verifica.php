<?php
session_start();
require_once 'connessione.php';

$errore = "";
$successo = "";

// Recuperiamo l'email dall'URL (quando arriviamo dalla registrazione) o dal Form (quando inviamo il codice)
$email = isset($_GET['email']) ? trim($_GET['email']) : "";
if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
}

// Se non c'è nessuna email da verificare, rimandiamo alla home
if (empty($email)) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codice_inserito = trim($_POST['codice']);

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Cerchiamo l'utente con quella email che NON è ancora attivo (attivo = 0)
        $sql = "SELECT id_utente, codice_verifica FROM utente WHERE email = :email AND attivo = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Controlliamo se l'utente esiste e se il codice combacia
        if ($utente) {
            if ($utente['codice_verifica'] === $codice_inserito) {
                // IL CODICE È GIUSTO! Attiviamo l'account e svuotiamo il codice
                $sql_update = "UPDATE utente SET attivo = 1, codice_verifica = NULL WHERE id_utente = :id";
                $stmt_up = $pdo->prepare($sql_update);
                $stmt_up->execute([':id' => $utente['id_utente']]);

                $successo = "Account verificato con successo! I tuoi 100 crediti ti aspettano. <br><br> <a href='login.php' class='btn-submit' style='display:inline-block; text-decoration:none;'>Vai al Login</a>";
            } else {
                $errore = "Il codice inserito non è corretto. Riprova.";
            }
        } else {
            $errore = "Account non trovato o già attivato.";
        }
    } catch (PDOException $e) {
        $errore = "Errore del database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Account - Lotteria</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border-top: 5px solid var(--primary); text-align: center; }
        .form-container h2 { margin-top: 0; color: var(--primary); margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; text-align: left;}
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        
        /* Stile specifico per il codice a 6 cifre */
        .input-code { width: 100%; padding: 15px; border: 2px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: monospace; font-size: 1.5rem; text-align: center; letter-spacing: 5px; font-weight: bold; transition: border-color 0.2s; }
        .input-code:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 10px; transition: background-color 0.3s;}
        .btn-submit:hover { background-color: var(--primary-hover); }
        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem;}
        .msg-success { background-color: #f0fdf4; color: #15803d; padding: 20px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #22c55e; line-height: 1.5;}
        .email-display { background: #e2e8f0; padding: 10px; border-radius: 5px; font-weight: bold; margin-bottom: 20px; color: var(--primary); word-break: break-all;}
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Verifica Email</h2>
        
        <?php if (!empty($successo)): ?>
            <div class="msg-success"><?php echo $successo; ?></div>
        <?php else: ?>
            <p style="color: var(--text-light); margin-bottom: 20px;">Abbiamo inviato un codice a 6 cifre al tuo indirizzo email.</p>
            
            <div class="email-display"><?php echo htmlspecialchars($email); ?></div>

            <?php if (!empty($errore)): ?>
                <div class="msg-errore"><?php echo $errore; ?></div>
            <?php endif; ?>

            <form method="POST" action="verifica.php">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label for="codice">Inserisci il codice di sicurezza</label>
                    <input type="text" id="codice" name="codice" class="input-code" maxlength="6" pattern="\d{6}" required placeholder="123456" title="Inserisci esattamente 6 numeri">
                </div>

                <button type="submit" class="btn-submit">attiva il tuo account</button> 
            </form>
        <?php endif; ?>
    </div>
</body>
</html>