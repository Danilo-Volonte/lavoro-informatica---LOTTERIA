<?php
session_start();
$errore = "";
$successo = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = trim($_POST['email']);

        // 1. Controlliamo se l'email esiste nel database
        $sql = "SELECT id_utente, nickname FROM utente WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utente) {
            // L'utente esiste! 
            // 2. Generiamo un Token univoco molto lungo (64 caratteri)
            $token = bin2hex(random_bytes(32)); 
            
            // 3. Impostiamo la scadenza a 15 minuti da adesso
            $scadenza = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // 4. Salviamo il token e la scadenza nel database
            $sql_update = "UPDATE utente SET token_recupero = :token, scadenza_token = :scadenza WHERE id_utente = :id_utente";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':token' => $token,
                ':scadenza' => $scadenza,
                ':id_utente' => $utente['id_utente']
            ]);

            // 5. Inviamo l'email con il link di recupero
            $oggetto = "Recupero Password - Lotteria";
            $intestazioni  = "MIME-Version: 1.0\r\n";
            $intestazioni .= "Content-type: text/html; charset=UTF-8\r\n";
            $intestazioni .= "From: sicurezza@lotteria.it\r\n";

            // Il link punterà a una nuova pagina (reset_password.php) passandogli il token nell'URL
            $link_recupero = "http://localhost/tuacartella/reset_password.php?token=" . $token;

            $messaggio_email = "
            <html>
            <body>
                <h2>Ciao " . htmlspecialchars($utente['nickname']) . ",</h2>
                <p>Abbiamo ricevuto una richiesta per reimpostare la tua password.</p>
                <p>Clicca sul pulsante qui sotto per crearne una nuova. <b>Il link scadrà tra 15 minuti.</b></p>
                <br>
                <a href='$link_recupero' style='background-color: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reimposta Password</a>
                <br><br>
                <p><small>Se il bottone non funziona, copia e incolla questo link nel browser: <br> $link_recupero</small></p>
                <p><small>Se non hai fatto tu questa richiesta, ignora questa email.</small></p>
            </body>
            </html>
            ";

            @mail($email, $oggetto, $messaggio_email, $intestazioni);

            // Mostriamo un messaggio di successo generico
            $successo = "Se l'indirizzo email è registrato, riceverai a breve un link per reimpostare la password.";
            
        } else {
            // NOTA DI SICUREZZA: Anche se l'email non esiste, mostriamo lo stesso messaggio 
            // per evitare che gli hacker scoprano quali email sono registrate sul nostro sito!
            $successo = "Se l'indirizzo email è registrato, riceverai a breve un link per reimpostare la password.";
        }

    } catch (PDOException $e) {
        $errore = "Errore di connessione al database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupero Password - Lotteria</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border-top: 5px solid var(--primary); text-align: center; }
        .form-container h2 { margin-top: 0; color: var(--primary); margin-bottom: 15px; }
        .form-container p { color: var(--text-light); margin-bottom: 25px; font-size: 0.95rem; }
        
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        .form-group > input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; transition: 0.3s; }
        .form-group > input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.3s;}
        .btn-submit:hover { background-color: var(--primary-hover); }

        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem; text-align: left;}
        .msg-success { background-color: #f0fdf4; color: #15803d; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #22c55e; line-height: 1.5; text-align: left;}
        
        .links { margin-top: 25px; text-align: center; font-size: 0.9rem; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; color: var(--primary-hover);}
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Password Dimenticata?</h2>
        
        <?php if (!empty($successo)): ?>
            <div class="msg-success"><?php echo $successo; ?></div>
            <div class="links">
                <a href="login.php">&larr; Torna al Login</a>
            </div>
        <?php else: ?>
            <p>Inserisci l'indirizzo email associato al tuo account. Ti invieremo un link per reimpostare la tua password.</p>

            <?php if (!empty($errore)): ?>
                <div class="msg-errore"><?php echo $errore; ?></div>
            <?php endif; ?>

            <form method="POST" action="recupero_password.php">
                <div class="form-group">
                    <label for="email">La tua Email</label>
                    <input type="email" id="email" name="email" required placeholder="es. mario@email.it">
                </div>
                <button type="submit" class="btn-submit">Invia Link di Recupero</button>
            </form>

            <div class="links">
                <a href="login.php">&larr; Annulla e torna al Login</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>