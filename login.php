<?php
session_start();

// Se l'utente è già loggato, lo mandiamo alla home
if (isset($_SESSION['id_utente'])) {
    header("Location: index.php");
    exit;
}

$errore = "";
$messaggio_info = isset($_GET['msg']) ? htmlspecialchars(trim($_GET['msg'])) : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'connessione.php';

    try {
        $pdo = new PDO($conn_str, $conn_usr, $conn_psw);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // 1. Aggiungiamo 'attivo' alla SELECT
        $sql = "SELECT id_utente, pw_hash, ruolo, nickname, attivo FROM utente WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Controlliamo se l'utente esiste e la password è corretta
        if ($utente && password_verify($password, $utente['pw_hash'])) {
            
            // 3. CONTROLLO SERVER MAIL: L'account è stato verificato?
            if ($utente['attivo'] == 0) {
                // Account non attivo: lo blocchiamo e lo mandiamo a inserire il codice!
                header("Location: verifica.php?email=" . urlencode($email));
                exit;
            }

            // Se è attivo, login completato con successo!
            $_SESSION['id_utente'] = $utente['id_utente'];
            $_SESSION['ruolo'] = $utente['ruolo'];
            $_SESSION['nickname'] = $utente['nickname'];

            header("Location: index.php");
            exit;
        } else {
            $errore = "Email o password errati.";
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
    <title>Accedi - Lotteria</title>
    <style>
        /* ================= PALETTE COLORI ================= */
        :root { 
            --primary: #1e3a8a; 
            --primary-hover: #1e40af; 
            --accent: #f59e0b; 
            --bg-color: #f8fafc; 
            --card-bg: #ffffff; 
            --text-main: #334155; 
            --text-light: #64748b; 
        }

        body { font-family: 'Segoe UI', sans-serif; margin: 0; background-color: var(--bg-color); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        
        .form-container { background-color: var(--card-bg); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; border-top: 5px solid var(--primary); }
        .form-container h2 { margin-top: 0; color: var(--primary); text-align: center; margin-bottom: 25px; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        
        /* OCCHIOLINO PASSWORD */
        .password-wrapper { position: relative; display: flex; align-items: center; }
        .password-wrapper input { width: 100%; padding: 10px; padding-right: 40px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .password-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        .toggle-password { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 1.2rem; }
        
        .form-group > input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .form-group > input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1); }
        
        .btn-submit { background-color: var(--primary); color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.3s;}
        .btn-submit:hover { background-color: var(--primary-hover); }

        .msg-errore { background-color: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-size: 0.9rem;}
        .msg-info { background-color: #eff6ff; color: #1d4ed8; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3b82f6; font-size: 0.9rem; text-align: center;}
        
        .links { margin-top: 25px; text-align: center; font-size: 0.9rem; display: flex; flex-direction: column; gap: 10px; }
        .links a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; color: var(--primary-hover);}
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Accedi</h2>

        <?php if (!empty($messaggio_info)): ?>
            <div class="msg-info"><?php echo $messaggio_info; ?></div>
        <?php endif; ?>

        <?php if (!empty($errore)): ?>
            <div class="msg-errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required placeholder="Inserisci la password">
                    <button type="button" class="toggle-password" onclick="toggleVisibility('password', this)" title="Mostra/Nascondi">👁️</button>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Entra</button>
        </form>

        <div class="links">
            <a href="recupero_password.php">Hai dimenticato la password?</a>
            <div>
                <span style="color: var(--text-light);">Non sei registrato?</span> 
                <a href="registrazione.php">Crea un account</a>
            </div>
            <a href="index.php" style="color: var(--text-light); font-weight: normal; margin-top: 10px; font-size: 0.8rem;">&larr; Torna alla Home</a>
        </div>
    </div>

    <script>
        function toggleVisibility(inputId, btnElement) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                btnElement.textContent = "🙈"; 
            } else {
                input.type = "password";
                btnElement.textContent = "👁️"; 
            }
        }
    </script>
</body>
</html>