<?php
session_start();
require_once 'connessione.php';

$errore = "";
$successo = "";

// Recuperiamo il token sia se arriviamo via GET (dal link della mail) sia via POST (quando inviamo il form)
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

// Se qualcuno prova ad aprire la pagina senza token, lo blocchiamo subito
if (empty($token)) {
    die("Errore: Token di sicurezza mancante. <a href='login.php'>Torna al login</a>");
}

// Funzione di validazione password (la stessa della registrazione)
function validaPassword($pwd) {
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    return preg_match($pattern, $pwd);
}

try {
    $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Cerchiamo se il token esiste ed è associato a un utente
    $sql = "SELECT id_utente, scadenza_token FROM utente WHERE token_recupero = :token";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':token' => $token]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utente) {
        $errore = "Il link di recupero non è valido o è già stato utilizzato.";
    } else {
        // 2. Il token esiste! Controlliamo se è scaduto
        $scadenza = new DateTime($utente['scadenza_token']);
        $ora_attuale = new DateTime();

        if ($ora_attuale > $scadenza) {
            $errore = "Il link di recupero è scaduto (sono passati più di 15 minuti). <br> <a href='recupero_password.php' style='color: #1e3a8a;'>Richiedine uno nuovo qui.</a>";
        } else {
            // 3. Il token è valido e NON è scaduto. Gestiamo l'invio del form.
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $password = $_POST['password'];
                $conferma = $_POST['conferma_password'];

                if ($password !== $conferma) {
                    $errore = "Le password non coincidono!";
                } elseif (!validaPassword($password)) {
                    $errore = "La password deve contenere almeno 8 caratteri, una maiuscola, una minuscola, un numero e un carattere speciale.";
                } else {
                    // TUTTO CORRETTO: Salviamo la nuova password
                    $pw_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Aggiorniamo la password e, molto importante, SVUOTIAMO il token e la scadenza
                    $sql_update = "UPDATE utente SET pw_hash = :hash, token_recupero = NULL, scadenza_token = NULL WHERE id_utente = :id";
                    $stmt_up = $pdo->prepare($sql_update);
                    $stmt_up->execute([
                        ':hash' => $pw_hash, 
                        ':id' => $utente['id_utente']
                    ]);

                    $successo = "La tua password è stata aggiornata con successo! Ora puoi accedere. <br><br> <a href='login.php' class='btn-submit' style='display:inline-block; text-decoration:none; text-align:center;'>Vai al Login</a>";
                }
            }
        }
    }
} catch (PDOException $e) {
    $errore = "Errore di connessione al database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scegli Nuova Password - Lotteria</title>
    <style>
        :root { --primary: #1e3a8a; --primary-hover: #1e40af; --accent: #f59e0b; --bg-color: #f8fafc; --card-bg: #ffffff; --text-main: #334155; --text-light: #64748b; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border-top: 5px solid var(--primary); }
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { width: 100%; padding: 10px; padding-right: 40px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; transition: 0.3s; }
        .password-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
        
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.3s;}
        .btn-submit:hover { background-color: var(--primary-hover); }

        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem; text-align: left;}
        .msg-success { background-color: #f0fdf4; color: #15803d; padding: 20px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #22c55e; line-height: 1.5; text-align: center;}
        
        .password-hint { font-size: 0.75rem; color: var(--text-light); margin-top: 4px; display: block; }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Nuova Password</h2>
        
        <?php if (!empty($errore)): ?>
            <div class="msg-errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <?php if (!empty($successo)): ?>
            <div class="msg-success"><?php echo $successo; ?></div>
        <?php elseif (empty($errore) || strpos($errore, 'scaduto') === false && strpos($errore, 'non è valido') === false): ?>
            <form method="POST" action="reset_password.php">
                
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">Nuova Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password" onclick="toggleVisibility('password', this)">👁️</button>
                    </div>
                    <span class="password-hint">Min. 8 caratteri, 1 maiuscola, 1 minuscola, 1 numero, 1 carattere speciale.</span>
                </div>

                <div class="form-group">
                    <label for="conferma_password">Conferma Nuova Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="conferma_password" name="conferma_password" required>
                        <button type="button" class="toggle-password" onclick="toggleVisibility('conferma_password', this)">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Salva la Nuova Password</button>
            </form>
        <?php endif; ?>

    </div>

    <script>
        function toggleVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === "password") { input.type = "text"; btn.textContent = "🙈"; } 
            else { input.type = "password"; btn.textContent = "👁️"; }
        }
    </script>
</body>
</html>